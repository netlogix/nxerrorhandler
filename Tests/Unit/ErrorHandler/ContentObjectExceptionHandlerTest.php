<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\ErrorHandler;

use Exception;
use Netlogix\Nxerrorhandler\ErrorHandler\ContentObjectExceptionHandler;
use Netlogix\Nxerrorhandler\ErrorHandler\GeneralExceptionHandler;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentObjectExceptionHandlerTest extends UnitTestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function itReThrowsExceptionIfRunningInCli()
    {
        $ex = new Exception(uniqid(), time());

        $this->expectException(get_class($ex));

        $subject = $this->getMockBuilder(ContentObjectExceptionHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isCli'])
            ->getMock();

        $subject->expects(self::once())->method('isCli')->willReturn(true);

        $subject->handle($ex);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itWillCallGeneralExceptionHandlerAndExitIfNotRunningInCli()
    {
        $ex = new Exception(uniqid(), time());

        $mockExceptionHandler = $this->getMockBuilder(GeneralExceptionHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['handleException'])
            ->getMock();
        $mockExceptionHandler->expects(self::once())->method('handleException')->with($ex);
        GeneralUtility::setSingletonInstance(GeneralExceptionHandler::class, $mockExceptionHandler);

        $subject = $this->getMockBuilder(ContentObjectExceptionHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['exit', 'isCli'])
            ->getMock();

        $subject->expects(self::once())->method('isCli')->willReturn(false);
        $subject->expects(self::once())->method('exit');

        $subject->handle($ex);
    }

}
