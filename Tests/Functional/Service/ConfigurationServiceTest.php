<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\Service;

use Netlogix\Nxerrorhandler\ErrorHandler\Component\ExtbaseArgumentsToBadRequestComponent;
use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

class ConfigurationServiceTest extends FunctionalTestCase
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
    public function itCanGetMessageBlacklistRegex()
    {
        self::assertEquals(ConfigurationService::getMessageBlacklistRegex(), '.*');
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetReportDatabaseConnectionErrors()
    {
        self::assertTrue(ConfigurationService::reportDatabaseConnectionErrors());
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetSkipForStatusCodes()
    {
        self::assertNotEmpty(ConfigurationService::getSkipForStatusCodes());
        self::assertEquals('404', ConfigurationService::getSkipForStatusCodes()[0]);
    }

    /**
     * @test
     * @return void
     */
    public function itCanGetExceptionHandlerComponents()
    {
        self::assertNotEmpty(ConfigurationService::getExceptionHandlerComponents());
        self::assertEquals(
            ExtbaseArgumentsToBadRequestComponent::class,
            ConfigurationService::getExceptionHandlerComponents()[0]
        );
    }

}
