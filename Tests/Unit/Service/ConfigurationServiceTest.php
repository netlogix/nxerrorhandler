<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\Service;

use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ConfigurationServiceTest extends UnitTestCase
{

    /**
     * @test
     * @return void
     */
    public function itCanGetErrorDocumentDirectory()
    {
        self::assertNotEmpty(ConfigurationService::getErrorDocumentDirectory());
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetErrorDocumentFilePath()
    {
        self::assertNotEmpty(ConfigurationService::getErrorDocumentFilePath());
    }
}
