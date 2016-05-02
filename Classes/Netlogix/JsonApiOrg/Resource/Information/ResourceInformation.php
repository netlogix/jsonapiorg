<?php
namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Netlogix\JsonApiOrg\Domain\Dto\AbstractResource;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Uri;

/**
 * The ResourceInformation is a mapping schema for bringing
 * Resource objects, payload objects and ActionControllers together.
 *
 * So a ResourceInformation object always brings:
 *
 * - The resource class name
 * - The payload class name
 * - Method arguments for calling an UriBuilder properly
 *
 * @Flow\Scope("singleton")
 */
abstract class ResourceInformation
{

    /**
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var \Netlogix\JsonApiOrg\Resource\Information\ResourceMapper
     * @Flow\Inject
     */
    protected $resourceMapper;

    /**
     * @var \Netlogix\JsonApiOrg\Resource\Information\ExposableTypeMapInterface
     * @Flow\Inject
     */
    protected $exposableTypeMap;

    /**
     * @var int
     */
    protected $priority = 0;

    /**
     * @var string
     */
    protected $resourceClassName = AbstractResource::class;

    /**
     * @var string
     */
    protected $payloadClassName = '';

    /**
     * @var string
     */
    protected $format = 'json';

    /**
     * @var string
     */
    protected $resourceControllerActionName = 'index';

    /**
     * @var
     */
    protected $relatedControllerActionName = 'showRelated';

    /**
     * @var string
     */
    protected $controllerName = '';

    /**
     * @var string
     */
    protected $packageKey = '';

    /**
     * @var string
     */
    protected $subPackageKey = null;

    /**
     * Return the priority of this DtoConverter. DtoConverters with a high priority are chosen before low priority.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Here, the DtoConverter can do some additional runtime checks to see whether
     * it can handle the given source data.
     *
     * @param mixed $payload the source data
     * @return boolean TRUE if this DtoConverter can handle the $source, FALSE otherwise.
     */
    public function canHandle($payload)
    {
        if (is_object($payload) && is_a($payload, $this->payloadClassName)) {
            return true;
        } elseif (is_string($payload) && is_subclass_of($payload, $this->payloadClassName)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param mixed $payload
     * @return \Netlogix\JsonApiOrg\Schema\Resource
     */
    public function getResource($payload)
    {
        return $this->objectManager->get($this->resourceClassName, $payload, $this);
    }

    /**
     * For every $resource to be handled, a Converter needs to be able to create
     * a public URI pointing at the index action.
     * So the Converter is used for both, exposing the API of a distinct object
     * type to the public as well as creating internal sub requests for related
     * objects.
     *
     * @param mixed $resource
     * @return Uri
     */
    public function getPublicResourceUri($resource)
    {
        return $this->getPublicUri($resource, $this->resourceControllerActionName,
            $this->getResourceControllerArguments($resource));
    }

    /**
     * For every $resource to be handled, a Converter needs to be able to create
     * a public URI pointing at an action showing information about an individual
     * relationship.
     *
     * @param mixed $resource
     * @param string $relationshipName
     *
     * @return Uri
     */
    public function getPublicRelationshipUri($resource, $relationshipName)
    {
        return $this->getPublicUri($resource, $this->resourceControllerActionName,
            $this->getRelationshipControllerArguments($resource, $relationshipName));
    }

    /**
     * For every $resource to be handled, a Converter needs to be able to create
     * a public URI pointing at an action showing information about an individual
     * relationship.
     *
     * @param mixed $resource
     * @param string $relationshipName
     *
     * @return Uri
     */
    public function getPublicRelatedUri($resource, $relationshipName)
    {
        return $this->getPublicUri($resource, $this->relatedControllerActionName,
            $this->getRelationshipControllerArguments($resource, $relationshipName));
    }

    /**
     * @param mixed $resource
     * @param string $controllerActionName
     * @param array $controllerArguments
     * @return Uri
     */
    protected function getPublicUri($resource, $controllerActionName, array $controllerArguments = array())
    {

        $uriBuilder = $this->resourceMapper->getControllerContext()->getUriBuilder();

        $uriBuilder->reset()->setFormat($this->format)->setCreateAbsoluteUri(true);

        $uri = $uriBuilder->uriFor($controllerActionName,
            array_merge($this->getResourceControllerArguments($resource), $controllerArguments), $this->controllerName,
            $this->packageKey, $this->subPackageKey);

        return new Uri($uri);
    }

    /**
     * @param mixed $resource
     * @return array
     */
    public function getResourceControllerArguments($resource)
    {
        return array(
            'resource' => $resource,
        );
    }

    /**
     * @param mixed $resource
     * @param string $relationshipName
     * @return array
     */
    protected function getRelationshipControllerArguments($resource, $relationshipName)
    {
        return array(
            'resource' => $resource,
            'relationshipName' => $relationshipName
        );
    }

}