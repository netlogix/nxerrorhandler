<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\Service;

use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConfigurationServiceTest extends UnitTestCase
{
    #[Test]
    public function itCanGetErrorDocumentDirectory(): void
    {
        $this->assertNotEmpty(ConfigurationService::getErrorDocumentDirectory());
    }

    #[Test]
    public function itCanGetErrorDocumentFilePath(): void
    {
        $this->assertNotEmpty(ConfigurationService::getErrorDocumentFilePath());
    }
}
