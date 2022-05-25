<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\Service;

use Netlogix\Nxerrorhandler\Service\SentryConfigurationService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class SentryConfigurationServiceTest extends UnitTestCase
{


    /**
     * @test
     * @return void
     */
    public function itCanGetSentryDsnFromEnv()
    {
        $dsn = uniqid() . '.example.com/foo';

        putenv('SENTRY_DSN=' . $dsn);

        self::assertEquals($dsn, SentryConfigurationService::getDsn());
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetEnvironmentFromEnv()
    {
        $env = uniqid() . '.example.com/foo';

        putenv('SENTRY_ENVIRONMENT=' . $env);

        self::assertEquals(SentryConfigurationService::getEnvironment(), $env);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetReleaseFromFallback()
    {
        self::assertEquals('unknown', SentryConfigurationService::getRelease());
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetReleaseFromEnv()
    {
        $release = rand(1, 9999);

        putenv('SENTRY_RELEASE=' . $release);

        self::assertEquals(SentryConfigurationService::getRelease(), $release);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetReleaseFromProjectPath()
    {
        self::markTestSkipped('cannot test deployment path');
    }


}
