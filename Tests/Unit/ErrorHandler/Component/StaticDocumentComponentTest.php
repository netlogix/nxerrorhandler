<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\ErrorHandler\Component;

use Netlogix\Nxerrorhandler\ErrorHandler\Component\StaticDocumentComponent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StaticDocumentComponentTest extends UnitTestCase
{
    #[Test]
    public function itAddsCurrentUrlToErrorDocument(): void
    {
        $url = uniqid('https://www.example.com/');

        $subject = $this->getAccessibleMock(StaticDocumentComponent::class, ['getErrorDocumentFromFile']);
        $subject->method('getErrorDocumentFromFile')
            ->willReturn('###CURRENT_URL###');

        $res = $subject->getOutput(0, new ServerRequest($url), '');

        self::assertEquals($url, $res);
    }

    #[Test]
    public function itAddsReasonTextToErrorDocument(): void
    {
        $reason = uniqid();

        $subject = $this->getAccessibleMock(StaticDocumentComponent::class, ['getErrorDocumentFromFile']);
        $subject->method('getErrorDocumentFromFile')
            ->willReturn('###REASON###');

        $res = $subject->getOutput(0, new ServerRequest('https://www.example.com'), $reason);

        self::assertEquals($reason, $res);
    }
}
