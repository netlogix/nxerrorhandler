<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Controller\ErrorPageController;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][ErrorPageController::class] = [
    'className' => \Netlogix\Nxerrorhandler\Controller\ErrorPageController::class,
];
