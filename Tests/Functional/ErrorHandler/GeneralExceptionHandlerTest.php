<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\ErrorHandler;

use Netlogix\Nxerrorhandler\ErrorHandler\GeneralExceptionHandler;
use Netlogix\Nxerrorhandler\Tests\Unit\Fixtures\ComponentFixture;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GeneralExceptionHandlerTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxerrorhandler' => [

            ],
        ]
    ];

    /**
     * @test
     *
     * @return void
     */
    public function itRendersContentFromErrorDocumentForException()
    {
        $message = uniqid('message_');

        $this->expectOutputRegex('/' . $message . '/i');

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxerrorhandler']['exceptionHandlerComponents'] = [ComponentFixture::class];
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://www.example.com/');

        $subject = GeneralUtility::makeInstance(GeneralExceptionHandler::class);

        $ex = new PageNotFoundException($message, time());

        $subject->echoExceptionWeb($ex);
    }


}
