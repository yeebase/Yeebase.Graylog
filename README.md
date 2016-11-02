Yeebase.Graylog
================

The Yeebase.Graylog Flow package logs your exceptions as well as single messages to a central Graylog server. This
package also provides a simple backend to log message of Flows Logger classes to a Graylog server.

It depends on the official GELF php package https://github.com/bzikarsky/gelf-php

Installation & configuration
------------

Just add "yeebase/graylog" as dependency to your composer.json and run a "composer update" in your project's root folder.

Configure your Graylog Server:
```yaml
Yeebase:
  Graylog:
    host: '127.0.0.1'
    port: 12201
    chunksize: 'wan'
    skipStatusCodes: [403, 404]
```


Activate the exception handler and configure the connection to your graylog server in your Settings.yaml:

```yaml
TYPO3:
  Flow:
    error:
      exceptionHandler:
        className: 'Yeebase\Graylog\Error\GraylogExceptionHandler'
```

To use log messages of Flows Logger classes configure your logger to use our backend in your Objects.yaml. The 
Graylog backend behaves the same way the other backends do.

```yaml
Some\Vendor\Class:
  properties:
    logger:
      object:
        factoryObjectName: TYPO3\Flow\Log\LoggerFactory
        arguments:
          1:
            value: 'Graylog'
          2:
            value: 'TYPO3\Flow\Log\Logger'
          3:
            setting: Yeebase.Graylog.Logger.backend
          4:
            setting: Yeebase.Graylog.Logger.backendOptions
```