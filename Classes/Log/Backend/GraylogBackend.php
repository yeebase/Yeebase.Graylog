<?php
namespace Yeebase\Graylog\Log\Backend;

/*                                                                        *
 * This script belongs to the Flow package "Yeebase.Graylog".             *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Backend\AbstractBackend;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
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
     * @param string $message The message to log
     * @param integer $severity One of the LOG_* constants
     * @param mixed $additionalData A variable containing more information about the event to be logged
     * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
     * @param string $className Name of the class triggering the log (determined automatically if not specified)
     * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
     * @return void
     */
    public function append($message, $severity = LOG_INFO, $additionalData = null, $packageKey = null, $className = null, $methodName = null)
    {
        if ($severity > $this->severityThreshold) {
            return;
        }

        $ipAddress = ($this->logIpAddress === true) ? str_pad((isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''), 15) : '';
        $severityLabel = (isset($this->severityLabels[$severity])) ? $this->severityLabels[$severity] : 'UNKNOWN  ';

        $output = $severityLabel . ': ' . $message;

        $messageContext = [
            'packageKey' => !is_null($packageKey) ? $packageKey : '',
            'className' => !is_null($className) ? $className : '',
            'methodName' => !is_null($methodName) ? $methodName : '',
            'additionalData' => !is_null($additionalData) ? $additionalData : '',
            'ipAddress' => !is_null($ipAddress) ? $ipAddress : '',
            'severityLabel' => !is_null($severityLabel) ? $severityLabel : '',
        ];
        $this->getGraylogService()->logMessage($output, $messageContext, $severity);
      
        if ($this->alsoLogWithSystemLogger && $this->systemLogger instanceof SystemLoggerInterface) {
            $this->systemLogger->log($output, $severity, $additionalData, $packageKey, $className, $methodName);
        }
    }

    /**
     * Called when this backend is added to a logger
     *
     * @return void
     */
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

    /**
     * Called when this backend is removed from a logger
     *
     * @return void
     */
    public function close()
    {
        // nothing to do here
    }

    /**
     * Returns an instance of the injected GraylogService (including a fallback to a manually instantiated instance
     * if Dependency Injection is not (yet) available)
     *
     * @return GraylogService
     */
    private function getGraylogService()
    {
        if ($this->graylogService instanceof GraylogService) {
            return $this->graylogService;
        } elseif ($this->graylogService instanceof DependencyProxy) {
            return $this->graylogService->_activateDependency();
        } else {
            return new GraylogService();
        }
    }

    /**
     * @param bool $alsoLogWithSystemLogger
     */
    public function setAlsoLogWithSystemLogger(bool $alsoLogWithSystemLogger)
    {
        $this->alsoLogWithSystemLogger = $alsoLogWithSystemLogger;
    }
}
