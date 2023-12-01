<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\Service;

use Netlogix\Nxerrorhandler\ErrorHandler\Component\ExtbaseArgumentsToBadRequestComponent;
use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ConfigurationServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxerrorhandler' => [
                'exceptionHandlerComponents' => [ExtbaseArgumentsToBadRequestComponent::class],
            ],
        ],
    ];

    /**
     * @test
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
