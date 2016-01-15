<?php
namespace Netlogix\JsonApiOrg\Resource\Resolver;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use TYPO3\Flow\Annotations as Flow;

/**
 * The ResourceResolver takes an incoming requestData data structure
 * and returns the according resource object.
 */
class ResourceResolverBySubrequest extends \TYPO3\Flow\Http\Client\Browser implements \Netlogix\JsonApiOrg\Resource\Resolver\ResourceResolverInterface {

	const SUB_REQUEST_HEADER = 'X-Jsonapiorg-Subrequest';

	/**
	 * @var \TYPO3\Flow\Http\Client\RequestEngineInterface
	 * @Flow\Inject
	 * @TODO I'd love to have the InternalRequestEngine used here
	 */
	protected $requestEngine;

	/**
	 * The supported media types information is used for both, providing
	 * an Accept header to sub requests as well as validating responses.
	 *
	 * @var array
	 */
	protected $supportedMediaTypes = array(
		'application/vnd.api+json'
	);

	/**
	 * @param array $requestData
	 * @return array
	 */
	public function resourceRequest(array $requestData) {
		$response = $this->request($requestData[\Netlogix\JsonApiOrg\Resource\RequestStack::RESULT_URI]);

		if ($response->getStatusCode() !== 200 || !in_array($response->getHeader('Content-Type'), $this->supportedMediaTypes)) {
			throw new \Exception('This request data can not be processed', 1452854998);
		}

		return json_decode($response->getContent(), TRUE);
	}

	/**
	 * @param array $supportedMediaTypes
	 */
	public function setSupportedMediaTypes(array $supportedMediaTypes) {
		$this->supportedMediaTypes = $supportedMediaTypes;
		$this->addAutomaticRequestHeader('Accept', current($supportedMediaTypes));
	}

	/**
	 *
	 */
	public function initializeObject() {
		$this->addAutomaticRequestHeader(self::SUB_REQUEST_HEADER, 'true');
	}

}