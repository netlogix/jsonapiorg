<?php
namespace Netlogix\JsonApiOrg\Property\TypeConverter\SchemaResource;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * The target type of this converter is any kind of Schema\Resource.
 */
class ResourceConverter extends AbstractTypeConverter
{

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
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return mixed|\Netlogix\JsonApiOrg\Schema\ResourceInterface|\TYPO3\Flow\Error\Error
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = array(),
        PropertyMappingConfigurationInterface $configuration = null
    ) {

        if (is_string($source)) {
            $sourceArray = json_decode($source, true);
            $source = is_array($sourceArray) ? $sourceArray : ['id' => $source];
        }

        if (!array_key_exists('type', $source)) {
            $dummyPayload = $this->objectManager->get($targetType);
            $typeIdentifier = $dummyPayload->getType();
            $source['type'] = $this->exposableTypeMap->getType($typeIdentifier);
        }

        if (array_key_exists('id', $source)) {
            $arguments = $source['id'];
        } else {
            $arguments = [];
        }
        $payload = $this->propertyMapper->convert($arguments, $this->exposableTypeMap->getClassName($source['type']));

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