<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\ErrorHandler\Component;


use Netlogix\Nxerrorhandler\ErrorHandler\Component\StaticDocumentComponent;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Http\ServerRequest;

class StaticDocumentComponentTest extends UnitTestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function itAddsCurrentUrlToErrorDocument()
    {
        $url = uniqid('https://www.example.com/');

        $subject = $this->getMockBuilder(StaticDocumentComponent::class)
            ->onlyMethods(['getErrorDocumentFromFile'])
            ->getMock();
        $subject->method('getErrorDocumentFromFile')->willReturn('###CURRENT_URL###');

        $res = $subject->getOutput(0, new ServerRequest($url), '');

        self::assertEquals($url, $res);
    }

    /**
     * @test
     *
     * @return void
     */
    public function itAddsReasonTextToErrorDocument()
    {
        $reason = uniqid();

        $subject = $this->getMockBuilder(StaticDocumentComponent::class)
            ->onlyMethods(['getErrorDocumentFromFile'])
            ->getMock();
        $subject->method('getErrorDocumentFromFile')->willReturn('###REASON###');

        $res = $subject->getOutput(0, new ServerRequest('https://www.example.com'), $reason);

        self::assertEquals($reason, $res);
    }

}
