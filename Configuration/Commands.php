<?php

declare(strict_types=1);

use Netlogix\Nxerrorhandler\Command\GenerateErrorPagesCommand;

return [
    'nxerrorhandler:generateErrorPages' => [
        'class' => GenerateErrorPagesCommand::class,
    ],
];
