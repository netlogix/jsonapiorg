<?php
namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The ResourceInformation is a mapping schema for bringing
 * Resource objects, payload objects and ActionControllers together.
 *
 * So a ResourceInformation object always brings:
 *
 * - The resource class name
 * - The payload class name
 * - Method arguments for calling an UriBuilder properly
 */
interface ResourceInformationInterface
{

    /**
     * Return the priority of this DtoConverter. DtoConverters with a high priority are chosen before low priority.
     *
     * @return int
     */
    public function getPriority();

    /**
     * Here, the DtoConverter can do some additional runtime checks to see whether
     * it can handle the given source data.
     *
     * @param mixed $payload the source data
     * @return boolean TRUE if this DtoConverter can handle the $source, FALSE otherwise.
     */
    public function canHandle($payload);

    /**
     * @param mixed $payload
     * @return \Netlogix\JsonApiOrg\Schema\ResourceInterface
     */
    public function getResource($payload);

    /**
     * For every $resource to be handled, a Converter needs to be able to create
     * a public URI pointing at the index action.
     * So the Converter is used for both, exposing the API of a distinct object
     * type to the public as well as creating internal sub requests for related
     * objects.
     *
     * @param mixed $resource
     * @return \Neos\Flow\Http\Uri
     */
    public function getPublicResourceUri($resource);

    /**
     * For every $resource to be handled, a Converter needs to be able to create
     * a public URI pointing at an action showing information about an individual
     * relationship.
     *
     * @param mixed $payload
     * @param string $relationshipName
     * @return \Neos\Flow\Http\Uri
     */
    public function getPublicRelatedUri($payload, $relationshipName);

    /**
     * @param mixed $payload
     * @return array
     */
    public function getResourceControllerArguments($payload);

}