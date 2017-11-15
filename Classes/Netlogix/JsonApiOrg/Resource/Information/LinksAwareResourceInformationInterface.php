<?php
namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 */

interface LinksAwareResourceInformationInterface extends ResourceInformationInterface
{
    /**
     * Provide links for a payload object.
     *
     * The self link gets automatically created if this method does not return one.
     *
     * @param mixed $payload
     * @return array
     */
    public function getLinksForPayload($payload);

    /**
     * Provide links for a relationship of a payload object.
     *
     * The self and related links get automatically created if this method does not return them.
     *
     * @param mixed $payload
     * @param string $relationshipName
     * @param string $relationshipType
     * @return array
     */
    public function getLinksForRelationship($payload, $relationshipName, $relationshipType = null);
}
