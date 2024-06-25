<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationService
{
    /**
     * @var string
     */
    final public const EXCEPTION_HANDLER_COMPONENTS = 'exceptionHandlerComponents';

    /**
     * @var string
     */
    final public const TARGET_DIRECTORY = '/tx_nxerrorhandler/';

    /**
     * @return mixed
     */
    protected static function getExtensionConfiguration(string $path)
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nxerrorhandler', $path);
    }

    public static function getExceptionHandlerComponents(): array
    {
        $components = self::getExtensionConfiguration(self::EXCEPTION_HANDLER_COMPONENTS);

        return empty($components) ? [] : (array) $components;
    }

    public static function getErrorDocumentDirectory(): string
    {
        return Environment::getPublicPath() . '/typo3temp/assets' . self::TARGET_DIRECTORY;
    }

    public static function getErrorDocumentFilePath(): string
    {
        return ConfigurationService::getErrorDocumentDirectory() . '%s/%s-%s-%s.html';
    }
}
