<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\Error;

use Netlogix\Nxerrorhandler\Error\PageContentErrorHandler;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;

class PageContentErrorHandlerTest extends UnitTestCase
{

    /**
     * @var PageContentErrorHandler|MockObject
     */
    private $subject;

    /**
     * @test
     * @return void
     */
    public function itReturnsJsonResponseForJsonRequest()
    {
        $req = new ServerRequest();
        $req = $req->withHeader('Accept', 'application/json');

        $res = $this->subject->handlePageError($req, 'fooMessage');

        self::assertInstanceOf(JsonResponse::class, $res);
    }

    /**
     * @test
     * @return void
     */
    public function itReturnsJsonResponseForJsonApiRequest()
    {
        $req = new ServerRequest();
        $req = $req->withHeader('Accept', 'application/vnd.api+json');

        $res = $this->subject->handlePageError($req, 'fooMessage');

        self::assertInstanceOf(JsonResponse::class, $res);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getMockBuilder(PageContentErrorHandler::class)->addMethods([]
        )->disableOriginalConstructor()->getMock();
        $reflection = new ReflectionClass(PageContentErrorHandler::class);
        $reflection_property = $reflection->getProperty('statusCode');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->subject, 400);
    }
}
