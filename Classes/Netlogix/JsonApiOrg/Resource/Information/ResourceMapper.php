<?php
namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * The resource mapper converts any internal payload to
 * an exposable resource object.
 *
 * @Flow\Scope("singleton")
 */
class ResourceMapper {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

	/**
	 * @var \Netlogix\JsonApiOrg\Resource\Information\ExposableTypeMap
	 * @Flow\Inject
	 */
	protected $exposableTypeMap;

	/**
	 * @var array<\Netlogix\JsonApiOrg\Resource\Information\ResourceInformationInterface>
	 */
	protected $resourceInformation = array();

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * Lifecycle method, called after all dependencies have been injected.
	 * Here, the Dto\Converter array gets initialized.
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->initializeControllerContext();
		$this->initializeConverters();
	}

	/**
	 * @return \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	/**
	 * Returns the resource information with the highest priority being capable
	 * of dealing with the given $payload.
	 *
	 * The controllerContext is added to the resource information object to allow
	 * it to create proper URIs.
	 *
	 * @todo Use runtime cache based on spl_object_hash
	 * @param mixed $resource
	 * @return \Netlogix\JsonApiOrg\Resource\Information\ResourceInformationInterface
	 */
	public function findResourceInformation($payload) {

		if (is_array($payload) && isset($payload['type']) && isset($payload['id'])) {
			$payload = $this->propertyMapper->convert((string)$payload['id'], $this->exposableTypeMap->getClassName($payload['type']));
		}

		/** @var ResourceInformationInterface $resourceInformation */
		foreach ($this->resourceInformation as $resourceInformation) {
			if ($resourceInformation->canHandle($payload)) {
				return $resourceInformation;
			}
		}
	}

	/**
	 * For any given $payload, the array structure holding the
	 * public type identifier as well as the resource id is returned.
	 *
	 * @param mixed $payload
	 * @return array
	 * @see http://jsonapi.org/format/#document-resource-identifier-objects
	 */
	public function getDataIdentifierForPayload($payload) {
		$resourceInformation = $this->findResourceInformation($payload);
		$resource = $resourceInformation->getResource($payload);
		return array(
			'type' => $this->exposableTypeMap->getType($resource->getType()),
			'id' => $resource->getId()
		);
	}

	/**
	 * If a proper data identifier structure is given, the corresponding
	 * payload is returned.
	 *
	 * @param mixed $identifier
	 * @return mixed
	 * @see http://jsonapi.org/format/#document-resource-identifier-objects
	 */
	public function getPayloadForDataIdentifier($dataIdentifier) {
		if ($dataIdentifier === NULL) {
			return NULL;
		}
		$resourceConverter = new \Netlogix\JsonApiOrg\Property\TypeConverter\ResourceConverter();
		/** @var \Netlogix\JsonApiOrg\Schema\Resource $resource */
		$resource = $resourceConverter->convertFrom($dataIdentifier, \Netlogix\JsonApiOrg\Schema\Resource::class);
		return $resource->getPayload();
	}

	/**
	 * @param mixed $payload
	 * @return \TYPO3\Flow\Http\Uri
	 * @see ResourceInformationInterface::getPublicResourceUri()
	 */
	public function getPublicResourceUri($payload) {
		$resourceInformation = $this->findResourceInformation($payload);
		return $resourceInformation->getPublicResourceUri($payload);
	}

	/**
	 * The Resource Information most likely needs an UriBuilder, so having a
	 * ControllerContext in place might come in handy.
	 *
	 * @return void
	 */
	protected function initializeControllerContext() {

		$request = new \TYPO3\Flow\Mvc\ActionRequest(\TYPO3\Flow\Http\Request::createFromEnvironment());
		$request->setDispatched(true);

		$response = new \TYPO3\Flow\Http\Response();

		$uriBuilder = new \TYPO3\Flow\Mvc\Routing\UriBuilder();
		$uriBuilder->setRequest($request);

		$arguments = new \TYPO3\Flow\Mvc\Controller\Arguments(array());

		$this->controllerContext = new \TYPO3\Flow\Mvc\Controller\ControllerContext($request, $response, $arguments, $uriBuilder);
	}

	/**
	 * @return void
	 */
	protected function initializeConverters() {

		$this->resourceInformation = array();

		foreach ($this->reflectionService->getAllImplementationClassNamesForInterface(ResourceInformationInterface::class) as $resourceInformationClassName) {
			$this->resourceInformation[] = $this->objectManager->get($resourceInformationClassName);
		}
		usort($this->resourceInformation, function(ResourceInformationInterface $first, ResourceInformationInterface $second) {
			if ($first->getPriority() == $second->getPriority()) {
				return strcmp(\TYPO3\Flow\Utility\TypeHandling::getTypeForValue($first), \TYPO3\Flow\Utility\TypeHandling::getTypeForValue($second));
			} else {
				return $first->getPriority() < $second->getPriority();
			}
		});
	}

}