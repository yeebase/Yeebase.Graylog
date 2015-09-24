Yeebase.Graylog
================

The Yeebase.Graylog Flow package logs your exceptions to a central Graylog server. It depends on the official GELF php package https://github.com/bzikarsky/gelf-php

Installation & configuration
------------

Just add "yeebase/graylog" as dependency to your composer.json and run a "composer update" in your project's root folder.

Activate the exception handler and configure the connection to your graylog server in your Settings.yaml:

```yaml
TYPO3:
  Flow:
    error:
      exceptionHandler:
        className: 'Yeebase\Graylog\Error\GraylogExceptionHandler'
        host: '127.0.0.1'
        port: 12201
        chunkSize: wan
```
