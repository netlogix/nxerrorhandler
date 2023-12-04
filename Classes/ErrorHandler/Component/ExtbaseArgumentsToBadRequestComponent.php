<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\ErrorHandler\Component;

use Throwable;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Extbase\Property\Exception as PropertyException;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;

/**
 * Change HTTP status code to 400 Bad Request if the exception is from extbase property mapper or a required argument is
 * missing for an extbase controller action.
 */
class ExtbaseArgumentsToBadRequestComponent extends AbstractComponent
{
    public function getHttpHeaders(Throwable $exception): array
    {
        if ($exception instanceof TargetNotFoundException) {
            return [HttpUtility::HTTP_STATUS_404];
        }

        if ($exception instanceof PropertyException || $exception instanceof RequiredArgumentMissingException) {
            return [HttpUtility::HTTP_STATUS_400];
        }

        return [];
    }
}
