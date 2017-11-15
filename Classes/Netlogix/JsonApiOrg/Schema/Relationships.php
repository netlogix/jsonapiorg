<?php
namespace Netlogix\JsonApiOrg\Schema;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;
use Netlogix\JsonApiOrg\Domain\Model\RelatedLinksAwareModelInterface;
use Netlogix\JsonApiOrg\Domain\Model\RelatedLinksAwareResourceInterface;
use Netlogix\JsonApiOrg\Resource\Information\LinksAwareResourceInformationInterface;
use Netlogix\JsonApiOrg\Resource\Information\MetaAwareResourceInformationInterface;
use Netlogix\JsonApiOrg\Schema\Traits\IncludeFieldsTrait;
use Netlogix\JsonApiOrg\Schema\Traits\ResourceBasedTrait;
use Netlogix\JsonApiOrg\Schema\Traits\SparseFieldsTrait;

/**
 * @see http://jsonapi.org/format/#document-resource-object-relationships
 */
class Relationships extends AbstractSchemaElement implements \IteratorAggregate, \ArrayAccess
{

    use ResourceBasedTrait;
    use SparseFieldsTrait;
    use IncludeFieldsTrait;

    const RELATIONSHIP_TYPE_SINGLE = 'single';
    const RELATIONSHIP_TYPE_COLLECTION = 'collection';

    /**
     * @var array
     */
    protected $jsonSerializeCache = [];

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $identifier = serialize([$this->sparseFields, $this->includeFields]);

        if (array_key_exists($identifier, $this->jsonSerializeCache)) {
            return $this->jsonSerializeCache[$identifier];
        }

        $this->jsonSerializeCache[$identifier] = [];
        foreach ($this->getRelationshipsToBeApiExposed() as $fieldName => $relationshipType) {

            if (!$this->isAllowedSparseField($fieldName)) {
                continue;
            }

            if (!$this->isAllowedIncludeField($fieldName)) {
                $this->jsonSerializeCache[$identifier][$fieldName] = $this->getBasicResourceRelationshipValue($fieldName, $relationshipType);

            } elseif ($relationshipType === Relationships::RELATIONSHIP_TYPE_SINGLE) {
                $this->jsonSerializeCache[$identifier][$fieldName] = $this->getResourceSingleRelationshipValue($fieldName);

            } elseif ($relationshipType === Relationships::RELATIONSHIP_TYPE_COLLECTION) {
                $this->jsonSerializeCache[$identifier][$fieldName] = $this->getResourceCollectionRelationshipValue($fieldName);

            }
        }

