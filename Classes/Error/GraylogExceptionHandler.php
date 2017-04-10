<?php
namespace Yeebase\Graylog\Error;

/*                                                                        *
 * This script belongs to the Flow package "Yeebase.Graylog".             *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\ProductionExceptionHandler;
use Yeebase\Graylog\GraylogService;

/**
 * Production Exception handler that reports exceptions to a Graylog server using the official gelf-php library
 */
class GraylogExceptionHandler extends ProductionExceptionHandler
{

    /**
     * @Flow\Inject
     * @var GraylogService
     */
    protected $graylogService;

    /**
     * @param \Exception|\Throwable $exception
     * @return void
     */
    protected function echoExceptionWeb($exception)
    {
        if ($this->graylogService === null) {
            $this->graylogService = new GraylogService();
        }
        $this->graylogService->logException($exception);
        parent::echoExceptionWeb($exception);
    }

    /**
     * @param \Exception|\Throwable $exception The exception
     * @return void
     */
    protected function echoExceptionCli($exception)
    {
        if ($this->graylogService === null) {
            $this->graylogService = new GraylogService();
        }
        $this->graylogService->logException($exception);
        parent::echoExceptionCli($exception);
    }
}
