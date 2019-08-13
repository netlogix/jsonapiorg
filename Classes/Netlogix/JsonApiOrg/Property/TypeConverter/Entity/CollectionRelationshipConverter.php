<?php

namespace Netlogix\JsonApiOrg\Property\TypeConverter\Entity;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

/**
 */
class CollectionRelationshipConverter extends AbstractSchemaResourceBasedEntityConverter
{
    /**
     * @var integer
     */
    protected $priority = 1457600399;

    /**
     * @var array
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = Collection::class;

    public function canConvertFrom($source, $targetType)
    {
        if (!is_array($source)
            || !array_key_exists('data', $source)
            || !is_array($source['data'])
        ) {
            return false;
        }
        foreach ($source['data'] as $data) {
            if (!$this->hasOnlyIdentifierProperties($data)) {
                return false;
            }
        }
        return true;
    }

    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        return new ArrayCollection(
            array_map(
                function ($data) {
                    return $this->convertIdentifierProperties($data);
                },
                $source['data']
            )
        );
    }

}