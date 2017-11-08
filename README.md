# Yeebase.Graylog

The Yeebase.Graylog Flow package logs your exceptions as well as single messages to a central Graylog server. This
package also provides a simple backend to log message of Flows Logger classes to a Graylog server.

It depends on the official GELF php package https://github.com/bzikarsky/gelf-php

## Installation & configuration

Just add "yeebase/graylog" as dependency to your composer.json and run a "composer update" in your project's root folder
or simply execute:
```
composer require yeebase/graylog
```
from your project's root.

Configure your Graylog Server:
```yaml
Yeebase:
  Graylog:
    host: '127.0.0.1'
    port: 12201
    chunksize: 'wan'
```

### Log exceptions

Activate the exception handler and configure the connection to your graylog server in your Settings.yaml:

```yaml
Neos:
  Flow:
    error:
      exceptionHandler:
        className: 'Yeebase\Graylog\Error\GraylogExceptionHandler'
```

*Note:* For `Development` context, the `Neos.Flow` package overrides this setting. Make sure to add this configuration
in the right context Settings.yaml.

#### Filter exceptions

To skip certain exceptions from being logged you can either use the `skipStatusCodes` setting:

```yaml
Yeebase:
  Graylog:
     # don't log any exceptions that would result in a HTTP status 403 (access denied) / 404 (not found)
    skipStatusCodes: [403, 404]
```

Since version 2.1 you can alternatively use the `renderingGroups` Flow setting, i.e. to exclude certain Exception
*classes* from being logged:

```yaml
Neos:
  Flow:
    error:
      exceptionHandler:
        className: 'Yeebase\Graylog\Error\GraylogExceptionHandler'
        renderingGroups:
          'accessDeniedExceptions':
            matchingExceptionClassNames: ['Neos\Flow\Security\Exception\AccessDeniedException']
            options:
              logException: false
```

### Manual logging


If you wish to log normal log messages to your Graylog server just use the provided `GraylogLoggerInterface`:

```php
use Neos\Flow\Annotations as Flow;
use Yeebase\Graylog\Log\GraylogLoggerInterface;

class SomeClass 
{
    /**
     * @Flow\Inject
     * @var GraylogLoggerInterface
     */
    protected $graylogLogger;

    public function yourMethod()
    {
      $this->graylogLogger->log('Your Message')
    }
}

```

By default messages will also be logged to the `SystemLoggerInterface` when Flow runs in `Development` context. You
can enable or disable this function with a setting:

```yaml
Yeebase:
  Graylog:
    Logger:
      backendOptions:
        alsoLogWithSystemLogger: true
```
