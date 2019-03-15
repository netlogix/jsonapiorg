<?php
namespace Netlogix\JsonApiOrg\Resource;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\Collection;
use Netlogix\JsonApiOrg\Schema\Relationships;
use Netlogix\JsonApiOrg\Schema\ResourceInterface;
use Netlogix\JsonApiOrg\Schema\TopLevel;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryResultInterface;

/**
 * The RelationshipIterator is basically a factory of TopLevel objects
 * holding both, the data as well as its relationships.
 */
class RelationshipIterator
{

    /**
     * @var \Netlogix\JsonApiOrg\Resource\Information\ExposableTypeMapInterface
     * @Flow\Inject
     */
    protected $exposableTypeMap;

    /**
     * The supported media types information is used for both, providing
     * an Accept header to sub requests as well as validating responses.
     *
     * @var array
     */
    protected $supportedMediaTypes = array(
        'application/vnd.api+json'
    );

    /**
     * @var array
     */
    protected $include = array();

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @var \Netlogix\JsonApiOrg\Resource\Information\ResourceMapper
     * @Flow\Inject
     */
    protected $resourceMapper;

    /**
     * @var \Netlogix\JsonApiOrg\Resource\Resolver\ResourceResolverInterface
     * @Flow\Inject
     */
    protected $resourceResolver;

    /**
     * @var RequestStack
     */
    protected $stack;

    /**
     * @param mixed $resource
     * @return TopLevel
     */
    public function createTopLevel($resource)
    {

        $this->initializeResourceResolver();
        $this->initializeStack($resource);

        $this->traverseRelationships();

        return $this->createResult(!$this->isArray($resource));
    }

    /**
     *
     */
    protected function initializeResourceResolver()
    {
        $this->resourceResolver->setSupportedMediaTypes($this->getSupportedMediaTypes());
    }

    /**
     * @param $resource
     */
    protected function initializeStack($resource)
    {
        $this->stack = new RequestStack();
        if ($this->isArray($resource)) {
            foreach ($resource as $singleResource) {
                $this->stack->push($singleResource, RequestStack::POSITION_DATACOLLECTION);
            }
        } else {
            $this->stack->push($resource, RequestStack::POSITION_DATA);
        }
    }

    /**
     *
     */
    protected function traverseRelationships()
    {
        while ($resourceWorkloadPackage = $this->stack->pop()) {
            $this->fetchResourceContentAndQueueRelationships($resourceWorkloadPackage);
        }
    }

