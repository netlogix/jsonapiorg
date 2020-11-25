<?php
namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 */

interface MetaAwareResourceInformationInterface extends ResourceInformationInterface
{
    /**
     * Provide meta data for a payload object.
     *
     * @param mixed $payload
     * @return array
     */
    public function getMetaForPayload($payload);

    /**
     * Provide meta data for a relationship of a payload object.
     *
     * @param mixed $payload
     * @param string $relationshipName
     * @param null $relationshipType
     * @param bool $included
     * @return array
     */
    public function getMetaForRelationship($payload, $relationshipName, $relationshipType = null, $included = false);
}
