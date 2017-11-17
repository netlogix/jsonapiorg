<?php
namespace Netlogix\JsonApiOrg\Schema;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Netlogix\JsonApiOrg\Resource\Information\ResourceInformationInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @see http://jsonapi.org/format/#document-resource-objects
 */
abstract class Resource extends AbstractSchemaElement implements ResourceInterface
{

    /**
     * @var Object
     */
    protected $payload;

    /**
     * @var ResourceInformationInterface
     */
    protected $resourceInformation;

    /**
     * @var Attributes
     */
    protected $attributes;

    /**
     * @var Links
     */
    protected $links;

    /**
     * @var Relationships
     */
    protected $relationships;

    /**
     * @var Meta
     */
    protected $meta;

    /**
     * Resource constructor.
     *
     * @param $payload
     * @param ResourceInformationInterface $resourceInformation
     */
    public function __construct(
        $payload,
        ResourceInformationInterface $resourceInformation = null
    ) {
        $this->payload = $payload;
        $this->resourceInformation = $resourceInformation;

        $this->attributes = new Attributes($this);
        $this->links = new Links($this);
        $this->relationships = new Relationships($this);
        $this->meta = new Meta($this);
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return ResourceInformationInterface
     */
    public function getResourceInformation()
    {
        return $this->resourceInformation;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $result = $this->resourceMapper->getDataIdentifierForPayload($this->getPayload());

        foreach (array('attributes', 'links', 'relationships', 'meta') as $optionalField) {
            $optionalValue = json_decode(json_encode($this->{$optionalField}), true);
            if ($optionalValue) {
                $result[$optionalField] = $optionalValue;
            }
        }

        return $result;
    }

    /**
     * @return Attributes
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return Links
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @return Relationships
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * @return Meta
     */
    public function getMeta()
    {
        return $this->meta;
    }

}