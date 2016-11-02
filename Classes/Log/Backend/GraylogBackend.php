<?php
namespace Yeebase\Graylog\Log;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Yeebase.Graylog".       *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\Backend\BackendInterface;
use Yeebase\Graylog\GraylogService;

/**
 * Production Exception handler that reports exceptions to a Graylog server using the official gelf-php library
 */
class GraylogBackend implements BackendInterface
{

    /**
     * @Flow\Inject
     * @var GraylogService
     */
    protected $graylogService;

    /**
     * Thiis method will send a message to our graylog service
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
        $messageContext = [];

        !is_null($packageKey) ? $messageContext['packageKey'] = $packageKey : '';
        !is_null($className) ? $messageContext['className'] = $className : '';
        !is_null($methodName) ? $messageContext['methodName'] = $methodName : '';
        !is_null($additionalData) ? $messageContext['additionalData'] = $additionalData : '';

        $this->graylogService->logMessage($message, $messageContext, $severity);
    }

    public function close()
    {

    }

    public function open()
    {

    }

}
