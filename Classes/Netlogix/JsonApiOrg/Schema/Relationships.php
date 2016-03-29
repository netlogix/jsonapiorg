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
use Netlogix\JsonApiOrg\Schema\Traits\IncludeFieldsTrait;
use Netlogix\JsonApiOrg\Schema\Traits\ResourceBasedTrait;
use Netlogix\JsonApiOrg\Schema\Traits\SparseFieldsTrait;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;

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
     * @return array
     */
    public function jsonSerialize()
    {
        $result = array();
        foreach ($this->getRelationshipsToBeApiExposed() as $fieldName => $relationshipType) {

            if (!$this->isAllowedSparseField($fieldName)) {
                continue;
            }

            if (!$this->isAllowedIncludeField($fieldName)) {
                $result[$fieldName] = $this->getBasicResourceRelationshipValue($fieldName);

            } elseif ($relationshipType === Relationships::RELATIONSHIP_TYPE_SINGLE) {
                $result[$fieldName] = $this->getResourceSingleRelationshipValue($fieldName);

            } elseif ($relationshipType === Relationships::RELATIONSHIP_TYPE_COLLECTION) {
                $result[$fieldName] = $this->getResourceCollectionRelationshipValue($fieldName);

            }
        }

        return $result;
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
     * @return array
     */
    protected function getBasicResourceRelationshipValue($fieldName)
    {
        return array(
            'links' => $this->getLinksPayloadForResource($fieldName),
        );
    }

    /**
     * @param $fieldName
     * @return array
     */
    protected function getResourceSingleRelationshipValue($fieldName)
    {
        $result = array_merge($this->getBasicResourceRelationshipValue($fieldName), array('data' => null));

        $relationship = ObjectAccess::getProperty($this->getPayload(), $fieldName);
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
        $payload = $this->getPayload();
        $relationship = $this->resourceMapper->getPayloadForDataIdentifier($value['data']);
        ObjectAccess::setProperty($payload, $fieldName, $relationship);
    }

    /**
     * @param string $fieldName
     */
    protected function getResourceCollectionRelationshipValue($fieldName)
    {
        $result = array_merge($this->getBasicResourceRelationshipValue($fieldName), array('data' => array()));

        foreach (ObjectAccess::getProperty($this->getPayload(), $fieldName) as $relationship) {
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
        $payload = $this->getPayload();

        $collection = array();
        foreach ($value['data'] as $relationship) {
            $collection[] = $this->resourceMapper->getPayloadForDataIdentifier($relationship);
        }

        $existingCollection = ObjectAccess::getProperty($payload, $fieldName);
        if (is_object($existingCollection) && $existingCollection instanceof Collection) {
            ObjectAccess::setProperty($payload, $fieldName, new ArrayCollection($collection));
        } else {
            ObjectAccess::setProperty($payload, $fieldName, $collection);
        }

    }

    /**
     * @param string $fieldName
     *
     * @return array
     */
    protected function getLinksPayloadForResource($fieldName)
    {
        return array(
            'self' => (string)$this->getResourceInformation()->getPublicRelationshipUri($this->getPayload(),
                $fieldName),
            'related' => (string)$this->getResourceInformation()->getPublicRelatedUri($this->getPayload(), $fieldName),
        );
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