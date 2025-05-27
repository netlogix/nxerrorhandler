<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Service;

use TYPO3\CMS\Core\Core\Environment;

class ConfigurationService
{
    final protected const string TARGET_DIRECTORY = '/tx_nxerrorhandler/';

    public static function getErrorDocumentDirectory(): string
    {
        return Environment::getPublicPath() . '/typo3temp/assets' . self::TARGET_DIRECTORY;
    }

    public static function getErrorDocumentFilePath(): string
    {
        return ConfigurationService::getErrorDocumentDirectory() . '%s/%s-%s.html';
    }
}
