<?php

namespace Netlogix\Nxerrorhandler\ErrorHandler\Component;

use Netlogix\Nxerrorhandler\Service\ExceptionBlacklistService;
use Netlogix\Nxerrorhandler\Service\SentryConfigurationService;
use Sentry\State\Scope;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use function Sentry\captureException;
use function Sentry\configureScope;
use function Sentry\init;

/**
 * Manage static error pages
 */
class SentryComponent extends AbstractComponent
{

    public function logError(\Throwable $exception, string $context, int $statusCode = 500): bool
    {

        $dsn = SentryConfigurationService::getDsn();
        if (empty($dsn) || !ExceptionBlacklistService::shouldHandleException($exception) || !ExceptionBlacklistService::shouldHandleStatusCode($statusCode)) {
            return false;
        }

        $options['dsn'] = $dsn;
        if (SentryConfigurationService::getRelease()) {
            $options['release'] = SentryConfigurationService::getRelease();
        }
        $options['environment'] = SentryConfigurationService::getEnvironment();
        $options['error_types'] = E_ALL ^ E_NOTICE;

        $httpConfiguration = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        if (!empty($httpConfiguration['proxy_host'])) {
            $options['http_proxy'] = $httpConfiguration['proxy_host'] . ':' . ($httpConfiguration['proxy_port'] ?: 8080);
        }
        if (!empty($httpConfiguration['proxy']['https']) || !empty($httpConfiguration['proxy']['http'])) {
            $options['http_proxy'] = $httpConfiguration['proxy']['https'] ?? $httpConfiguration['proxy']['http'];
        }

        init($options);

        $this->setUserContext();
        $this->setTagsContext();
        $this->setExtraContext($exception);

        captureException($exception);

        return true;
    }

    protected static function setUserContext(): void
    {
        configureScope(function (Scope $scope): void {
            $userContext = [];
            if (TYPO3_MODE === 'FE' && isset($GLOBALS['TSFE']->fe_user->user['username'])) {
                $userContext['session_data'] =$GLOBALS['TSFE']->fe_user->sesData ?? [];
                $userObject = $GLOBALS['TSFE']->fe_user->user;
            } elseif (isset($GLOBALS['BE_USER']->user['username'])) {
                $userObject = $GLOBALS['BE_USER']->user;
            }
            if (isset($userObject)) {
                $userContext['id'] = $userObject['uid'];
                $userContext['username'] = $userObject['username'];
                if (isset($userObject['email'])) {
                    $userContext['email'] = $userObject['email'];
                }
            }
            $scope->setUser($userContext);
        });
    }

    protected function setTagsContext(): void
    {
        configureScope(function (Scope $scope): void {
            $scope->setTags([
                'version' => 'v' . SentryConfigurationService::getRelease(),
                'php_version' => phpversion(),
                'typo3_version' => TYPO3_version,
                'typo3_mode' => TYPO3_MODE,
            ]);
        });
    }

    protected function setExtraContext(\Throwable $exception): void
    {
        configureScope(function (Scope $scope) use ($exception): void {
            $extras = $this->resolveControllerAndAction($exception->getTrace());
            if (TYPO3_MODE === 'FE') {
                $extras['pageId'] = $GLOBALS['TSFE']->id;
            } else if (TYPO3_MODE === 'BE' && GeneralUtility::_GP('id')) {
                $extras['pageId'] = GeneralUtility::_GP('id');
            }

            $scope->setExtras($extras);
        });
    }

    protected function resolveControllerAndAction(array $trace): array
    {
        foreach ($trace as $call) {
            if (isset($call['class']) && in_array(ActionController::class, class_parents($call['class']))) {
                if (isset($call['function']) && substr($call['function'], -6) === 'Action') {
                    return [
                        'controller' => $call['class'],
                        'action' => substr($call['function'], 0, -6),
                    ];
                }
            }
        }

        return [];
    }

}
