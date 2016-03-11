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

/**
 */
abstract class AbstractSchemaResourceBasedEntityConverter extends \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter
{

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
     * All properties in the source array except __identity are sub-properties.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return [];
    }

    /**
     * @param $source
     * @return bool
     */
    protected function hasOnlyIdentifierProperties($source)
    {
        if (!is_array($source)) {
            return false;
        } elseif (count($source) === 1 && array_key_exists('type', $source)) {
            return true;
        } elseif (count($source) === 2 && array_key_exists('type', $source) && array_key_exists('id', $source)) {
            return true;
        }
        return false;
    }

    /**
     * @param $source
     * @return mixed
     */
    protected function convertIdentifierProperties($source)
    {
        $identifier = array_key_exists('id', $source) ? $source['id'] : [];
        return $this->propertyMapper->convert($identifier, $this->exposableTypeMap->getClassName($source['type']));
    }

}