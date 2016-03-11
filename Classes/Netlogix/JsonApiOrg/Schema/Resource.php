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
 * @see http://jsonapi.org/format/#document-resource-objects
 */
abstract class Resource extends AbstractSchemaElement {

	/**
	 * @var Object
	 */
	protected $payload;

	/**
	 * @var \Netlogix\JsonApiOrg\Resource\Information\ResourceInformationInterface
	 */
	protected $resourceInformation;

	/**
	 * @var \Netlogix\JsonApiOrg\Schema\Attributes
	 */
	protected $attributes;

	/**
	 * @var \Netlogix\JsonApiOrg\Schema\Links
	 */
	protected $links;

	/**
	 * @var \Netlogix\JsonApiOrg\Schema\Relationships
	 */
	protected $relationships;

	/**
	 * @var \Netlogix\JsonApiOrg\Schema\Meta
	 */
	protected $meta;

	/**
	 * Resource constructor.
	 *
	 * @param $payload
	 * @param \Netlogix\JsonApiOrg\Resource\Information\ResourceInformationInterface $resourceInformation
	 */
	public function __construct($payload, \Netlogix\JsonApiOrg\Resource\Information\ResourceInformationInterface $resourceInformation = NULL) {
		$this->payload = $payload;
		$this->resourceInformation = $resourceInformation;

		$this->attributes = new \Netlogix\JsonApiOrg\Schema\Attributes($this);
		$this->links = new \Netlogix\JsonApiOrg\Schema\Links($this);
		$this->relationships = new \Netlogix\JsonApiOrg\Schema\Relationships($this);
		$this->meta = new \Netlogix\JsonApiOrg\Schema\Meta($this);
	}

	/**
	 * @return mixed
	 */
	public function getPayload() {
		return $this->payload;
	}

	/**
	 * @return \Netlogix\JsonApiOrg\Resource\Information\ResourceInformationInterface
	 */
	public function getResourceInformation() {
		return $this->resourceInformation;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize () {
		$result = $this->resourceMapper->getDataIdentifierForPayload($this->getPayload());

		foreach (array('attributes', 'links', 'relationships', 'meta') as $optionalField) {
			$optionalValue = json_decode(json_encode($this->{$optionalField}), TRUE);
			if ($optionalValue) {
				$result[$optionalField] = $optionalValue;
			}
		}

		return $result;
	}

	/**
	 * @return Attributes
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * @return Links
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * @return Relationships
	 */
	public function getRelationships() {
		return $this->relationships;
	}

	/**
	 * @return Meta
	 */
	public function getMeta() {
		return $this->meta;
	}

}