<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\Error;

use Netlogix\Nxerrorhandler\Error\PageContentErrorHandler;
use Netlogix\Nxerrorhandler\ErrorHandler\Component\StaticDocumentComponent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageContentErrorHandlerTest extends UnitTestCase
{
    private MockObject&PageContentErrorHandler $subject;

    #[Test]
    public function itReturnsJsonResponseForJsonRequest(): void
    {
        $req = new ServerRequest();
        $req = $req->withHeader('Accept', 'application/json');

        $res = $this->subject->handlePageError($req, 'fooMessage');

        self::assertInstanceOf(JsonResponse::class, $res);
    }

    #[Test]
    public function itReturnsJsonResponseForJsonApiRequest(): void
    {
        $req = new ServerRequest();
        $req = $req->withHeader('Accept', 'application/vnd.api+json');

        $res = $this->subject->handlePageError($req, 'fooMessage');

        self::assertInstanceOf(JsonResponse::class, $res);
    }

    #[Test]
    public function itReturnsStaticContentIfExists(): void
    {
        $mockComponent = $this->createMock(StaticDocumentComponent::class);

        $content = uniqid('content_');

        $mockComponent->expects(self::once())->method('getOutput')->willReturn($content);
        GeneralUtility::addInstance(StaticDocumentComponent::class, $mockComponent);

        $resp = $this->subject->handlePageError(new ServerRequest(), uniqid('message_'));

        self::assertEquals($content, $resp->getBody()->getContents());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getMockBuilder(PageContentErrorHandler::class)->addMethods(
            []
        )->disableOriginalConstructor()
            ->getMock();
        $reflection = new ReflectionClass(PageContentErrorHandler::class);
        $reflection_property = $reflection->getProperty('statusCode');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->subject, 400);
    }
}