    /**
     * @param bool $singleResource
     * @return TopLevel
     */
    protected function createResult($singleResource)
    {

        $result = new TopLevel($singleResource);

        foreach ($this->stack->getResults() as $resourceWorkloadPackage) {
            if ($resourceWorkloadPackage[RequestStack::RESULT_DATA]) {
                switch ($resourceWorkloadPackage[RequestStack::RESULT_POSITION]) {
                    case RequestStack::POSITION_DATA:
                        $result->setData($resourceWorkloadPackage[RequestStack::RESULT_DATA]);
                        break;
                    case RequestStack::POSITION_DATACOLLECTION:
                        $result->addData($resourceWorkloadPackage[RequestStack::RESULT_DATA]);
                        break;
                    case RequestStack::POSITION_INCLUDE:
                        $result->addIncluded($resourceWorkloadPackage[RequestStack::RESULT_DATA]);
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $resourceWorkloadPackage
     */
    protected function fetchResourceContentAndQueueRelationships(array $resourceWorkloadPackage)
    {
        $object = $resourceWorkloadPackage[RequestStack::RESULT_RESOURCE];
        $resource = $this->resourceMapper->findResourceInformation($object)->getResource($object);
        $nestingPaths = $this->stack->getNestingPaths($object);

        $effectiveFields = $this->getEffectiveFieldsForResource($resource);
        if (!is_null($effectiveFields)) {
            $resource->getAttributes()->setSparseFields($effectiveFields);
            $resource->getRelationships()->setSparseFields($effectiveFields);
        }

        $effectiveInclude = $this->getEffectiveIncludeForNestingPaths($nestingPaths);
        $resource->getRelationships()->setIncludeFields($effectiveInclude);

        $this->stack->finalize($object, $resource);

        $relationshipsToBeApiExposed = $resource->getRelationshipsToBeApiExposed();

        foreach ($resource->getRelationships() as $relationshipName => $relationshipContent) {

            if (!$resource->getRelationships()->isAllowedIncludeField($relationshipName)) {
                continue;
            }

            $relationshipNestingPaths = $this->enhanceNestingPaths($nestingPaths, $relationshipName);
            if (!is_array($relationshipContent['data'])) {
                continue;
            }

            switch ($relationshipsToBeApiExposed[$relationshipName]) {
                case Relationships::RELATIONSHIP_TYPE_SINGLE:
                    $this->pushRelation($relationshipContent['data'], $relationshipNestingPaths);
                    break;

                case Relationships::RELATIONSHIP_TYPE_COLLECTION:
                    foreach ($relationshipContent['data'] as $relation) {
                        $this->pushRelation($relation, $relationshipNestingPaths);
                    }
                    break;

            }
        }
    }

    /**
     * @param array <string> $nestingPaths
     * @return array<string>
     */
    protected function getEffectiveIncludeForNestingPaths(array $nestingPaths)
    {
        $effectiveInclude = array();
        foreach ($nestingPaths as $nestingPath) {
            foreach ($this->getInclude() as $include) {
                if ($nestingPath === '') {
                    $prefixFreeInclude = $include;
                } elseif ($include !== $nestingPath && strpos($include, $nestingPath) === 0) {
                    $prefixFreeInclude = substr($include, strlen($nestingPath) + 1);
                } else {
                    continue;
                }
                $include = current(explode('.', $prefixFreeInclude));
                $effectiveInclude[$include] = $include;
            }
        }

        return $effectiveInclude;
    }

    /**
     * @param ResourceInterface $resource
     * @return mixed
     */
    protected function getEffectiveFieldsForResource(ResourceInterface $resource)
    {
        $type = $this->exposableTypeMap->getType($resource->getType());
        if (array_key_exists($type, $this->fields)) {
            return $this->fields[$type];
        }
        return null;
    }

    /**
     * @param array <string> $nestingPaths
     * @param string $relationshipName
     * @return array<string>
     */
    protected function enhanceNestingPaths($nestingPaths, $relationshipName)
    {
        $relationshipNestingPaths = array();
        foreach ($nestingPaths as $nestingPath) {
            $nestingPath = ltrim($nestingPath . '.' . $relationshipName, '.');
            $relationshipNestingPaths[$nestingPath] = $nestingPath;
        }

        return $relationshipNestingPaths;
    }

    /**
     * @param array $relation
     * @param array $relationshipNestingPaths
     */
    protected function pushRelation($relation, $relationshipNestingPaths)
    {
        foreach ($relationshipNestingPaths as $nestingPath) {
            $this->stack->pushIdentifier($relation, RequestStack::POSITION_INCLUDE, $nestingPath);
        }
    }

    /**
     * @param array <string> $supportedMediaTypes
     */
    public function setSupportedMediaTypes(array $supportedMediaTypes)
    {
        $this->supportedMediaTypes = $supportedMediaTypes;
    }

    /**
     * @return array<string>
     */
    public function getSupportedMediaTypes()
    {
        return $this->supportedMediaTypes;
    }

    /**
     * @param array <string> $include
     */
    public function setInclude(array $includes)
    {
        $this->include = array();
        foreach ($includes as $include) {
            $include = (string)$include;
            $this->include[$include] = $include;
        }
    }

    /**
     * @return array
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * @param array <array<string>> $include
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param mixed $resource
     * @return bool
     */
    private function isArray($resource)
    {
        return is_array($resource) || (is_object($resource) && $resource instanceof Collection) || (is_object($resource) && $resource instanceof QueryResultInterface);
    }

}