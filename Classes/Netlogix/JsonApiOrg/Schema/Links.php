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
 * @see http://jsonapi.org/format/#document-links
 */
class Links extends AbstractSchemaElement implements \ArrayAccess{

	use \Netlogix\JsonApiOrg\Schema\Traits\ResourceBasedTrait;

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return array(
			'self' => (string)$this->getResourceInformation()->getPublicResourceUri($this->getPayload()),
		);
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return isset($this->jsonSerialize()[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->jsonSerialize()[$offset];
	}

	/**
	 * Since this would mean creating entirely new link fields,
	 * calling offsetSet is forbidden.
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
	}

	/**
	 * Since this would mean dropping existing link fields endirely,
	 * calling offsetUnset is forbidden.
	 *
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
	}

}