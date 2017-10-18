<?php
namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Netlogix\JsonApiOrg\Property\TypeConverter\SchemaResource\ResourceConverter;
use Netlogix\JsonApiOrg\Schema;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Utility\TypeHandling;

/**
 * The resource mapper converts any internal payload to
 * an exposable resource object.
 *
 * @Flow\Scope("singleton")
 */
class ResourceMapper
{

    /**
     * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var \Neos\Flow\Reflection\ReflectionService
     * @Flow\Inject
     */
    protected $reflectionService;

    /**
     * @var \Neos\Flow\Property\PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * @var \Netlogix\JsonApiOrg\Resource\Information\ExposableTypeMapInterface
     * @Flow\Inject
     */
    protected $exposableTypeMap;

    /**
     * @var array
     * @Flow\InjectConfiguration(package="Neos.Flow", path="http")
     */
    protected $flowHttpSettings;

    /**
     * @var array<\Netlogix\JsonApiOrg\Resource\Information\ResourceInformationInterface>
     */
    protected $resourceInformation = array();

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * Lifecycle method, called after all dependencies have been injected.
     * Here, the Dto\Converter array gets initialized.
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->initializeControllerContext();
        $this->initializeConverters();
    }

    /**
     * @return ControllerContext
     */
    public function getControllerContext()
    {
        return $this->controllerContext;
    }

    /**
     * Returns the resource information with the highest priority being capable
     * of dealing with the given $payload.
     *
     * The controllerContext is added to the resource information object to allow
     * it to create proper URIs.
     *
     * @todo Use runtime cache based on spl_object_hash
     * @param mixed $payload
     * @return \Netlogix\JsonApiOrg\Resource\Information\ResourceInformationInterface
     */
    public function findResourceInformation($payload)
    {

        if (is_array($payload) && isset($payload['type']) && isset($payload['id'])) {
            $payload = $this->propertyMapper->convert((string)$payload['id'],
                $this->exposableTypeMap->getClassName($payload['type']));
        }

        /** @var ResourceInformationInterface $resourceInformation */
        foreach ($this->resourceInformation as $resourceInformation) {
            if ($resourceInformation->canHandle($payload)) {
                return $resourceInformation;
            }
        }
        return null;
    }

    /**
     * For any given $payload, the array structure holding the
     * public type identifier as well as the resource id is returned.
     *
     * @param mixed $payload
     * @return array
     * @see http://jsonapi.org/format/#document-resource-identifier-objects
     */
    public function getDataIdentifierForPayload($payload)
    {
        $resourceInformation = $this->findResourceInformation($payload);
        $resource = $resourceInformation->getResource($payload);

        return array(
            'type' => $this->exposableTypeMap->getType($resource->getType()),
            'id' => $resource->getId()
        );
    }

    /**
     * If a proper data identifier structure is given, the corresponding
     * payload is returned.
     *
     * @param mixed $dataIdentifier
     * @return mixed
     * @see http://jsonapi.org/format/#document-resource-identifier-objects
     */
    public function getPayloadForDataIdentifier($dataIdentifier)
    {
        if ($dataIdentifier === null) {
            return null;
        }
        $resourceConverter = new ResourceConverter();
        /** @var Schema\Resource $resource */
        $resource = $resourceConverter->convertFrom($dataIdentifier, Schema\PersistentResource::class);

        return $resource->getPayload();
    }

    /**
     * @param mixed $payload
     * @return \Neos\Flow\Http\Uri
     * @see ResourceInformationInterface::getPublicResourceUri()
     */
    public function getPublicResourceUri($payload)
    {
        $resourceInformation = $this->findResourceInformation($payload);

        return $resourceInformation->getPublicResourceUri($payload);
    }

    /**
     * The Resource Information most likely needs an UriBuilder, so having a
     * ControllerContext in place might come in handy.
     *
     * @return void
     */
    protected function initializeControllerContext()
    {

        $request = new ActionRequest(Request::createFromEnvironment());
        $request->setDispatched(true);
        if (isset($this->flowHttpSettings['baseUri'])) {
            $request->getHttpRequest()->setBaseUri(new Uri($this->flowHttpSettings['baseUri']));
        }

        $response = new Response();

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        $arguments = new Arguments(array());

        $this->controllerContext = new ControllerContext($request, $response, $arguments, $uriBuilder);
    }

    /**
     * @return void
     */
    protected function initializeConverters()
    {

        $this->resourceInformation = array();

        foreach ($this->reflectionService->getAllImplementationClassNamesForInterface(ResourceInformationInterface::class) as $resourceInformationClassName) {
            $this->resourceInformation[] = $this->objectManager->get($resourceInformationClassName);
        }
        usort($this->resourceInformation,
            function (ResourceInformationInterface $first, ResourceInformationInterface $second) {
                if ($first->getPriority() == $second->getPriority()) {
                    return strcmp(TypeHandling::getTypeForValue($first), TypeHandling::getTypeForValue($second));
                } else {
                    return $first->getPriority() < $second->getPriority();
                }
            });
    }

}