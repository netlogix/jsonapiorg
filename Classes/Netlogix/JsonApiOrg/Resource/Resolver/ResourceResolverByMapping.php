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
class ResourceResolverByMapping implements \Netlogix\JsonApiOrg\Resource\Resolver\ResourceResolverInterface {

	/**
	 * @var array
	 */
	protected $automaticRequestHeader = array();

	/**
	 * @var \TYPO3\Flow\Http\Client\InternalRequestEngine
	 * @Flow\Inject
	 */
	protected $requestEngine;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

	/**
	 * @param array $requestData
	 * @return array
	 */
	public function resourceRequest(array $requestData) {

		/** @var \Netlogix\JsonApiOrg\Schema\Resource $resource */
		$resource = $this->propertyMapper->convert($requestData[\Netlogix\JsonApiOrg\Resource\RequestStack::RESULT_DATA_IDENTIFIER], \Netlogix\JsonApiOrg\Schema\Resource::class);

		if (!$resource) {
			throw new \Exception('This request data can not be processed', 1452854971);
		}

		return json_decode(json_encode($resource), TRUE);
	}

	/**
	 * @param array $supportedMediaTypes
	 */
	public function setSupportedMediaTypes(array $supportedMediaTypes) {

	}

}