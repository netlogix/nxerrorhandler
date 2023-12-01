<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\ErrorHandler;

use ErrorException;
use TYPO3\CMS\Core\Error\ExceptionHandlerInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle fatal php errors
 */
class FatalErrorHandler implements SingletonInterface
{
    /**
     * Reserved memory to be freed when handling a fatal error. Necessary to have some memory in case of memory limit
     * errors.
     *
     * @var string
     */
    protected $reservedMemory;

    /**
     * Initialize error handler
     *
     * @param int $reservedMemorySize Size of memory to reserve for handling errors, in KiB. Necessary for memory limit exceeded.
     */
    public function initialize($reservedMemorySize = 50)
    {
        if ($this->reservedMemory === null) {
            $this->reservedMemory = str_repeat('x', 1024 * $reservedMemorySize);
            $this->registerShutdownFunction();
        }
    }

    /**
     * Check if an error occurred and pass it on to the exception handler
     */
    public function handleFatalError()
    {
        // always free reserved memory even if this handler does nothing
        unset($this->reservedMemory);

        $lastError = $this->getLastError();

        if ($lastError === null) {
            return;
        }

        if ($this->getExceptionHandlerClassName() === null) {
            return;
        }

        $errors = E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_STRICT;

        if ($lastError['type'] & $errors) {
            $e = new ErrorException(
                @$lastError['message'],
                @$lastError['type'],
                @$lastError['type'],
                @$lastError['file'],
                @$lastError['line']
            );
            $exceptionHandler = GeneralUtility::makeInstance($this->getExceptionHandlerClassName());
            assert($exceptionHandler instanceof ExceptionHandlerInterface);
            $exceptionHandler->handleException($e);
        }
    }

    /**
     * Wrapper for better testability
     */
    protected function getLastError(): ?array
    {
        return error_get_last();
    }

    protected function getExceptionHandlerClassName(): ?string
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] ?? null;
    }

    /**
     * Register the error handler as shutdown function
     */
    protected function registerShutdownFunction()
    {
        register_shutdown_function([$this, 'handleFatalError']);
    }
}
