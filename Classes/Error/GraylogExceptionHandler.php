<?php
namespace Yeebase\Graylog\Error;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Yeebase.Graylog".       *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Error\ProductionExceptionHandler;
use Yeebase\Graylog\GraylogService;

/**
 * Production Exception handler that reports exceptions to a Graylog server using the official gelf-php library
 */
class GraylogExceptionHandler extends ProductionExceptionHandler {

	/**
	 * @var GraylogService
	 */
	protected $graylogService;

	/**
	 * @param \Exception $exception
	 * @return void
	 */
	protected function echoExceptionWeb($exception) {
		if ($this->graylogService === NULL) {
			$this->graylogService = new GraylogService();
		}
		$this->graylogService->logException($exception);
		parent::echoExceptionWeb($exception);
	}

	/**
	 * @param \Exception $exception The exception
	 * @return void
	 */
	protected function echoExceptionCli($exception) {
		if ($this->graylogService === NULL) {
			$this->graylogService = new GraylogService();
		}
		$this->graylogService->logException($exception);
		parent::echoExceptionCli($exception);
	}
}