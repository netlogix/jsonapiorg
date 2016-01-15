<?php
namespace Netlogix\JsonApiOrg\Property\TypeConverter;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * The target type of this converter is any kind of Schema\Resource.
 */
class ResourceConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \Netlogix\JsonApiOrg\Resource\Information\ResourceMapper
	 * @Flow\Inject
	 */
	protected $resourceMapper;

	/**
	 * @var \Netlogix\JsonApiOrg\Resource\Information\ExposableTypeMap
	 * @Flow\Inject
	 */
	protected $exposableTypeMap;

	/**
	 * The source types this converter can convert.
	 *
	 * @var array<string>
	 * @api
	 */
	protected $sourceTypes = array('array', 'string');

	/**
	 * The target type this converter can convert to.
	 *
	 * @var string
	 * @api
	 */
	protected $targetType = 'Netlogix\\JsonApiOrg\\Schema\\Resource';

	/**
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface|null $configuration
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null) {

		if (is_array($source)) {
			$sourceArray = $source;
		} elseif (is_string($source)) {
			$sourceArray = json_decode($source, TRUE);
		}

		$payload = NULL;

		if (is_array($sourceArray) && isset($sourceArray['id']) && isset($sourceArray['type'])) {
			$payload = $this->propertyMapper->convert($sourceArray['id'], $this->exposableTypeMap->getClassName($sourceArray['type']));
		}

		if (is_null($payload) && is_array($sourceArray) && isset($sourceArray['type'])) {
			$payload = $this->propertyMapper->convert(array(), $this->exposableTypeMap->getClassName($sourceArray['type']));
		}

		if (is_null($payload) && is_array($sourceArray) && isset($sourceArray['__identity'])) {
			$source = $sourceArray['__identity'];
		}

		if (is_null($payload) && is_string($source)) {
			/** @var \Netlogix\JsonApiOrg\Schema\ResourceInterface $dummyPayload */
			$dummyPayload = $this->objectManager->get($targetType);
			$typeIdentifier = $dummyPayload->getType();
			$type = $this->exposableTypeMap->getType($typeIdentifier);
			$className = $this->exposableTypeMap->getClassName($type);
			$payload = $this->propertyMapper->convert($source, $className);
		}

		$resourceInformation = $this->resourceMapper->findResourceInformation($payload);
		$resource = $resourceInformation->getResource($payload);

		if (isset($source['attributes'])) {
			$attributes = $resource->getAttributes();
			foreach ($source['attributes'] as $fieldName => $value) {
				$attributes[$fieldName] = $value;
			}
		}

		if (isset($source['relationships'])) {
			$relationships = $resource->getRelationships();
			foreach ($source['relationships'] as $fieldName => $value) {
				$relationships[$fieldName] = $value;
			}
		}

		return $resource;
	}

}