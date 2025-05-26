<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\Error;

use Netlogix\Nxerrorhandler\Error\PageContentErrorHandler;
use Netlogix\Nxerrorhandler\Service\StaticDocumentOutputService;
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

        $this->assertInstanceOf(JsonResponse::class, $res);
    }

    #[Test]
    public function itReturnsJsonResponseForJsonApiRequest(): void
    {
        $req = new ServerRequest();
        $req = $req->withHeader('Accept', 'application/vnd.api+json');

        $res = $this->subject->handlePageError($req, 'fooMessage');

        $this->assertInstanceOf(JsonResponse::class, $res);
    }

    #[Test]
    public function itReturnsStaticContentIfExists(): void
    {
        $mockComponent = $this->createMock(StaticDocumentOutputService::class);

        $content = uniqid('content_');

        $mockComponent->expects($this->once())->method('getOutput')->willReturn($content);
        GeneralUtility::addInstance(StaticDocumentOutputService::class, $mockComponent);

        $resp = $this->subject->handlePageError(new ServerRequest(), uniqid('message_'));

        $this->assertSame($content, $resp->getBody()->getContents());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getMockBuilder(PageContentErrorHandler::class)
            ->addMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $reflection = new ReflectionClass(PageContentErrorHandler::class);
        $reflection_property = $reflection->getProperty('statusCode');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->subject, 400);
    }
}
