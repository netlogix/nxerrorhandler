<?php

namespace Netlogix\Nxerrorhandler\ErrorHandler;

use Exception;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;

class ContentObjectExceptionHandler extends ProductionExceptionHandler
{

    /**
     * Handles exceptions thrown during rendering of content objects
     * The handler can decide whether to re-throw the exception or
     * return a nice error message for production context.
     */
    public function handle(
        Exception $exception,
        AbstractContentObject $contentObject = null,
        $contentObjectConfiguration = []
    ): string {
        if ($this->isCli()) {
            throw $exception;
        }
        $exceptionHandler = GeneralUtility::makeInstance(GeneralExceptionHandler::class);
        $exceptionHandler->handleException($exception);
        $this->exit();
        return '';
    }

    /**
     * This is a wrapper for better testability
     *
     * @return bool
     */
    protected function isCli(): bool
    {
        return Environment::isCli();
    }

    /**
     * This is a wrapper for better testability
     *
     * @return void
     */
    protected function exit(): void
    {
        exit(1);
    }

}
