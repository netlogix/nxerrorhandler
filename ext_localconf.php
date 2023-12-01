<?php

defined('TYPO3') or die();

call_user_func(function () {
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \Netlogix\Nxerrorhandler\ErrorHandler\FatalErrorHandler::class
    )->initialize();
});
