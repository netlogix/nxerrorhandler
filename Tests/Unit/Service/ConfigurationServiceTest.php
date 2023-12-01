<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\Service;

use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ConfigurationServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function itCanGetErrorDocumentDirectory()
    {
        self::assertNotEmpty(ConfigurationService::getErrorDocumentDirectory());
    }

    /**
     * @test
     */
    public function itCanGetErrorDocumentFilePath()
    {
        self::assertNotEmpty(ConfigurationService::getErrorDocumentFilePath());
    }
}
