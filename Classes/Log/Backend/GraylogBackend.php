<?php
namespace Yeebase\Graylog\Log\Backend;

/*                                                                        *
 * This script belongs to the Flow package "Yeebase.Graylog".             *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Backend\AbstractBackend;
use Neos\Flow\Log\SystemLoggerInterface;
use Yeebase\Graylog\GraylogService;

/**
 * Backend that can be used for Logger that implement Neos\Flow\Log\LoggerInterface
 */
class GraylogBackend extends AbstractBackend
{

    /**
     * @Flow\Inject
     * @var GraylogService
     */
    protected $graylogService;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * An array of severity labels, indexed by their integer constant
     * @var array
     */
    protected $severityLabels;

    /**
     * @var bool
     */
    protected $alsoLogWithSystemLogger;

    /**
     * This method will send a message to our graylog service
     *
     * @param string $message
     * @param int $severity
     * @param null $additionalData
     * @param null $packageKey
     * @param null $className
     * @param null $methodName
     */
    public function append($message, $severity = LOG_INFO, $additionalData = null, $packageKey = null, $className = null, $methodName = null)
    {
        if ($severity > $this->severityThreshold) {
            return;
        }

        $ipAddress = ($this->logIpAddress === true) ? str_pad((isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''), 15) : '';
        $severityLabel = (isset($this->severityLabels[$severity])) ? $this->severityLabels[$severity] : 'UNKNOWN  ';

        $output = $severityLabel . ': ' . $message;

        $messageContext = [];
        !is_null($packageKey) ? $messageContext['packageKey'] = $packageKey : '';
        !is_null($className) ? $messageContext['className'] = $className : '';
        !is_null($methodName) ? $messageContext['methodName'] = $methodName : '';
        !is_null($additionalData) ? $messageContext['additionalData'] = $additionalData : '';
        !is_null($ipAddress) ? $messageContext['ipAddress'] = $ipAddress : '';
        !is_null($severityLabel) ? $messageContext['severityLabel'] = $severityLabel : '';

        $this->graylogService->logMessage($output, $messageContext, $severity);

        if ($this->alsoLogWithSystemLogger) {
            $this->systemLogger->log($output, $severity, $additionalData, $packageKey, $className, $methodName);
        }
    }

    public function open()
    {
        $this->severityLabels = [
            LOG_EMERG   => 'EMERGENCY',
            LOG_ALERT   => 'ALERT    ',
            LOG_CRIT    => 'CRITICAL ',
            LOG_ERR     => 'ERROR    ',
            LOG_WARNING => 'WARNING  ',
            LOG_NOTICE  => 'NOTICE   ',
            LOG_INFO    => 'INFO     ',
            LOG_DEBUG   => 'DEBUG    ',
        ];
    }

    public function close()
    {
    }

    /**
     * @param bool $alsoLogWithSystemLogger
     */
    public function setAlsoLogWithSystemLogger(bool $alsoLogWithSystemLogger)
    {
        $this->alsoLogWithSystemLogger = $alsoLogWithSystemLogger;
    }
}
