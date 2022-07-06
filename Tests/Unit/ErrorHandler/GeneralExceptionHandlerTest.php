<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\ErrorHandler;

use Exception;
use Netlogix\Nxerrorhandler\ErrorHandler\GeneralExceptionHandler;
use Netlogix\Nxerrorhandler\Tests\Unit\Fixtures\ComponentFixture;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Error\Http\StatusException;
use TYPO3\CMS\Core\Utility\HttpUtility;

class GeneralExceptionHandlerTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider statusHeaderDataProvider
     *
     * @return void
     */
    public function itCanParseErrorCodeFromHeaders(array $headers, int $expected)
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, ['dummy']);

        $res = $subject->_callRef('parseStatusHeadersForCode', $headers);

        self::assertEquals($expected, $res);
    }

    public function statusHeaderDataProvider(): array
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
     *
     * @return void
     */
    public function itFallsBackToStatus500IfNoneIsFoundInHeaders()
    {
        $headers = [
            'X-Foo: Bar'
        ];

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, ['dummy']);

        $res = $subject->_callRef('parseStatusHeadersForCode', $headers);

        self::assertEquals(500, $res);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itDoesNotAddStatusCodesIfNoComponentsAreRegistered()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, ['dummy']);
        $subject->_set('components', []);
        $exception = new Exception(uniqid(), time());

        $res = $subject->_callRef('getStatusHeaders', $exception);

        self::assertEmpty($res);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itGetsStatusHeadersFromComponents()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, ['dummy']);
        $exception = new Exception(uniqid(), time());
        $expected = rand(100, 599);

        $componentMock = $this->getMockBuilder(ComponentFixture::class)
            ->onlyMethods(['getHttpHeaders'])
            ->getMock();
        $componentMock->expects(self::once())->method('getHttpHeaders')->with($exception)->willReturn([$expected]);

        $components = [
            $componentMock
        ];
        $subject->_set('components', $components);

        $res = $subject->_callRef('getStatusHeaders', $exception);

        self::assertEquals($res, [$expected]);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itMergesStatusHeadersFromMultipleComponents()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, ['dummy']);
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

        $res = $subject->_callRef('getStatusHeaders', $exception);

        self::assertEquals($res, $codes);
    }

    /**
     * @test
     *
     * @return void
     */
    public function sendStatusCodesFallsBackToStatus500IfNonIsAvailable()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, ['dummy']);
        $exception = new Exception(uniqid(), time());

        $subject->_set('components', []);

        $res = $subject->_callRef('sendStatusCodes', $exception);

        self::assertEquals(500, $res);
    }

    /**
     * @test
     *
     * @return void
     */
    public function sendStatusCodesGetsStatusCodeFromComponents()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, ['dummy']);
        $exception = new Exception(uniqid(), time());

        $code = 418;
        $headerLine = HttpUtility::HTTP_STATUS_418;

        $componentMock = $this->getMockBuilder(ComponentFixture::class)
            ->onlyMethods(['getHttpHeaders'])
            ->getMock();
        $componentMock->expects(self::once())->method('getHttpHeaders')->with($exception)->willReturn([$headerLine]);

        $subject->_set('components', [$componentMock]);

        $res = $subject->_callRef('sendStatusCodes', $exception);

        self::assertEquals($code, $res);
    }

    /**
     * @test
     *
     * @return void
     */
    public function sendStatusCodesGetsStatusCodeFromException()
    {
        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, ['dummy']);

        $code = 418;
        $headerLine = HttpUtility::HTTP_STATUS_418;

        $exception = new StatusException($headerLine, uniqid(), uniqid(), time());

        $subject->_set('components', []);

        $res = $subject->_callRef('sendStatusCodes', $exception);

        self::assertEquals($code, $res);
    }

    /**
     * @test
     *
     * @return void
     */
    public function sendStatusCodesSendsHeaders()
    {
        self::markTestSkipped(
            'Testing response codes needs enabled "processIsolation". This slows down tests immensely.'
        );

        $subject = $this->getAccessibleMock(GeneralExceptionHandler::class, ['dummy']);

        $code = 418;
        $headerLine = HttpUtility::HTTP_STATUS_418;

        $exception = new StatusException($headerLine, uniqid(), uniqid(), time());

        $subject->_set('components', []);


        $subject->_callRef('sendStatusCodes', $exception);

        $res = http_response_code();

        self::assertEquals($res, $code);
    }
}
