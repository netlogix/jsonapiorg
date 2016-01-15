<?php
namespace Netlogix\JsonApiOrg\Resource;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * The RelationshipIterator is basically a factory of TopLevel objects
 * holding both, the data as well as its relationships.
 */
class RelationshipIterator {

	/**
	 * @var \Netlogix\JsonApiOrg\Resource\Information\ExposableTypeMap
	 * @Flow\Inject
	 */
	protected $exposableTypeMap;

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
	 * @var array
	 */
	protected $include = array();

	/**
	 * @var array
	 */
	protected $fields = array();

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;


	/**
	 * @var \Netlogix\JsonApiOrg\Resource\Information\ResourceMapper
	 * @Flow\Inject
	 */
	protected $resourceMapper;

	/**
	 * @var \Netlogix\JsonApiOrg\Resource\Resolver\ResourceResolverInterface
	 * @Flow\Inject
	 */
	protected $resourceResolver;

	/**
	 * @var \Netlogix\JsonApiOrg\Resource\RequestStack
	 */
	protected $stack;

	/**
	 * @param mixed $resource
	 * @return \Netlogix\JsonApiOrg\Schema\TopLevel
	 */
	public function createTopLevel($resource) {

		$this->initializeResourceResolver();
		$this->initializeStack($resource);

		$this->traverseRelationships();

		return $this->createResult();
	}

	/**
	 *
	 */
	protected function initializeResourceResolver() {
		$this->resourceResolver->setSupportedMediaTypes($this->getSupportedMediaTypes());
	}

	/**
	 * @param $resource
	 */
	protected function initializeStack($resource) {

		$this->stack = new \Netlogix\JsonApiOrg\Resource\RequestStack();
		if (is_array($resource)
				|| (is_object($resource) && $resource instanceof \Doctrine\Common\Collections\Collection)
				|| (is_object($resource) && $resource instanceof \TYPO3\Flow\Persistence\QueryResultInterface)) {

			foreach ($resource as $singleResource) {
				$resourceUri = $this->resourceMapper->getPublicResourceUri($singleResource);
				$dataIdentifier = $this->resourceMapper->getDataIdentifierForPayload($singleResource);
				$this->stack->push((string)$resourceUri, $dataIdentifier, \Netlogix\JsonApiOrg\Resource\RequestStack::POSITION_DATACOLLECTION);
			}

		} else {
			$resourceUri = $this->resourceMapper->getPublicResourceUri($resource);
			$dataIdentifier = $this->resourceMapper->getDataIdentifierForPayload($resource);
			$this->stack->push((string)$resourceUri, $dataIdentifier, \Netlogix\JsonApiOrg\Resource\RequestStack::POSITION_DATA);

		}

	}

	/**
	 *
	 */
	protected function traverseRelationships() {
		while ($resourceWorkloadPackage = $this->stack->pop()) {
			$this->fetchResourceContentAndQueueRelationships($resourceWorkloadPackage);
		}
	}

	/**
	 * @return \Netlogix\JsonApiOrg\Schema\TopLevel
	 */
	protected function createResult() {

		$result = new \Netlogix\JsonApiOrg\Schema\TopLevel();

		foreach ($this->stack->getResults() as $resourceWorkloadPackage) {
			if ($resourceWorkloadPackage[\Netlogix\JsonApiOrg\Resource\RequestStack::RESULT_DATA]) {
				switch ($resourceWorkloadPackage[\Netlogix\JsonApiOrg\Resource\RequestStack::RESULT_POSITION]) {
					case \Netlogix\JsonApiOrg\Resource\RequestStack::POSITION_DATA:
						$result->setData($resourceWorkloadPackage[\Netlogix\JsonApiOrg\Resource\RequestStack::RESULT_DATA]);
						break;
					case \Netlogix\JsonApiOrg\Resource\RequestStack::POSITION_DATACOLLECTION:
						$result->addData($resourceWorkloadPackage[\Netlogix\JsonApiOrg\Resource\RequestStack::RESULT_DATA]);
						break;
					case \Netlogix\JsonApiOrg\Resource\RequestStack::POSITION_INCLUDE:
						$result->addIncluded($resourceWorkloadPackage[\Netlogix\JsonApiOrg\Resource\RequestStack::RESULT_DATA]);
						break;
				}
			}
		}

		return $result;
	}

