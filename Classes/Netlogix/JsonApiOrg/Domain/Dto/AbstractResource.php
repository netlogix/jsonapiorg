<?php
namespace Netlogix\JsonApiOrg\Domain\Dto;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Netlogix\JsonApiOrg\Schema\Resource;
use Netlogix\JsonApiOrg\Schema\ResourceInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\TypeHandling;

/**
 * A default/template implementation of a custom resource.
 *
 * @package Netlogix\JsonApiOrg\Domain\Model\Resource
 */
abstract class AbstractResource extends Resource implements ResourceInterface
{

    /**
     * @var \Neos\Flow\Persistence\PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * Those attribute names of the underlying $payload are exposed to the public
     * and thus available for reading and writing.
     *
     * This very array is just a collection of strings, each of them targeting
     * a property of the $payload.
     *
     * To expose actual $payload values, an additional Attributes object is used
     * just as proposed by the jsonapi.org schema.
     *
     * @var array<string>
     */
    protected $attributesToBeApiExposed = array();

    /**
     * Those relationship names of the underlying $payload are exposed to the public
     * and thus available for reading and writing.
     *
     * This very array maps a relationship name to a type of the relationship, where
     * the type is either "collection" or "single".
     *
     * To expose actual $payload values, an additional Relationships object is used
     * just as proposed by the jsonapi.org schema.
     *
     * Example:
     *   array(
     *     'parent' => 'single',
     *     'self' => 'single',
     *     'children' => 'collection'
     *   )
     *
     * @var array<string>
     */
    protected $relationshipsToBeApiExposed = array();

    /**
     * Those attribute names of the underlying $payload are exposed to the public
     * and thus available for reading and writing.
     *
     * This very array is just a collection of strings, each of them targeting
     * a property of the $payload.
     *
     * To expose actual $payload values, an additional Attributes object is used
     * just as proposed by the jsonapi.org schema.
     *
     * @return array<string>
     */
    public function getAttributesToBeApiExposed()
    {
        return $this->attributesToBeApiExposed;
    }

    /**
     * Those relationship names of the underlying $payload are exposed to the public
     * and thus available for reading and writing.
     *
     * This very array maps a relationship name to a type of the relationship, where
     * the type is either "collection" or "single".
     *
     * To expose actual $payload values, an additional Relationships object is used
     * just as proposed by the jsonapi.org schema.
     *
     * Example:
     *   array(
     *     'parent' => 'single',
     *     'self' => 'single',
     *     'children' => 'collection'
     *   )
     *
     * @return array<string>
     */
    public function getRelationshipsToBeApiExposed()
    {
        return $this->relationshipsToBeApiExposed;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return TypeHandling::getTypeForValue($this->getPayload());
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->persistenceManager->getIdentifierByObject($this->getPayload());
    }

    /**
     * @param string $propertyName
     * @return mixed
     * @throws \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function getPayloadProperty($propertyName)
    {
        return \Neos\Utility\ObjectAccess::getProperty($this->getPayload(), $propertyName);
    }

    /**
     * @param string $propertyName
     * @param $value
     */
    public function setPayloadProperty($propertyName, $value)
    {
        $payload = $this->getPayload();
        \Neos\Utility\ObjectAccess::setProperty($payload, $propertyName, $value);
    }

}