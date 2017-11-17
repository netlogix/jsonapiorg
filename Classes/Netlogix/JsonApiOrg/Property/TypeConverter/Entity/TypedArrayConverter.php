<?php
namespace Netlogix\JsonApiOrg\Property\TypeConverter\Entity;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

/**
 */
class TypedArrayConverter extends AbstractSchemaResourceBasedEntityConverter
{

    /**
     * @var integer
     */
    protected $priority = 1457600398;

    /**
     * @var array<string>
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var \Neos\Flow\Property\TypeConverter\TypedArrayConverter
     */
    protected $typedArrayConverter;

    public function __construct()
    {
        $this->typedArrayConverter = new \Neos\Flow\Property\TypeConverter\TypedArrayConverter();
    }

    /**
     * @inheritdoc
     */
    public function canConvertFrom($source, $targetType)
    {
        if (!$this->typedArrayConverter->canConvertFrom($source, $targetType)) {
            return false;
        }
        if (!is_array($source) || !array_key_exists('data', $source)) {
            return false;
        }

        foreach ($source['data'] as $data) {
            if (!$this->hasOnlyIdentifierProperties($data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = array(),
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        $result = $this->typedArrayConverter->convertFrom($source['data'], $targetType, $convertedChildProperties, $configuration);
        return $result['data'];
    }

    /**
     * @inheritdoc
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return $this->typedArrayConverter->getSourceChildPropertiesToBeConverted($source);
    }

    /**
     * @inheritdoc
     */
    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration)
    {
        if ($propertyName === 'data') {
            return $targetType;
        }

        return $this->typedArrayConverter->getTypeOfChildProperty($targetType, $propertyName, $configuration);
    }

}