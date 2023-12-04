<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Functional\ErrorHandler;

use Netlogix\Nxerrorhandler\ErrorHandler\GeneralExceptionHandler;
use Netlogix\Nxerrorhandler\Tests\Unit\Fixtures\ComponentFixture;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class GeneralExceptionHandlerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/nxerrorhandler'];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'nxerrorhandler' => [],
        ],
    ];

    protected function tearDown(): void
    {
        restore_exception_handler();

        parent::tearDown();
    }

    #[Test]
    public function itRendersContentFromErrorDocumentForException(): void
    {
        $message = uniqid('message_');

        $this->expectOutputRegex('/' . $message . '/i');

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nxerrorhandler']['exceptionHandlerComponents'] = [
            ComponentFixture::class,
        ];
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://www.example.com/');

        $subject = GeneralUtility::makeInstance(GeneralExceptionHandler::class);

        $ex = new PageNotFoundException($message, time());

        $subject->echoExceptionWeb($ex);
    }
}
