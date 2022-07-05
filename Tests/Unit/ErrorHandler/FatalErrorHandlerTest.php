<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\ErrorHandler;

use Netlogix\Nxerrorhandler\ErrorHandler\FatalErrorHandler;
use Netlogix\Nxerrorhandler\Tests\Unit\Fixtures\ExceptionHandlerFixture;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FatalErrorHandlerTest extends UnitTestCase
{

    public function tearDown(): void
    {
        parent::tearDown();

        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     *
     * @return void
     */
    public function itRegistersShutdownFunction()
    {
        $subject = $this->getMockBuilder(FatalErrorHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['registerShutdownFunction'])
            ->getMock();

        // PHP does not offer an API to access shutdown functions
        $subject->expects(self::once())->method('registerShutdownFunction');


        $subject->initialize();
    }

    /**
     * @test
     *
     * @return void
     */
    public function itWillReserveMemory()
    {
        $memorySize = rand(10, 100);

        $subject = new FatalErrorHandler();

        $memUsageBefore = memory_get_usage();

        $subject->initialize($memorySize);

        $memUsageAfter = memory_get_usage();

        self::assertGreaterThan($memUsageBefore + (1024 * $memorySize), $memUsageAfter);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itWillFreeReservedMemory()
    {
        $memorySize = rand(10, 100);

        $subject = new FatalErrorHandler();

        $subject->initialize($memorySize);

        $memUsageAfterInit = memory_get_usage();

        $subject->handleFatalError();

        $memUsageAfterHandle = memory_get_usage();

        self::assertLessThan($memUsageAfterInit - (1024 * $memorySize), $memUsageAfterHandle);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itWillNotCallExceptionHandlerIfNoErrorOccurred()
    {
        $subject = $this->getMockBuilder(FatalErrorHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLastError'])
            ->getMock();

        $handlerMock = $this->getMockBuilder(ExceptionHandlerFixture::class)
            ->onlyMethods(['handleException'])
            ->getMock();
        $handlerMock->expects(self::never())->method('handleException');
        GeneralUtility::addInstance(ExceptionHandlerFixture::class, $handlerMock);

        $subject->expects(self::once())->method('getLastError')->willReturn(null);

        $subject->handleFatalError();
    }

    /**
     * @test
     *
     * @return void
     */
    public function itWillNotCallExceptionHandlerIfNoExceptionHandlerIsRegistered()
    {
        $subject = $this->getMockBuilder(FatalErrorHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLastError', 'getExceptionHandlerClassName'])
            ->getMock();

        $handlerMock = $this->getMockBuilder(ExceptionHandlerFixture::class)
            ->onlyMethods(['handleException'])
            ->getMock();
        $handlerMock->expects(self::never())->method('handleException');
        GeneralUtility::addInstance(ExceptionHandlerFixture::class, $handlerMock);

        $subject->expects(self::once())->method('getLastError')->willReturn(
            ['type' => 0, 'message' => 'foo', 'file' => '/dev/null', 'line' => 1]
        );
        $subject->expects(self::once())->method('getExceptionHandlerClassName')->willReturn(null);

        $subject->handleFatalError();
    }

    /**
     * @test
     *
     * @return void
     */
    public function itWillNotCallExceptionHandlerForInvalidErrorCode()
    {
        $subject = $this->getMockBuilder(FatalErrorHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLastError', 'getExceptionHandlerClassName'])
            ->getMock();

        $handlerMock = $this->getMockBuilder(ExceptionHandlerFixture::class)
            ->onlyMethods(['handleException'])
            ->getMock();
        $handlerMock->expects(self::never())->method('handleException');
        GeneralUtility::addInstance(ExceptionHandlerFixture::class, $handlerMock);


        $subject->expects(self::once())->method('getLastError')->willReturn(
            ['type' => 0, 'message' => 'foo', 'file' => '/dev/null', 'line' => 1]
        );
        $subject->expects(self::once())->method('getExceptionHandlerClassName')->willReturn(
            ExceptionHandlerFixture::class
        );

        $subject->handleFatalError();
    }

    /**
     * @test
     * @dataProvider errorCodeDataProvider
     * @return void
     */
    public function itWillCallExceptionHandlerForValidErrorCodes(int $code)
    {
        $handlerMock = $this->getMockBuilder(ExceptionHandlerFixture::class)
            ->onlyMethods(['handleException'])
            ->getMock();
        $handlerMock->expects(self::once())->method('handleException');
        GeneralUtility::addInstance(ExceptionHandlerFixture::class, $handlerMock);


        $subject = $this->getMockBuilder(FatalErrorHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLastError', 'getExceptionHandlerClassName'])
            ->getMock();

        $subject->expects(self::once())->method('getLastError')->willReturn(
            ['type' => $code, 'message' => 'foo', 'file' => '/dev/null', 'line' => 1]
        );
        $subject->expects(self::any())->method('getExceptionHandlerClassName')->willReturn(
            ExceptionHandlerFixture::class
        );

        $subject->handleFatalError();
    }

    /**
     * @test
     *
     * @return void
     */
    public function itCanGetRegisteredExceptionHandlerClassName()
    {
        $class = new ReflectionClass(FatalErrorHandler::class);
        $method = $class->getMethod('getExceptionHandlerClassName');
        $method->setAccessible(true);

        $subject = new FatalErrorHandler();

        $expected = uniqid();
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = $expected;

        $res = $method->invoke($subject);

        self::assertEquals($expected, $res);
    }

    public function errorCodeDataProvider(): array
    {
        return [
            'Fatal run-time errors' => [E_ERROR],
            'Compile-time parse errors' => [E_PARSE],
            "Fatal errors that occur during PHP's initial startup" => [E_CORE_ERROR],
            "Warnings (non-fatal errors) that occur during PHP's initial startup" => [E_CORE_WARNING],
            'Fatal compile-time errors' => [E_COMPILE_ERROR],
            'Compile-time warnings (non-fatal errors)' => [E_COMPILE_WARNING],
            'Strict Errors' => [E_STRICT],
        ];
    }
}

