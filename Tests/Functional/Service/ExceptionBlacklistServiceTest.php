<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\Service;

use Exception;
use Netlogix\Nxerrorhandler\ErrorHandler\Component\ExtbaseArgumentsToBadRequestComponent;
use Netlogix\Nxerrorhandler\Service\ExceptionBlacklistService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExceptionBlacklistServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxerrorhandler' => [
                'reportDatabaseConnectionErrors' => true,
                'messageBlacklistRegex' => '/eatMe/',
                'exceptionHandlerComponents' => [ExtbaseArgumentsToBadRequestComponent::class],
            ],
        ],
    ];

    /**
     * @test
     */
    public function itShouldHandleNonBlacklistedException()
    {
        $ex = new Exception(uniqid(), 1653485364);

        self::assertTrue(ExceptionBlacklistService::shouldHandleException($ex));
    }

    /**
     * @test
     */
    public function itShouldNotHandleBlacklistedException()
    {
        $ex = new Exception(uniqid() . ' eatMe ' . uniqid(), 1653485364);

        self::assertFalse(ExceptionBlacklistService::shouldHandleException($ex));
    }
}
