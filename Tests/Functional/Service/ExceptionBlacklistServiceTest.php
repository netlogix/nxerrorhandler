<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\Service;

use Exception;
use Netlogix\Nxerrorhandler\ErrorHandler\Component\ExtbaseArgumentsToBadRequestComponent;
use Netlogix\Nxerrorhandler\Service\ExceptionBlacklistService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

class ExceptionBlacklistServiceTest extends FunctionalTestCase
{

    protected $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxerrorhandler' => [
                'reportDatabaseConnectionErrors' => true,
                'messageBlacklistRegex' => '/eatMe/',
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
    public function itShouldHandleNonBlacklistedException()
    {
        $ex = new Exception(uniqid(), 1653485364);

        self::assertTrue(ExceptionBlacklistService::shouldHandleException($ex));
    }

    /**
     * @test
     * @return void
     */
    public function itShouldNotHandleBlacklistedException()
    {
        $ex = new Exception(uniqid() . ' eatMe ' . uniqid(), 1653485364);

        self::assertFalse(ExceptionBlacklistService::shouldHandleException($ex));
    }


    /**
     * @test
     * @return void
     */
    public function itShouldHandleNonBlacklistedStatusCode()
    {
        $code = 419;

        self::assertTrue(ExceptionBlacklistService::shouldHandleStatusCode($code));
    }

    /**
     * @test
     * @return void
     */
    public function itShouldNotHandleBlacklistedStatusCode()
    {
        self::assertFalse(ExceptionBlacklistService::shouldHandleStatusCode(404));
    }


}
