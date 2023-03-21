<?php declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Service;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationService
{

    const MESSAGE_BLACKLIST_REGEX = 'messageBlacklistRegex';

    const REPORT_DATABASE_CONNECTION_ERRORS = 'reportDatabaseConnectionErrors';

    const EXCEPTION_HANDLER_COMPONENTS = 'exceptionHandlerComponents';

    const TARGET_DIRECTORY = '/tx_nxerrorhandler/';

    /**
     * @param $path
     * @return mixed
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    protected static function getExtensionConfiguration(string $path)
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nxerrorhandler', $path);
    }

    public static function getMessageBlacklistRegex(): ?string
    {
        return self::getExtensionConfiguration(self::MESSAGE_BLACKLIST_REGEX);
    }

    public static function reportDatabaseConnectionErrors(): bool
    {
        return (bool)self::getExtensionConfiguration(self::REPORT_DATABASE_CONNECTION_ERRORS);
    }

    public static function getExceptionHandlerComponents(): array
    {
        $components = self::getExtensionConfiguration(self::EXCEPTION_HANDLER_COMPONENTS);
        return !empty($components) ? (array)$components : [];
    }

    public static function getErrorDocumentDirectory(): string
    {
        return Environment::getVarPath() . self::TARGET_DIRECTORY;
    }

    public static function getErrorDocumentFilePath(): string
    {
        return ConfigurationService::getErrorDocumentDirectory() . '%s/%s-%s-%s.html';
    }

}
