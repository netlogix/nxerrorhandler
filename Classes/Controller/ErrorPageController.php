<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Controller;

use Netlogix\Nxerrorhandler\Service\StaticDocumentOutputService;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[Autoconfigure(public: true)]
readonly class ErrorPageController extends \TYPO3\CMS\Core\Controller\ErrorPageController
{
    public function errorAction(
        string $title,
        string $message,
        int $errorCode = 0,
        ?int $httpStatusCode = null,
    ): string {
        try {
            $output = GeneralUtility::makeInstance(StaticDocumentOutputService::class)->getOutput(
                $httpStatusCode ?? 500,
                $this->getRequest(),
                $message,
            );
        } catch (\Throwable) {
            $output = '';
        }

        if ($output === '') {
            return parent::errorAction($title, $message, $errorCode, $httpStatusCode);
        }

        return $output;
    }

    private function getRequest(): ServerRequestInterface
    {
        if (array_key_exists('TYPO3_REQUEST', $GLOBALS)) {
            return $GLOBALS['TYPO3_REQUEST'];
        }
        return ServerRequestFactory::fromGlobals();
    }
}
