<?php
namespace Netlogix\JsonApiOrg\Schema;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * @see http://jsonapi.org/format/#document-resource-object-attributes
 */
class Attributes extends AbstractSchemaElement implements \ArrayAccess{

	use \Netlogix\JsonApiOrg\Schema\Traits\ResourceBasedTrait;
	use \Netlogix\JsonApiOrg\Schema\Traits\SparseFieldsTrait;

	/**
	 * @return array
	 */
	public function jsonSerialize () {
		$result = array();
		foreach ($this->getAttributesToBeApiExposed() as $fieldName) {
			if (!$this->isAllowedSparseField($fieldName)) {
				continue;
			}

			$result[$fieldName] = $this->offsetGet($fieldName);
		}

		return $result;
	}

	/**
	 * @param mixed $fieldName
	 * @return bool
	 */
	public function offsetExists($fieldName) {
		return in_array($fieldName, $this->getAttributesToBeApiExposed());
	}

	/**
	 * @param mixed $fieldName
	 * @return mixed
	 * @throws \TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException
	 */
	public function offsetGet($fieldName) {
		if ($this->offsetExists($fieldName)) {
			$payload = $this->getPayload();
			return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($payload, $fieldName);
		}
	}

	/**
	 * @param mixed $fieldName
	 * @param mixed $value
	 * @return bool
	 */
	public function offsetSet($fieldName, $value) {
		if ($this->offsetExists($fieldName)) {
			$payload = $this->getPayload();
			return \TYPO3\Flow\Reflection\ObjectAccess::setProperty($payload, $fieldName, $value);
		}
	}

	/**
	 * @param mixed $fieldName
	 */
	public function offsetUnset($fieldName) {
		$this->offsetSet($fieldName, NULL);
	}

}