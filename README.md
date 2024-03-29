# TYPO3 extension nxerrorhandler

[![stability-wip](https://img.shields.io/badge/stability-wip-lightgrey.svg)](https://github.com/netlogix/nxerrorhandler)
[![TYPO3 V12](https://img.shields.io/badge/TYPO3-12-orange.svg)](https://get.typo3.org/version/12)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)
[![GitHub CI status](https://github.com/netlogix/nxerrorhandler/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/netlogix/nxerrorhandler/actions)

Improves error handling in TYPO3 by using statically rendered error documents for output to reduce strain on the server.

This extension is a work in progress.

## Usage

Add this to your `config/system/settings.php`

```php
return [
    'EXTENSIONS' => [
        'nxerrorhandler' => [
            'exceptionHandlerComponents' => [
                \Netlogix\Nxerrorhandler\ErrorHandler\Component\ExtbaseArgumentsToBadRequestComponent::class,
                \Netlogix\Nxerrorhandler\ErrorHandler\Component\StaticDocumentComponent::class,
            ],
        ]
    ],
    'SYS' => [
        'productionExceptionHandler' => \Netlogix\Nxerrorhandler\ErrorHandler\GeneralExceptionHandler::class
    ],
];
```

Note: This will register the ExceptionHandler for all contexts including backend
requests. If you want to restrict it to frontend requests only then add this
line to `config/system/additional.php` instead:

```php
    if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_FE) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = \Netlogix\Nxerrorhandler\ErrorHandler\GeneralExceptionHandler::class;
    }
```

Add this to your `config/sites/sitename/config.yaml`

```yaml
errorHandling:
    - errorCode: '400'
      errorHandler: PHP
      errorPhpClassFQCN: Netlogix\Nxerrorhandler\Error\PageContentErrorHandler
      errorContentSource: 't3://page?uid=99'
    - errorCode: '404'
      errorHandler: PHP
      errorPhpClassFQCN: Netlogix\Nxerrorhandler\Error\PageContentErrorHandler
      errorContentSource: 't3://page?uid=99'
    - errorCode: '500'
      errorHandler: PHP
      errorPhpClassFQCN: Netlogix\Nxerrorhandler\Error\PageContentErrorHandler
      errorContentSource: 't3://page?uid=99'
```
