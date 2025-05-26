<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Command;

use RuntimeException;
use InvalidArgumentException;
use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[
    AsCommand(
        name: 'nxerrorhandler:generateErrorPages',
        description: 'Generates static error pages based on site configuration.',
    ),
]
class GenerateErrorPagesCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private int|bool|null $deploymentDate = null;

    protected function configure()
    {
        $this->addArgument(
            'forceGeneration',
            InputArgument::OPTIONAL,
            'Force regeneration of all static error pages',
            false,
        );
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @see InputInterface::bind()
     * @see InputInterface::validate()
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->initializeTargetDirectory();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = GeneralUtility::makeInstance(SiteFinder::class);
        $forceGeneration = (bool) $input->getArgument('forceGeneration');

        foreach ($finder->getAllSites() as $site) {
            $errorHandlingConfiguration = [];
            foreach ($site->getConfiguration()['errorHandling'] ?? [] as $configuration) {
                $code = $configuration['errorCode'];
                unset($configuration['errorCode']);
                $errorHandlingConfiguration[(int) $code] = $configuration;
            }

            if ($errorHandlingConfiguration === []) {
                continue;
            }

            GeneralUtility::setIndpEnv('TYPO3_REQUEST_URL', (string) $site->getBase());
            foreach ($errorHandlingConfiguration as $errorCode => $configuration) {
                foreach ($site->getLanguages() as $language) {
                    if ($language->isEnabled() === false) {
                        continue;
                    }

                    if (
                        !$forceGeneration &&
                        !$this->checkIfErrorPageNeedsRegeneration($errorCode, $site, $language)
                    ) {
                        continue;
                    }

                    $request = ServerRequestFactory::fromGlobals();
                    $request
                        ->withUri($site->getBase())
                        ->withAttribute('site', $site)
                        ->withAttribute('language', $language);

                    $resolvedUrl = $this->resolveUrl($request, $configuration['errorContentSource']);
                    $content = GeneralUtility::getUrl($resolvedUrl);
                    if ($content === false || $content === '') {
                        $this->logger->error(
                            sprintf(
                                'Could not retrieve [%1$s] error page on rootPageId [%2$d] with language [%3$d]. Requested url was "%4$s".',
                                $errorCode,
                                $site->getRootPageId(),
                                $language->getLanguageId(),
                                $resolvedUrl,
                            ),
                        );

                        continue;
                    }

                    $this->saveErrorPage($errorCode, $site, $language, $content);
                }
            }
        }

        return 0;
    }

    private function initializeTargetDirectory(): void
    {
        if (!file_exists(ConfigurationService::getErrorDocumentDirectory())) {
            GeneralUtility::mkdir(ConfigurationService::getErrorDocumentDirectory());
        } elseif (!is_dir(ConfigurationService::getErrorDocumentDirectory())) {
            throw new RuntimeException('Target directory is not a directory', 1394124945);
        }
    }

    protected function saveErrorPage(int $errorCode, Site $site, SiteLanguage $language, string $content): void
    {
        if (!is_dir($this->getErrorDocumentPath($errorCode))) {
            GeneralUtility::mkdir($this->getErrorDocumentPath($errorCode));
        }

        $file = $this->getErrorDocumentFilePath($errorCode, $site, $language);
        GeneralUtility::writeFile($file, $content);
    }

    private function checkIfErrorPageNeedsRegeneration(int $errorCode, Site $site, SiteLanguage $language): bool
    {
        $file = $this->getErrorDocumentFilePath($errorCode, $site, $language);

        return !file_exists($file) || filemtime($file) < $this->getDeploymentDate();
    }

    private function getErrorDocumentFilePath(int $errorCode, Site $site, SiteLanguage $language): string
    {
        return sprintf(
            ConfigurationService::getErrorDocumentFilePath(),
            $errorCode,
            $site->getRootPageId(),
            $language->getLanguageId(),
        );
    }

    private function getErrorDocumentPath(int $errorCode): string
    {
        return sprintf(ConfigurationService::getErrorDocumentDirectory() . '%s', $errorCode);
    }

    private function getDeploymentDate(): int
    {
        if ($this->deploymentDate === null) {
            $this->deploymentDate = filectime(Environment::getProjectPath());
        }

        return $this->deploymentDate;
    }

    /**
     * Resolve the URL (currently only page and external URL are supported)
     */
    private function resolveUrl(ServerRequestInterface $request, string $typoLinkUrl): string
    {
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        $urlParams = $linkService->resolve($typoLinkUrl);
        if ($urlParams['type'] !== 'page' && $urlParams['type'] !== 'url') {
            throw new InvalidArgumentException(
                'PageContentErrorHandler can only handle TYPO3 urls of types "page" or "url"',
                1588933778,
            );
        }

        if ($urlParams['type'] === 'url') {
            return $urlParams['url'];
        }

        try {
            // Get the site related to the configured error page
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId((int) $urlParams['pageuid']);
        } catch (SiteNotFoundException) {
            // Fall back to current request for the site
            $site = $request->getAttribute('site', null);
        }

        /** @var SiteLanguage $requestLanguage */
        $requestLanguage = $request->getAttribute('language', null);
        // Try to get the current request language from the site that was found above
        if ($requestLanguage instanceof SiteLanguage) {
            try {
                $language = $site->getLanguageById($requestLanguage->getLanguageId());
            } catch (InvalidArgumentException) {
                $language = $site->getDefaultLanguage();
            }
        } else {
            $language = $site->getDefaultLanguage();
        }

        // Build Url
        $uri = $site->getRouter()->generateUri((int) $urlParams['pageuid'], [
            '_language' => $language,
        ]);

        // Fallback to the current URL if the site is not having a proper scheme and host
        $currentUri = $request->getUri();
        if ($uri->getScheme() === '') {
            $uri = $uri->withScheme($currentUri->getScheme());
        }

        if ($uri->getUserInfo() === '') {
            $uri = $uri->withUserInfo($currentUri->getUserInfo());
        }

        if ($uri->getHost() === '') {
            $uri = $uri->withHost($currentUri->getHost());
        }

        if ($uri->getPort() === null) {
            $uri = $uri->withPort($currentUri->getPort());
        }

        return (string) $uri;
    }
}
