<?php
namespace Netlogix\JsonApiOrg\Schema;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Having an abstract schema element in place is not a feature by
 * the jsonapi.org definition but just a convenience to access
 * the resource mapper.
 */
abstract class AbstractSchemaElement implements \JsonSerializable {

	/**
	 * @var \Netlogix\JsonApiOrg\Resource\Information\ResourceMapper
	 * @Flow\Inject
	 */
	protected $resourceMapper;

	/**
	 * Every jsonapi.org schema element must be able to jsonSerialize
	 * itself. So the whole schema definition can be seen as its very
	 * own JsonView implementation.
	 *
	 * @return array
	 */
	abstract function jsonSerialize();

	/**
	 * @param $payload
	 * @return array
	 */
	protected function getDataIdentifierForPayload($payload) {
		return $this->resourceMapper->getDataIdentifierForPayload($payload);
	}

}