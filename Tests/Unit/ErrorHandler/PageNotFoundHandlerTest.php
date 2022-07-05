<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\ErrorHandler;

use Netlogix\Nxerrorhandler\ErrorHandler\Component\StaticDocumentComponent;
use Netlogix\Nxerrorhandler\ErrorHandler\PageNotFoundHandler;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;

class PageNotFoundHandlerTest extends UnitTestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function isUsesContentFromStaticDocumentComponent()
    {
        $expectedContent = str_repeat(uniqid(), 100);

        $mockComponent = $this->getMockBuilder(StaticDocumentComponent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOutput'])
            ->getMock();
        $mockComponent->expects(self::once())->method('getOutput')->willReturn($expectedContent);
        GeneralUtility::addInstance(StaticDocumentComponent::class, $mockComponent);

        $subject = new PageNotFoundHandler();
        $res = $subject->handlePageNotFound(['reasonText' => 'foo'], new ErrorController());

        self::assertEquals($expectedContent, $res);
    }

    /**
     * @test
     *
     * @return void
     */
    public function isWillFallBackToErrorPageIfDtaticComponentHasNoContent()
    {
        $expectedContent = str_repeat(uniqid(), 100);

        $mockComponent = $this->getMockBuilder(StaticDocumentComponent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOutput'])
            ->getMock();
        $mockComponent->expects(self::once())->method('getOutput')->willReturn('');
        GeneralUtility::addInstance(StaticDocumentComponent::class, $mockComponent);


        $mockErrorPage = $this->getMockBuilder(ErrorPageController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['errorAction'])
            ->getMock();
        $mockErrorPage->expects(self::once())->method('errorAction')->willReturn($expectedContent);
        GeneralUtility::addInstance(ErrorPageController::class, $mockErrorPage);

        $subject = new PageNotFoundHandler();
        $res = $subject->handlePageNotFound(['reasonText' => 'foo'], new ErrorController());

        self::assertEquals($expectedContent, $res);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_REQUEST'] = $this->getMockBuilder(ServerRequest::class)->disableOriginalConstructor()->getMock(
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TYPO3_REQUEST']);
    }
}
