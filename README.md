# TYPO3 extension nxerrorhandler

[![TYPO3 V13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)
[![GitHub CI status](https://github.com/netlogix/nxerrorhandler/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/netlogix/nxerrorhandler/actions)

Improves error handling in TYPO3 by using statically rendered error documents for output to reduce strain on the server.

## Compatibility

The current version of this extension has been tested in TYPO3 13 on PHP 8.3, 8.4.

## Usage

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
