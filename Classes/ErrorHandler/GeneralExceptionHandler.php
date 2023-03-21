<?php
namespace Netlogix\Nxerrorhandler\ErrorHandler;

use Netlogix\Nxerrorhandler\ErrorHandler\Component\AbstractComponent;
use Netlogix\Nxerrorhandler\Exception\Exception;
use Netlogix\Nxerrorhandler\Service\ConfigurationService;
use Throwable;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Error\ProductionExceptionHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Manage static error pages
 */
class GeneralExceptionHandler extends ProductionExceptionHandler
{

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var AbstractComponent[]
     */
    protected array $components = [];

    /**
     * @inheritdoc
     */
    public function echoExceptionWeb(Throwable $exception)
    {
        try {
            $this->initialize();

            $effectiveStatusCode = $this->sendStatusCodes($exception);

            $this->logError($exception, self::CONTEXT_WEB, $effectiveStatusCode);


            $message = $this->getMessage($exception);

            $errorDocument = $this->getErrorDocument($effectiveStatusCode, $message, $exception);

            echo $errorDocument;
        } catch (Throwable $t) {
            $this->writeLogEntries($t, self::CONTEXT_WEB);
            parent::echoExceptionWeb($exception);
        }
    }

    public function echoExceptionCLI(Throwable $exception)
    {
        try {
            $this->logError($exception, self::CONTEXT_CLI, 500);
            exit(1);
        } catch (Throwable $t) {
            $this->writeLogEntries($t, self::CONTEXT_CLI);
            parent::echoExceptionCLI($exception);
        }
    }

    public function logError(Throwable $exception, string $context, int $statusCode = 500)
    {
        try {
            $this->initialize();
        } catch (Throwable $t) {
            $this->writeLogEntries($exception, $context);
            return;
        }

        $suppressDefaultLogEntries = false;
        foreach ($this->components as $component) {
            $suppressDefaultLogEntries = $suppressDefaultLogEntries || $component->logError(
                    $exception,
                    $context,
                    $statusCode
                );
        }
        if (!$suppressDefaultLogEntries) {
            $this->writeLogEntries($exception, $context);
        }
    }

    protected function initialize()
    {
        if (empty($this->components)) {
            if (!empty(ConfigurationService::getExceptionHandlerComponents())) {
                foreach (ConfigurationService::getExceptionHandlerComponents() as $componentClass) {
                    if (!class_exists($componentClass)) {
                        throw new Exception(
                            'Error handler component ' . $componentClass . ' does not exist', 1395074867
                        );
                    }
                    $this->components[] = GeneralUtility::makeInstance($componentClass);
                }
            } else {
                throw new Exception('No error handler components registered', 1395075649);
            }
        }
    }

    protected function getStatusHeaders(Throwable $exception): array
    {
        $statusHeaders = [];
        foreach ($this->components as $component) {
            $statusHeaders = array_merge($statusHeaders, $component->getHttpHeaders($exception));
        }

        return $statusHeaders;
    }

    protected function parseStatusHeadersForCode(array $headers): int
    {
        $statusCode = 500;
        foreach ($headers as $header) {
            if (preg_match('~^HTTP/1.[01] (\d{3}) ~', $header, $matches) === 1) {
                $statusCode = (int)$matches[1];
            }
        }

        return $statusCode;
    }

    private function getServerRequest(): ServerRequest
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    /**
     * @param Throwable $exception
     * @return int
     */
    protected function sendStatusCodes(Throwable $exception): int
    {
        $statusHeaders = $this->getStatusHeaders($exception);
        $effectiveStatusCode = 500;
        if (!empty($statusHeaders)) {
            $effectiveStatusCode = $this->parseStatusHeadersForCode($statusHeaders);
            if (!headers_sent()) {
                foreach ($statusHeaders as $header) {
                    header($header);
                }
            }
        } else {
            if (method_exists($exception, 'getStatusHeaders')) {
                $effectiveStatusCode = $this->parseStatusHeadersForCode($exception->getStatusHeaders());
            }
            $this->sendStatusHeaders($exception);
        }
        return $effectiveStatusCode;
    }

    /**
     * @param int $effectiveStatusCode
     * @param string $message
     * @param Throwable $exception
     * @return string
     */
    protected function getErrorDocument(
        int $effectiveStatusCode,
        string $message,
        Throwable $exception
    ): string {
        $request = $this->getServerRequest();

        $errorDocument = '';
        foreach ($this->components as $component) {
            $errorDocument = $component->getOutput($effectiveStatusCode, $request, $message);
            if ($errorDocument !== '') {
                break;
            }
        }

        if ($errorDocument === '') {
            $errorDocument = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                $this->getTitle($exception),
                $this->getMessage($exception)
            );
        }
        return $errorDocument;
    }
}
