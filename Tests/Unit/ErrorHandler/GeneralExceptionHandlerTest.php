<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\ErrorHandler;

use Exception;
use Netlogix\Nxerrorhandler\ErrorHandler\GeneralExceptionHandler;
use Netlogix\Nxerrorhandler\Tests\Unit\Fixtures\ComponentFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Error\Http\StatusException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GeneralExceptionHandlerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://www.example.com/');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        restore_exception_handler();

        parent::tearDown();
    }

    #[DataProvider('statusHeaderDataProvider')]
    #[Test]
    public function itCanParseErrorCodeFromHeaders(array $headers, int $expected): void
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
            $line = constant(HttpUtility::class . '::HTTP_STATUS_' . $code);

            $data[$line] = [[$line], $code];
        }

        return $data;
    }

    #[Test]
    public function itFallsBackToStatus500IfNoneIsFoundInHeaders(): void
    {
        $headers = ['X-Foo: Bar'];

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $res = $subject->_call('parseStatusHeadersForCode', $headers);

        self::assertEquals(500, $res);
    }

    #[Test]
    public function itDoesNotAddStatusCodesIfNoComponentsAreRegistered(): void
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $subject->_set('components', []);

        $exception = new Exception(uniqid(), time());

        $res = $subject->_call('getStatusHeaders', $exception);

        self::assertEmpty($res);
    }

    #[Test]
    public function itGetsStatusHeadersFromComponents(): void
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());
        $expected = random_int(100, 599);

        $componentMock = $this->createMock(ComponentFixture::class);
        $componentMock->expects(self::once())->method('getHttpHeaders')->with($exception)->willReturn([$expected]);

        $components = [$componentMock];
        $subject->_set('components', $components);

        $res = $subject->_call('getStatusHeaders', $exception);

        self::assertEquals($res, [$expected]);
    }

    #[Test]
    public function itMergesStatusHeadersFromMultipleComponents(): void
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $codes = [random_int(100, 599), random_int(100, 599)];
        foreach ($codes as $code) {
            $componentMock = $this->createMock(ComponentFixture::class);
            $componentMock->expects(self::once())->method('getHttpHeaders')->with($exception)->willReturn([$code]);
            $components[] = $componentMock;
        }

        $subject->_set('components', $components);

        $res = $subject->_call('getStatusHeaders', $exception);

        self::assertEquals($res, $codes);
    }

    #[Test]
    public function sendStatusCodesFallsBackToStatus500IfNonIsAvailable(): void
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $subject->_set('components', []);

        $res = $subject->_call('sendStatusCodes', $exception);

        self::assertEquals(500, $res);
    }

    #[Test]
    public function sendStatusCodesGetsStatusCodeFromComponents(): void
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $code = 418;
        $headerLine = HttpUtility::HTTP_STATUS_418;

        $componentMock = $this->createMock(ComponentFixture::class);
        $componentMock->expects(self::once())->method('getHttpHeaders')->with($exception)->willReturn([$headerLine]);

        $subject->_set('components', [$componentMock]);

        $res = $subject->_call('sendStatusCodes', $exception);

        self::assertEquals($code, $res);
    }

    #[Test]
    public function sendStatusCodesGetsStatusCodeFromException(): void
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $code = 418;
        $headerLine = HttpUtility::HTTP_STATUS_418;

        $exception = new StatusException($headerLine, uniqid(), uniqid(), time());

        $subject->_set('components', []);

        $res = $subject->_call('sendStatusCodes', $exception);

        self::assertEquals($code, $res);
    }

    #[Test]
    public function sendStatusCodesSendsHeaders(): never
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

    #[Test]
    public function itCanGetErrorDocumentFromComponent(): void
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $content = uniqid('content_');
        $code = random_int(100, 599);

        $componentMock = $this->createMock(ComponentFixture::class);
        $componentMock->expects(self::once())->method('getOutput')->willReturn($content);

        $subject->_set('components', [$componentMock]);

        $res = $subject->_call('getErrorDocument', $code, uniqid('message_'), $exception);

        self::assertEquals($content, $res);
    }

    #[Test]
    public function itFallsBackToErrorDocumentFromErrorPageController(): void
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);
        $exception = new Exception(uniqid(), time());

        $content = uniqid('content_');
        $code = random_int(100, 599);

        $controllerMock = $this->createMock(ErrorPageController::class);
        $controllerMock->expects(self::once())->method('errorAction')->willReturn($content);
        GeneralUtility::addInstance(ErrorPageController::class, $controllerMock);

        $subject->_set('components', []);

        $res = $subject->_call('getErrorDocument', $code, uniqid('message_'), $exception);

        self::assertEquals($content, $res);
    }

    #[Test]
    public function itThrowsExceptionIfNoComponentIsRegistered(): void
    {
        $this->expectException(\Netlogix\Nxerrorhandler\Exception\Exception::class);
        $this->expectExceptionCode(1395075649);

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxerrorhandler']['exceptionHandlerComponents'] = [];

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $subject->_call('initialize');
    }

    #[Test]
    public function itThrowsExceptionIfConfiguredComponentDoesnotExist(): void
    {
        $this->expectException(\Netlogix\Nxerrorhandler\Exception\Exception::class);
        $this->expectExceptionCode(1395074867);

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxerrorhandler']['exceptionHandlerComponents'] = ['NotAClass'];

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, null);

        $subject->_call('initialize');
    }

    #[Test]
    public function itLoadsComponentsFromConfiguration(): void
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
