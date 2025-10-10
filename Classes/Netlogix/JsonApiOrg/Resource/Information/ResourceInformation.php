<?php
namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Uri;
use Netlogix\JsonApiOrg\Domain\Dto\AbstractResource;
use Neos\Flow\Annotations as Flow;
use Netlogix\JsonApiOrg\Schema;
use Psr\Http\Message\UriInterface;

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
     * @var \Neos\Flow\Persistence\PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
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
    public function getPriority(): int
    {
        return $this->priority;
    }

    public function canHandle(mixed $payload): bool
    {
        if (is_object($payload) && is_a($payload, $this->payloadClassName)) {
            return true;
        } elseif (is_string($payload) && is_subclass_of($payload, $this->payloadClassName)) {
            return true;
        } else {
            return false;
        }
    }

    public function getResource(mixed $payload): Schema\Resource
    {
        $resource = $this->objectManager->get($this->resourceClassName, $payload, $this);
        assert($resource instanceof Schema\Resource);
        return $resource;
    }

    public function getPublicResourceUri(mixed $resource): Uri
    {
        return $this->getPublicUri(
            $resource,
            $this->resourceControllerActionName,
            $this->getResourceControllerArguments($resource)
        );
    }

    public function getPublicRelationshipUri(mixed $resource, string $relationshipName): Uri
    {
        return $this->getPublicUri(
            $resource,
            $this->resourceControllerActionName,
            $this->getRelationshipControllerArguments($resource, $relationshipName)
        );
    }

    public function getPublicRelatedUri(mixed $resource, string $relationshipName): Uri
    {
        $x = $this->getPublicUri(
            $resource,
            $this->relatedControllerActionName,
            $this->getRelationshipControllerArguments($resource, $relationshipName)
        );
        return $x;
    }

    public function getUriBuilder(): \Neos\Flow\Mvc\Routing\UriBuilder
    {
        $uriBuilder = $this->resourceMapper->getControllerContext()->getUriBuilder();

        $uriBuilder->reset()->setFormat($this->format)->setCreateAbsoluteUri(true);

        return $uriBuilder;
    }

    /**
     * @param array<mixed, mixed> $controllerArguments
     */
    protected function getPublicUri(mixed $resource, string $controllerActionName, array $controllerArguments = array()): UriInterface
    {
        $uriBuilder = $this->getUriBuilder();

        $uri = $uriBuilder->uriFor(
            $controllerActionName,
            array_merge($this->getResourceControllerArguments($resource), $controllerArguments),
            $this->controllerName,
            $this->packageKey,
            $this->subPackageKey
        );

        return new Uri($uri);
    }

    /**
     * @return array<string, mixed>
     */
    public function getResourceControllerArguments(mixed $resource): array
    {
        return array(
            'resource' => $resource,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRelationshipControllerArguments(mixed $resource, string $relationshipName): array
    {
        return array(
            'resource' => $resource,
            'relationshipName' => $relationshipName,
        );
    }

}