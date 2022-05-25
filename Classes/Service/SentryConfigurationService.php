<?php declare(strict_types=1);
namespace Netlogix\Nxerrorhandler\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SentryConfigurationService
{
    const DSN = 'dsn';

    /**
     * @param $path
     * @return mixed
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    protected static function getExtensionConfiguration(string $path)
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nxerrorhandler', 'sentry/' . $path);
    }

    public static function getDsn(): ?string
    {
        return getenv('SENTRY_DSN') ?: self::getExtensionConfiguration(self::DSN);
    }

    public static function getEnvironment(): string
    {
        return getenv('SENTRY_ENVIRONMENT') ?: self::getNormalizedApplicationContext();
    }

    public static function getRelease(): ?string
    {
        return getenv('SENTRY_RELEASE') ?: self::getReleaseFromDeploymentVersion();
    }

    protected static function getNormalizedApplicationContext()
    {
        return preg_replace("/[^a-zA-Z0-9]/", "-", Environment::getContext());
    }

    public static function getProjectRoot()
    {
        if (Environment::isComposerMode()) {
            return dirname(getcwd());
        } else {
            return getcwd();
        }
    }

    /**
     * Extract deployment version from path
     */
    protected static function getReleaseFromDeploymentVersion(): string
    {
        if (preg_match('~/releases/(\d{14})/Web$~', Environment::getProjectPath(), $matches) === 1) {
            return $matches[1];
        }

        return 'unknown';
    }
}
