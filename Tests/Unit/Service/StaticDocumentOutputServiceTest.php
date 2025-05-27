<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\Service;

use Netlogix\Nxerrorhandler\Service\StaticDocumentOutputService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StaticDocumentOutputServiceTest extends UnitTestCase
{
    #[Test]
    public function itAddsCurrentUrlToErrorDocument(): void
    {
        $url = uniqid('https://www.example.com/');

        $subject = $this->getAccessibleMock(StaticDocumentOutputService::class, ['getErrorDocumentFromFile']);
        $subject->method('getErrorDocumentFromFile')->willReturn('###CURRENT_URL###');

        $res = $subject->getOutput(0, new ServerRequest($url), '');

        $this->assertEquals($url, $res);
    }

    #[Test]
    public function itAddsReasonTextToErrorDocument(): void
    {
        $reason = uniqid();

        $subject = $this->getAccessibleMock(StaticDocumentOutputService::class, ['getErrorDocumentFromFile']);
        $subject->method('getErrorDocumentFromFile')->willReturn('###REASON###');

        $res = $subject->getOutput(0, new ServerRequest('https://www.example.com'), $reason);

        $this->assertEquals($reason, $res);
    }
}
