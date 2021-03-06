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
class SingleRelationshipConverter extends AbstractSchemaResourceBasedEntityConverter
{

    /**
     * @var integer
     */
    protected $priority = 1457600397;

    /**
     * @var array
     */
    protected $sourceTypes = array('array');

    /**
     * Only convert non-persistent types
     *
     * @param mixed $source
     * @param string $targetType
     * @return boolean
     */
    public function canConvertFrom($source, $targetType)
    {
        if (!is_array($source) || !array_key_exists('data', $source)) {
            return false;
        }

        return is_null($source['data']) ?: $this->hasOnlyIdentifierProperties($source['data']);
    }

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * The return value can be one of three types:
     * - an arbitrary object, or a simple type (which has been created while mapping).
     *   This is the normal case.
     * - NULL, indicating that this object should *not* be mapped (i.e. a "File Upload" Converter could return NULL if no file has been uploaded, and a silent failure should occur.
     * - An instance of \Neos\Error\Messages\Error -- This will be a user-visible error message later on.
     * Furthermore, it should throw an Exception if an unexpected failure (like a security error) occurred or a configuration issue happened.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return mixed|\Neos\Error\Messages\Error the target type, or an error object if a user-error occurred
     * @throws \Neos\Flow\Property\Exception\TypeConverterException thrown in case a developer error occurred
     * @api
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = array(),
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        return is_null($source['data']) ? null : $this->convertIdentifierProperties($source['data']);
    }

}