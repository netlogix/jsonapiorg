<?php
namespace Netlogix\JsonApiOrg\Property\TypeConverter\Entity;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\Exception\FormatNotSupportedException;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;

/**
 */
class PersistentObjectConverter extends AbstractSchemaResourceBasedEntityConverter
{
    /**
     * @var integer
     */
    protected $priority = 1457600398;

    /**
     * @var \Netlogix\JsonApiOrg\Resource\Information\ResourceMapper
     * @Flow\Inject
     */
    protected $resourceMapper;

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
        if (!is_array($source) || !array_key_exists('type', $source)) {
            return false;
        }
        try {
            $className = $this->exposableTypeMap->getClassName($source['type']);
        } catch (FormatNotSupportedException $e) {
            return false;
        }
        if ($className !== $targetType && !is_subclass_of($className, $targetType) && !is_subclass_of($targetType,
                $className)
        ) {
            return false;
        }

        return !!$this->resourceMapper->findResourceInformation($className);
    }

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * The return value can be one of three types:
     * - an arbitrary object, or a simple type (which has been created while mapping).
     *   This is the normal case.
     * - NULL, indicating that this object should *not* be mapped (i.e. a "File Upload" Converter could return NULL if no file has been uploaded, and a silent failure should occur.
     * - An instance of \TYPO3\Flow\Error\Error -- This will be a user-visible error message later on.
     * Furthermore, it should throw an Exception if an unexpected failure (like a security error) occurred or a configuration issue happened.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return mixed|\TYPO3\Flow\Error\Error the target type, or an error object if a user-error occurred
     * @throws \TYPO3\Flow\Property\Exception\TypeConverterException thrown in case a developer error occurred
     * @api
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = array(),
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        $arguments = [];
        if (array_key_exists('attributes', $source)) {
            $arguments = array_merge($arguments, $source['attributes']);
        }
        if (array_key_exists('relationships', $source)) {
            $arguments = array_merge($arguments, $source['relationships']);
        }
        if (array_key_exists('id', $source)) {
            $arguments['__identity'] = $source['id'];
        }

        return $this->propertyMapper->convert($arguments, $this->exposableTypeMap->getClassName($source['type']));
    }

}