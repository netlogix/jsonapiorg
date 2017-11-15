<?php
namespace Netlogix\JsonApiOrg\Schema\Traits;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Netlogix\JsonApiOrg\Schema;

/**
 * Some jsonapi.org objects, such as attributes, relationships and
 * links, need to access both, the connected resource as well as
 * the internal payload.
 */
trait ResourceBasedTrait
{
    /**
     * @var Schema\Resource
     */
    protected $resource;

    /**
     * Resource constructor.
     *
     * @param Schema\Resource $resource
     */
    public function __construct(Schema\Resource $resource = null)
    {
        $this->resource = $resource;
    }

    /**
     * @return Schema\Resource
     */
    protected function getResource()
    {
        return $this->resource;
    }

    /**
     * @return \Netlogix\JsonApiOrg\Resource\Information\ResourceInformation
     */
    protected function getResourceInformation()
    {
        return $this->resource ? $this->resource->getResourceInformation() : null;
    }

    /**
     * @return mixed
     */
    protected function getPayload()
    {
        return $this->resource ? $this->resource->getPayload() : null;
    }

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
    protected function getAttributesToBeApiExposed()
    {
        return $this->resource ? $this->resource->getAttributesToBeApiExposed() : [];
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
    protected function getRelationshipsToBeApiExposed()
    {
        return $this->resource ? $this->resource->getRelationshipsToBeApiExposed() : [];
    }

}