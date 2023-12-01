<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\ErrorHandler;

use Exception;
use Netlogix\Nxerrorhandler\ErrorHandler\GeneralExceptionHandler;
use Netlogix\Nxerrorhandler\Tests\Unit\Fixtures\ComponentFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Error\Http\StatusException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

class GeneralExceptionHandlerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://www.example.com/');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * @test
     * @dataProvider statusHeaderDataProvider
     */
    public function itCanParseErrorCodeFromHeaders(array $headers, int $expected)
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $res = $subject->_call('parseStatusHeadersForCode', $headers);

        self::assertEquals($expected, $res);
    }

    public static function statusHeaderDataProvider(): array
    {
        $data = [];
        // this is a selection of relevant codes
        $codes = [400, 401, 404, 410, 418, 500, 503];

        foreach ($codes as $code) {
            $line = constant('\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_' . $code);

            $data[$line] = [[$line], $code];
        }

        return $data;
    }

    /**
     * @test
     */
    public function itFallsBackToStatus500IfNoneIsFoundInHeaders()
    {
        $headers = ['X-Foo: Bar'];

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $res = $subject->_call('parseStatusHeadersForCode', $headers);

        self::assertEquals(500, $res);
    }

    /**
     * @test
     */
    public function itDoesNotAddStatusCodesIfNoComponentsAreRegistered()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $subject->_set('components', []);
        $exception = new Exception(uniqid(), time());

        $res = $subject->_call('getStatusHeaders', $exception);

        self::assertEmpty($res);
    }

    /**
     * @test
     */
    public function itGetsStatusHeadersFromComponents()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());
        $expected = rand(100, 599);

        $componentMock = $this->getMockBuilder(ComponentFixture::class)
            ->onlyMethods(['getHttpHeaders'])
            ->getMock();
        $componentMock->expects(self::once())->method('getHttpHeaders')->with($exception)->willReturn([$expected]);

        $components = [$componentMock];
        $subject->_set('components', $components);

        $res = $subject->_call('getStatusHeaders', $exception);

        self::assertEquals($res, [$expected]);
    }

    /**
     * @test
     */
    public function itMergesStatusHeadersFromMultipleComponents()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $codes = [rand(100, 599), rand(100, 599)];
        foreach ($codes as $code) {
            $componentMock = $this->getMockBuilder(ComponentFixture::class)
                ->onlyMethods(['getHttpHeaders'])
                ->getMock();
            $componentMock->expects(self::once())->method('getHttpHeaders')->with($exception)->willReturn([$code]);
            $components[] = $componentMock;
        }

        $subject->_set('components', $components);

        $res = $subject->_call('getStatusHeaders', $exception);

        self::assertEquals($res, $codes);
    }

    /**
     * @test
     */
    public function sendStatusCodesFallsBackToStatus500IfNonIsAvailable()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $subject->_set('components', []);

        $res = $subject->_call('sendStatusCodes', $exception);

        self::assertEquals(500, $res);
    }

    /**
     * @test
     */
    public function sendStatusCodesGetsStatusCodeFromComponents()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $code = 418;
        $headerLine = HttpUtility::HTTP_STATUS_418;

        $componentMock = $this->getMockBuilder(ComponentFixture::class)
            ->onlyMethods(['getHttpHeaders'])
            ->getMock();
        $componentMock->expects(self::once())->method('getHttpHeaders')->with($exception)->willReturn([$headerLine]);

        $subject->_set('components', [$componentMock]);

        $res = $subject->_call('sendStatusCodes', $exception);

        self::assertEquals($code, $res);
    }

    /**
     * @test
     */
    public function sendStatusCodesGetsStatusCodeFromException()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $code = 418;
        $headerLine = HttpUtility::HTTP_STATUS_418;

        $exception = new StatusException($headerLine, uniqid(), uniqid(), time());

        $subject->_set('components', []);

        $res = $subject->_call('sendStatusCodes', $exception);

        self::assertEquals($code, $res);
    }

    /**
     * @test
     */
    public function sendStatusCodesSendsHeaders()
    {
        self::markTestSkipped(
            'Testing response codes needs enabled "processIsolation". This slows down tests immensely.'
        );

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $code = 418;
        $headerLine = HttpUtility::HTTP_STATUS_418;

        $exception = new StatusException($headerLine, uniqid(), uniqid(), time());

        $subject->_set('components', []);

        $subject->_call('sendStatusCodes', $exception);

        $res = http_response_code();

        self::assertEquals($res, $code);
    }

    /**
     * @test
     */
    public function itCanGetErrorDocumentFromComponent()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $content = uniqid('content_');
        $code = rand(100, 599);

        $componentMock = $this->getMockBuilder(ComponentFixture::class)
            ->onlyMethods(['getOutput'])
            ->getMock();
        $componentMock->expects(self::once())->method('getOutput')->willReturn($content);

        $subject->_set('components', [$componentMock]);

        $res = $subject->_call('getErrorDocument', $code, uniqid('message_'), $exception);

        self::assertEquals($content, $res);
    }

    /**
     * @test
     */
    public function itFallsBackToErrorDocumentFromErrorPageController()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $content = uniqid('content_');
        $code = rand(100, 599);

        $controllerMock = $this->getMockBuilder(ErrorPageController::class)
            ->onlyMethods(['errorAction'])
            ->disableOriginalConstructor()
            ->getMock();
        $controllerMock->expects(self::once())->method('errorAction')->willReturn($content);
        GeneralUtility::addInstance(ErrorPageController::class, $controllerMock);

        $subject->_set('components', []);

        $res = $subject->_call('getErrorDocument', $code, uniqid('message_'), $exception);

        self::assertEquals($content, $res);
    }

    /**
     * @test
     */
    public function itThrowsExceptionIfNoComponentIsRegistered()
    {
        $this->expectException(\Netlogix\Nxerrorhandler\Exception\Exception::class);
        $this->expectExceptionCode(1395075649);

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxerrorhandler']['exceptionHandlerComponents'] = [];

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $subject->_call('initialize');
    }

    /**
     * @test
     */
    public function itThrowsExceptionIfConfiguredComponentDoesnotExist()
    {
        $this->expectException(\Netlogix\Nxerrorhandler\Exception\Exception::class);
        $this->expectExceptionCode(1395074867);

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxerrorhandler']['exceptionHandlerComponents'] = ['NotAClass'];

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $subject->_call('initialize');
    }

    /**
     * @test
     */
    public function itLoadsComponentsFromConfiguration()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxerrorhandler']['exceptionHandlerComponents'] = [
            ComponentFixture::class,
        ];

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $subject->_call('initialize');

        $components = $subject->_get('components');

        self::assertNotEmpty($components);
        self::assertCount(1, $components);
        self::assertInstanceOf(ComponentFixture::class, $components[0]);
    }
}
