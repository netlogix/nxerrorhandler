# TYPO3 extension nxerrorhandler

Improves error handling in TYPO3. Can send exceptions by mail or to sentry and uses
statically rendered error documents for output to reduce strain on the server.

## Usage

Add this to your `LocalConfiguration.php`

```php
return [
    'EXTENSIONS' => [
        'nxerrorhandler' => [
            'sentry' => [
                'dsn' => '',
            ],
            'skipForStatusCodes' => [
                '404'
            ],
            'exceptionHandlerComponents' => [
                \Netlogix\Nxerrorhandler\ErrorHandler\Component\ExtbaseArgumentsToBadRequestComponent::class,
                \Netlogix\Nxerrorhandler\ErrorHandler\Component\SentryComponent::class,
                \Netlogix\Nxerrorhandler\ErrorHandler\Component\StaticDocumentComponent::class,
            ],
        ]
    ],
    'FE' => [
        'pageNotFound_handling' => 'USER_FUNCTION:' . \Netlogix\Nxerrorhandler\ErrorHandler\PageNotFoundHandler::class . '->handlePageNotFound'
    ],
    'SYS' => [
        'productionExceptionHandler' => \Netlogix\Nxerrorhandler\ErrorHandler\GeneralExceptionHandler::class
    ],
];
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

The new Sentry SDK 3.x has some environment variables which can be used, for example in a .env file:
```apacheconfig
SENTRY_DSN='http://public_key@your-sentry-server.com/project-id'
SENTRY_RELEASE='1.0.7'
SENTRY_ENVIRONMENT='Staging'
```
