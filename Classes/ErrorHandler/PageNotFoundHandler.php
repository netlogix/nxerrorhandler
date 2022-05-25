<?php

namespace Netlogix\Nxerrorhandler\ErrorHandler;

use Netlogix\Nxerrorhandler\ErrorHandler\Component\StaticDocumentComponent;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Manage static error pages
 */
class PageNotFoundHandler
{

    public function handlePageNotFound(array $params, ErrorController $errorController): string
    {
        $request = $this->getCurrentRequest();
        $staticDocumentComponent = GeneralUtility::makeInstance(StaticDocumentComponent::class);
        $errorDocument = $staticDocumentComponent->getOutput(404, $request, $params['reasonText']);
        if ($errorDocument === '') {
            $errorDocument = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                'Page Not Found',
                'The page did not exist or was inaccessible.' . ($params['reasonText'] ? ' Reason: ' . $params['reasonText'] : '')
            );
        }
        return $errorDocument;
    }

    private function getCurrentRequest(): ServerRequestInterface
    {
        if ($GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            return $GLOBALS['TYPO3_REQUEST'];
        }
        return \TYPO3\CMS\Core\Http\ServerRequestFactory::fromGlobals();
    }

}

