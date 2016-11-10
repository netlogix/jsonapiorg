<?php
namespace Netlogix\JsonApiOrg\Schema;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Netlogix\JsonApiOrg\Schema\Traits\ResourceBasedTrait;
use Netlogix\JsonApiOrg\Schema\Traits\SparseFieldsTrait;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;

/**
 * @see http://jsonapi.org/format/#document-resource-object-attributes
 */
class Attributes extends AbstractSchemaElement implements \ArrayAccess
{

    use ResourceBasedTrait;
    use SparseFieldsTrait;

    /**
     * @var PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $result = array();
        foreach ($this->getAttributesToBeApiExposed() as $fieldName) {
            if (!$this->isAllowedSparseField($fieldName)) {
                continue;
            }
            $result[$fieldName] = $this->offsetGet($fieldName);
            if (is_object($result[$fieldName])) {
                try {
                    $result[$fieldName] = $this->propertyMapper->convert($result[$fieldName], 'string');
                } catch (\Exception $e) {}
            }
        }

        return $result;
    }

    /**
     * @param mixed $fieldName
     * @return bool
     */
    public function offsetExists($fieldName)
    {
        return in_array($fieldName, $this->getAttributesToBeApiExposed());
    }

    /**
     * @param mixed $fieldName
     * @return mixed
     * @throws \TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException
     */
    public function offsetGet($fieldName)
    {
        if ($this->offsetExists($fieldName)) {
            return $this->getResource()->getPayloadProperty($fieldName);
        }
        return null;
    }

    /**
     * @param mixed $fieldName
     * @param mixed $value
     * @return bool
     */
    public function offsetSet($fieldName, $value)
    {
        if ($this->offsetExists($fieldName)) {
            return $this->getResource()->setPayloadProperty($fieldName, $value);
        }
        return null;
    }

    /**
     * @param mixed $fieldName
     */
    public function offsetUnset($fieldName)
    {
        $this->offsetSet($fieldName, null);
    }

}