<?php
namespace Netlogix\JsonApiOrg\Schema;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * @see http://jsonapi.org/format/#document-resource-objects
 */
interface ResourceInterface extends \JsonSerializable
{

    /**
     * Those attribute names of the underlying $payload are exposed to the public
     * and thus available for reading and writing.
     *
     * This very array is just a collection of strings, each of them targeting
     * a property of the $payload.
     *
     * To expose actual $payload values, an additioanl Attributes object is used
     * just as proposed by the jsonapi.org schema.
     *
     * @return array<string>
     */
    public function getAttributesToBeApiExposed();

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
    public function getRelationshipsToBeApiExposed();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getId();

    /**
     * @return \Netlogix\JsonApiOrg\Resource\Information\ResourceInformation
     */
    public function getResourceInformation();

    /**
     * @return mixed
     */
    public function getPayload();

    /**
     * @return Attributes
     */
    public function getAttributes();

    /**
     * @return Links
     */
    public function getLinks();

    /**
     * @return Relationships
     */
    public function getRelationships();

    /**
     * @return Meta
     */
    public function getMeta();

    /**
     * @param string $propertyName
     * @return mixed
     */
    public function getPayloadProperty($propertyName);

    /**
     * @param string $propertyName
     * @param $value
     */
    public function setPayloadProperty($propertyName, $value);

}