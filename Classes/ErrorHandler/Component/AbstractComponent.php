<?php
namespace Netlogix\Nxerrorhandler\ErrorHandler\Component;

use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractComponent
{

    /**
     * Define HTTP headers to be sent to the client.
     */
    public function getHttpHeaders(\Throwable $exception): array
    {
        return [];
    }

    /**
     * Log the exception.
     *
     * If TRUE is returned the default logging of TYPO3 will not be done. Other components might still
     * log the error somehow
     */
    public function logError(\Throwable $exception, string $context, int $statusCode = 500): bool
    {
        return false;
    }

    /**
     * Provides an error message to be sent to the client. If an empty string is returned the next component will be tried.
     * If no component provides any output, a default message is used
     */
    public function getOutput(int $errorCode, ServerRequestInterface $request, string $reason = ''): string
    {
        return '';
    }

}