	/**
	 * @param array $resourceWorkloadPackage
	 */
	protected function fetchResourceContentAndQueueRelationships(array $resourceWorkloadPackage) {

		$requestUri = $resourceWorkloadPackage[\Netlogix\JsonApiOrg\Resource\RequestStack::RESULT_URI];

		$resource = $this->resourceResolver->resourceRequest($resourceWorkloadPackage);

		/** @var \Netlogix\JsonApiOrg\Schema\ResourceInterface $resource */
		$resource = $this->propertyMapper->convert($resource, \Netlogix\JsonApiOrg\Schema\Resource::class);
		$nestingPaths = $this->stack->getNestingPaths($requestUri);

		$effektiveFields = $this->getEffektiveFieldsForResource($resource);
		if (!is_null($effektiveFields)) {
			$resource->getAttributes()->setSparseFields($effektiveFields);
			$resource->getRelationships()->setSparseFields($effektiveFields);
		}

		$effectiveInclude = $this->getEffectiveIncludeForNestingPaths($nestingPaths);
		$resource->getRelationships()->setIncludeFields($effectiveInclude);

		$this->stack->finalize($requestUri, $resource);

		$relationshipsToBeApiExposed = $resource->getRelationshipsToBeApiExposed();

		foreach ($resource->getRelationships() as $relationshipName => $relationshipContent) {

			if (!$resource->getRelationships()->isAllowedIncludeField($relationshipName)) {
				continue;
			}

			$relationshipNestingPaths = $this->enhanceNestingPaths($nestingPaths, $relationshipName);
			if (!is_array($relationshipContent['data'])) {
				continue;
			}

			switch ($relationshipsToBeApiExposed[$relationshipName]) {
				case \Netlogix\JsonApiOrg\Schema\Relationships::RELATIONSHIP_TYPE_SINGLE:
					$this->pushUriToStack($relationshipContent['data'], $relationshipNestingPaths);
					break;

				case \Netlogix\JsonApiOrg\Schema\Relationships::RELATIONSHIP_TYPE_COLLECTION:
					foreach ($relationshipContent['data'] as $relation) {
						$this->pushUriToStack($relation, $relationshipNestingPaths);
					}
					break;

			}
		}

	}

	/**
	 * @param array<string> $nestingPaths
	 * @return array<string>
	 */
	protected function getEffectiveIncludeForNestingPaths(array $nestingPaths) {
		$effectiveInclude = array();
		foreach ($nestingPaths as $nestingPath) {
			foreach ($this->getInclude() as $include) {
				if ($nestingPath === '') {
					$prefixFreeInclude = $include;
				} elseif($include !== $nestingPath && strpos($include, $nestingPath) === 0) {
					$prefixFreeInclude = substr($include, strlen($nestingPath) + 1);
				} else {
					continue;
				}
				$include = current(explode('.', $prefixFreeInclude));
				$effectiveInclude[$include] = $include;
			}
		}
		return $effectiveInclude;
	}

	/**
	 * @param \Netlogix\JsonApiOrg\Schema\ResourceInterface $resource
	 */
	protected function getEffektiveFieldsForResource(\Netlogix\JsonApiOrg\Schema\ResourceInterface $resource) {
		$type = $this->exposableTypeMap->getType($resource->getType($resource->getPayload()));
		if (array_key_exists($type, $this->fields)) {
			return $this->fields[$type];
		}
	}

	/**
	 * @param array<string> $nestingPaths
	 * @param string $relationshipName
	 * @return array<string>
	 */
	protected function enhanceNestingPaths($nestingPaths, $relationshipName) {
		$relationshipNestingPaths = array();
		foreach ($nestingPaths as $nestingPath) {
			$nestingPath = ltrim($nestingPath . '.' . $relationshipName, '.');
			$relationshipNestingPaths[$nestingPath] = $nestingPath;
		}
		return $relationshipNestingPaths;
	}

	/**
	 * @param array $relation
	 * @param string $nestingPaths
	 */
	protected function pushUriToStack($relation, $relationshipNestingPaths) {
		$uri = $this->getResourceUriForArrayFormat($relation);
		foreach ($relationshipNestingPaths as $nestingPath) {
			$this->stack->push((string)$uri, $relation, \Netlogix\JsonApiOrg\Resource\RequestStack::POSITION_INCLUDE, $nestingPath);
		}
	}

	/**
	 * @param array $relation
	 * @return string
	 */
	protected function getResourceUriForArrayFormat($relation) {
		$resource = $this->propertyMapper->convert((string)$relation['id'], $this->exposableTypeMap->getClassName($relation['type']));
		$resourceInformation = $this->resourceMapper->findResourceInformation($resource);
		return $resourceInformation->getPublicResourceUri($resource);
	}

	/**
	 * @param array<string> $supportedMediaTypes
	 */
	public function setSupportedMediaTypes(array $supportedMediaTypes) {
		$this->supportedMediaTypes = $supportedMediaTypes;
	}

	/**
	 * @return array<string>
	 */
	public function getSupportedMediaTypes() {
		return $this->supportedMediaTypes;
	}

	/**
	 * @param array<string> $include
	 */
	public function setInclude(array $includes) {
		$this->include = array();
		foreach ($includes as $include) {
			$include = (string)$include;
			$this->include[$include] = $include;
		}
	}

	/**
	 * @return array
	 */
	public function getInclude() {
		return $this->include;
	}

	/**
	 * @param array<array<string>> $include
	 */
	public function setFields(array $fields) {
		$this->fields = $fields;
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}

}