<?php

declare(strict_types=1);

namespace Netlogix\Nxerrorhandler\Tests\Unit\Fixtures;

use Throwable;
use TYPO3\CMS\Core\Error\ExceptionHandlerInterface;

class ExceptionHandlerFixture implements ExceptionHandlerInterface
{

    public function __construct()
    {
    }

    public function handleException(Throwable $exception)
    {
        // NOOP
    }

    public function echoExceptionWeb(Throwable $exception)
    {
        // NOOP
    }

    public function echoExceptionCLI(Throwable $exception)
    {
        // NOOP
    }
}