        return $this->jsonSerializeCache[$identifier];
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->jsonSerialize());
    }

    /**
     * @param string $fieldName
     * @param string $relationshipType
     * @return array
     */
    protected function getBasicResourceRelationshipValue($fieldName, $relationshipType)
    {
        return array_filter([
            'links' => $this->getLinksPayloadForResource($fieldName, $relationshipType),
            'meta' => $this->getMetaPayloadForResource($fieldName, $relationshipType),
        ]);
    }

    /**
     * @param $fieldName
     * @return array
     */
    protected function getResourceSingleRelationshipValue($fieldName)
    {
        $result = array_merge($this->getBasicResourceRelationshipValue($fieldName, Relationships::RELATIONSHIP_TYPE_SINGLE), array('data' => null));

        $relationship = $this->getResource()->getPayloadProperty($fieldName);
        if (!is_null($relationship)) {
            $result['data'] = $this->resourceMapper->getDataIdentifierForPayload($relationship);
        }

        if (is_null($result['data'])) {
            unset($result['links']['related']);
        }

        return $result;
    }

    /**
     * @param $fieldName
     * @param $value
     */
    protected function setResourceSingleRelationshipValue($fieldName, $value)
    {
        if (!array_key_exists('data', $value)) {
            return;
        }
        $relationship = $this->resourceMapper->getPayloadForDataIdentifier($value['data']);
        $this->getResource()->setPayloadProperty($fieldName, $relationship);
    }

    /**
     * @param string $fieldName
     * @return mixed
     */
    protected function getResourceCollectionRelationshipValue($fieldName)
    {
        $result = array_merge($this->getBasicResourceRelationshipValue($fieldName), array('data' => array()));

        foreach ($this->getResource()->getPayloadProperty($fieldName) as $relationship) {
            if (!is_null($relationship)) {
                $result['data'][] = $this->resourceMapper->getDataIdentifierForPayload($relationship);
            }
        }

        return $result;
    }

    /**
     * @param string $fieldName
     * @param array $value
     */
    protected function setResourceCollectionRelationshipValue($fieldName, $value)
    {
        if (!array_key_exists('data', $value)) {
            return;
        }

        $payload = $this->getPayload();

        $collection = array();
        foreach ($value['data'] as $relationship) {
            $collection[] = $this->resourceMapper->getPayloadForDataIdentifier($relationship);
        }

        $existingCollection = $this->getResource()->getPayloadProperty($fieldName);
        if (is_object($existingCollection) && $existingCollection instanceof Collection) {
            $this->getResource()->setPayloadProperty($fieldName, new ArrayCollection($collection));
        } else {
            $this->getResource()->setPayloadProperty($fieldName, $collection);
        }

    }


    protected function getLinksPayloadForResource($fieldName, $relationshipType)
    {
        $result = [];

        $payload = $this->getPayload();
        $resourceInformation = $this->getResourceInformation();

        if ($resourceInformation instanceof LinksAwareResourceInformationInterface) {
            foreach ($resourceInformation->getLinksForRelationship($payload, $fieldName, $relationshipType) as $key => $value) {
                $result[$key] = $value;
            }
        }

        if (!array_key_exists('self', $result)) {
            try {
                $result['self'] = (string)$this->getResourceInformation()->getPublicRelationshipUri($payload, $fieldName);
            } catch (\Exception $e) {
            }
        }
        if (!array_key_exists('related', $result)) {
            try {
                $result['related'] = (string)$this->getResourceInformation()->getPublicRelatedUri($payload, $fieldName);
            } catch (\Exception $e) {
            }
        }

        return array_filter($result);
    }

    protected function getMetaPayloadForResource($fieldName, $relationshipType)
    {
        $result = [];

        $payload = $this->getPayload();
        $resourceInformation = $this->getResourceInformation();

        if ($resourceInformation instanceof MetaAwareResourceInformationInterface) {
            foreach ($resourceInformation->getMetaForRelationship($payload, $fieldName, $relationshipType) as $key => $value) {
                $result[$key] = $value;
            }
        }

        return array_filter($result);
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    public function offsetExists($fieldName)
    {
        return isset($this->getRelationshipsToBeApiExposed()[$fieldName]);
    }

    /**
     * @param string $fieldName
     * @return array
     */
    public function offsetGet($fieldName)
    {
        if ($this->offsetExists($fieldName)) {
            switch ($this->getRelationshipsToBeApiExposed()[$fieldName]) {
                case Relationships::RELATIONSHIP_TYPE_SINGLE:
                    return $this->getResourceSingleRelationshipValue($fieldName);
                case Relationships::RELATIONSHIP_TYPE_COLLECTION:
                    return $this->getResourceCollectionRelationshipValue($fieldName);
            }
        }
        return null;
    }

    /**
     * @param string $fieldName
     * @param mixed $value
     */
    public function offsetSet($fieldName, $value)
    {
        if ($this->offsetExists($fieldName)) {
            switch ($this->getRelationshipsToBeApiExposed()[$fieldName]) {
                case Relationships::RELATIONSHIP_TYPE_SINGLE:
                    $this->setResourceSingleRelationshipValue($fieldName, $value);
                    break;
                case Relationships::RELATIONSHIP_TYPE_COLLECTION:
                    $this->setResourceCollectionRelationshipValue($fieldName, $value);
                    break;
            }
        }
    }

    /**
     * @param string $fieldName
     */
    public function offsetUnset($fieldName)
    {
        $this->offsetSet($fieldName, null);
    }

}