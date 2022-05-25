<?php

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

		$currentUrl = $request ? (string)$request->getUri() : GeneralUtility::getIndpEnv('REQUEST_URI');
		$errorDocument = $this->getErrorDocumentFromFile($errorCode, $request);
		$errorDocument = str_replace('###CURRENT_URL###', htmlspecialchars($currentUrl), $errorDocument);
		$errorDocument = str_replace('###REASON###', htmlspecialchars($reason), $errorDocument);

		return $errorDocument;
	}

	protected function getErrorDocumentFromFile(int $errorCode, ServerRequestInterface $request): string
	{
		/** @var Site $site */
		$site = $request->getAttribute('site');
		/** @var SiteLanguage $siteLanguage */
		$siteLanguage = $request->getAttribute('language');
		if ($siteLanguage === null) {
			$siteLanguage = $site->getDefaultLanguage();
		}

		$errorHandlingConfiguration = [];
		foreach ($site->getConfiguration()['errorHandling'] ?? [] as $configuration) {
			$code = $configuration['errorCode'];
			unset($configuration['errorCode']);
			$errorHandlingConfiguration[(int)$code] = $configuration;
		}

		if (isset($errorHandlingConfiguration[$errorCode])) {
			$rootPageId = $site->getRootPageId();

			$errorDocumentPath = ConfigurationService::getErrorDocumentFilePath();
			$errorDocumentFileNames = [
				sprintf($errorDocumentPath, $errorCode, $siteLanguage->getBase()->getHost(), $rootPageId, $siteLanguage->getLanguageId()),
				sprintf($errorDocumentPath, $errorCode, $siteLanguage->getBase()->getHost(), $rootPageId, $site->getDefaultLanguage()->getLanguageId()),
				sprintf($errorDocumentPath, $errorCode, $site->getBase()->getHost(), $rootPageId, $site->getDefaultLanguage()->getLanguageId()),
			];
			foreach ($errorDocumentFileNames as $errorDocumentFileName) {
				if (file_exists($errorDocumentFileName) && is_readable($errorDocumentFileName)) {
					return GeneralUtility::getUrl($errorDocumentFileName);
				}
			}
		}
		return '';
	}
}
