<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\Service;

use Netlogix\Nxerrorhandler\ErrorHandler\Component\ExtbaseArgumentsToBadRequestComponent;
use Netlogix\Nxerrorhandler\Service\SentryConfigurationService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

class SentryConfigurationServiceTest extends FunctionalTestCase
{

    protected $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxerrorhandler' => [
                'reportDatabaseConnectionErrors' => true,
                'messageBlacklistRegex' => '.*',
                'exceptionHandlerComponents' => [
                    ExtbaseArgumentsToBadRequestComponent::class,
                ],
                'sentry' => ['dsn' => 'sentry.example.invalid/12345'],
                'skipForStatusCodes' => [
                    '404',
                ],
            ],
        ]
    ];

    /**
     * @test
     * @return void
     */
    public function itCanGetSentryDsnFromConfiguration()
    {
        // this is the one set above
        $dsn = 'sentry.example.invalid/12345';

        self::assertEquals($dsn, SentryConfigurationService::getDsn());
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetEnvironmentFromApplicationContext()
    {
        self::assertEquals(SentryConfigurationService::getEnvironment(), 'Testing');
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetProjectRootPath()
    {
        self::assertNotEmpty(SentryConfigurationService::getProjectRoot());
    }

}
