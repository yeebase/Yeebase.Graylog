<?php
namespace Yeebase\Graylog;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Yeebase.Graylog".      *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use Gelf\Publisher;
use Gelf\Logger;
use Gelf\Transport\UdpTransport;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Exception as FlowException;
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Party\Domain\Model\Person;
use TYPO3\Party\Domain\Service\PartyService;

/**
 * Service that can be used to report exceptions to a Graylog server using the official gelf-php library
 *
 * @Flow\Scope("singleton")
 */
class GraylogService {

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \Exception $exception
	 * @return void
	 */
	public function logException(\Exception $exception) {
		if (!isset($this->settings['host']) || strlen($this->settings['host']) === 0) {
			return;
		}
		$statusCode = NULL;
		if ($exception instanceof FlowException) {
			$statusCode = $exception->getStatusCode();
		}
		// skip exceptions with status codes matching "skipStatusCodes" setting
		if (isset($this->settings['skipStatusCodes']) && in_array($statusCode, $this->settings['skipStatusCodes'])) {
			return;
		}
		$host = $this->settings['host'];
		$port = isset($this->settings['port']) ? $this->settings['port'] : UdpTransport::DEFAULT_PORT;

		// set chunk size option to wan (default) or lan
		if (isset($this->settings['chunksize']) && strtolower($this->settings['chunksize']) === 'lan') {
			$chunkSize = UdpTransport::CHUNK_SIZE_LAN;
		} else {
			$chunkSize = UdpTransport::CHUNK_SIZE_WAN;
		}

		// setup connection to graylog server
		$transport = new UdpTransport($host, $port, $chunkSize);
		$publisher = new Publisher();
		$publisher->addTransport($transport);

		// set logLevel depending on http status code
		$logLevel = 4; // warning
		if ($statusCode === 500) {
			$logLevel = 3; // error
		}

		// build message context
		$messageContext = array(
			'full_message' => $exception->getTraceAsString(),
			'reference_code' => $exception instanceof FlowException ? $exception->getReferenceCode() : NULL,
			'response_status' => $statusCode,
			'short_message' => sprintf('%d %s', $statusCode, Response::getStatusMessageByCode($statusCode)),
			'code' => $exception->getCode(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine()
		);
		if ($this->securityContext !== NULL && $this->securityContext->isInitialized()) {
			$account = $this->securityContext->getAccount();
			if ($account !== NULL) {
				$messageContext['authenticated_account'] = $account->getAccountIdentifier() . ' (' . $this->persistenceManager->getIdentifierByObject($account) . ')';
				$messageContext['authenticated_roles'] = implode(', ', array_keys($this->securityContext->getRoles()));
				if ($this->objectManager->isRegistered(PartyService::class)) {
					/** @var PartyService $partyService */
					$partyService = $this->objectManager->get(PartyService::class);
					$person = $partyService->getAssignedPartyOfAccount($account);
					if ($person instanceof Person) {
						$messageContext['authenticated_person'] = (string)$person->getName() . ' (' . $this->persistenceManager->getIdentifierByObject($person) . ')';
					}
				}
			}
		}

		// prepare request details
		if (Bootstrap::$staticObjectManager instanceof ObjectManagerInterface) {
			$bootstrap = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\Bootstrap');
			/* @var Bootstrap $bootstrap */
			$requestHandler = $bootstrap->getActiveRequestHandler();
			if ($requestHandler instanceof HttpRequestHandlerInterface) {
				$request = $requestHandler->getHttpRequest();
				$requestData = array(
					'request_domain' => $request->getHeader('Host'),
					'request_remote_addr' => $request->getClientIpAddress(),
					'request_path' => $request->getRelativePath(),
					'request_uri' => $request->getUri()->getPath(),
					'request_user_agent' => $request->getHeader('User-Agent'),
					'request_method' => $request->getMethod(),
					'request_port' => $request->getPort()
				);
				$messageContext = array_merge($messageContext, $requestData);
			}
		}

		// send message to graylog server
		$logger = new Logger($publisher);
		$logger->log($logLevel, $exception->getMessage(), $messageContext);
	}
}