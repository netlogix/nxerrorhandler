<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\ErrorHandler\Component;

use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Manage static error pages
 */
class StaticDocumentComponent extends AbstractComponent
{
    public function getOutput(int $errorCode, ServerRequestInterface $request, string $reason = ''): string
    {
        $currentUrl = $request ? (string) $request->getUri() : GeneralUtility::getIndpEnv('REQUEST_URI');
        $errorDocument = $this->getErrorDocumentFromFile($errorCode, $request);
        $errorDocument = str_replace('###CURRENT_URL###', htmlspecialchars($currentUrl), $errorDocument);

        return str_replace('###REASON###', htmlspecialchars($reason), $errorDocument);
    }

    protected function getErrorDocumentFromFile(int $errorCode, ServerRequestInterface $request): string
    {
        /** @var Site $site */
        $site = $request->getAttribute('site');

        /** @var SiteLanguage $siteLanguage */
        $siteLanguage = $request->getAttribute('language') ?? $site->getDefaultLanguage();

        $errorHandlingConfiguration = [];
        foreach ($site->getConfiguration()['errorHandling'] ?? [] as $configuration) {
            $code = $configuration['errorCode'];
            unset($configuration['errorCode']);
            $errorHandlingConfiguration[(int) $code] = $configuration;
        }

        if (isset($errorHandlingConfiguration[$errorCode])) {
            $rootPageId = $site->getRootPageId();

            $errorDocumentPath = ConfigurationService::getErrorDocumentFilePath();
            $errorDocumentFileNames = [
                sprintf(
                    $errorDocumentPath,
                    $errorCode,
                    $rootPageId,
                    $siteLanguage->getLanguageId()
                ),
                sprintf(
                    $errorDocumentPath,
                    $errorCode,
                    $rootPageId,
                    $site->getDefaultLanguage()
                        ->getLanguageId()
                ),
                sprintf(
                    $errorDocumentPath,
                    $errorCode,
                    $rootPageId,
                    $site->getDefaultLanguage()
                        ->getLanguageId()
                ),
            ];
            foreach ($errorDocumentFileNames as $errorDocumentFileName) {
                $content = $this->getContentFromPath($errorDocumentFileName);
                if ($content) {
                    return $content;
                }
            }
        }

        return '';
    }

    protected function getContentFromPath(string $errorDocumentFileName): ?string
    {
        if (!file_exists($errorDocumentFileName)) {
            return null;
        }

        if (!is_readable($errorDocumentFileName)) {
            return null;
        }

        return GeneralUtility::getUrl($errorDocumentFileName);
    }
}
