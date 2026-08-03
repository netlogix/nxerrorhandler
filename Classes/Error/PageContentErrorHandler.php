<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Error;

use Override;
use Netlogix\Nxerrorhandler\Service\StaticDocumentOutputService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageContentErrorHandler as T3PageContentErrorHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageContentErrorHandler extends T3PageContentErrorHandler
{
    #[Override]
    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = [],
    ): ResponseInterface {
        if ($this->isJson($request)) {
            return new JsonResponse([], $this->statusCode);
        }

        $output = GeneralUtility::makeInstance(StaticDocumentOutputService::class)->getOutput(
            $this->statusCode,
            $request,
            $message,
        );

        if ($output === '') {
            return parent::handlePageError($request, $message, $reasons);
        }

        return new HtmlResponse($output, $this->statusCode);
    }

    private function isJson(ServerRequestInterface $request): bool
    {
        $accept = GeneralUtility::trimExplode(',', $request->getHeaderLine('Accept'))[0] ?? '';

        return in_array($accept, ['application/json', 'application/vnd.api+json'], true);
    }
}
