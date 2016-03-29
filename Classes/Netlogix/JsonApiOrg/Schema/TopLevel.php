<?php
namespace Netlogix\JsonApiOrg\Schema;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Netlogix\JsonApiOrg\Schema;

/**
 * @see http://jsonapi.org/format/#document-top-level
 */
class TopLevel extends AbstractSchemaElement
{

    /**
     * @var \Netlogix\JsonApiOrg\Schema\Resource|array<\Netlogix\JsonApiOrg\Schema\Resource>
     */
    protected $data = [];

    /**
     * @var array<Error>
     */
    protected $errors = [];

    /**
     * @var \Netlogix\JsonApiOrg\Schema\Meta
     */
    protected $meta;

    /**
     * @var \Netlogix\JsonApiOrg\Schema\JsonApi
     */
    protected $jsonapi;

    /**
     * @var array<\Netlogix\JsonApiOrg\Schema\Resource>
     */
    protected $included;

    /**
     * @var string
     */
    protected $self;

    /**
     * TopLevel constructor.
     */
    public function __construct()
    {
        $this->jsonapi = new JsonApi();
    }

    public function jsonSerialize()
    {
        $result = array(
            'data' => $this->data,
        );
        foreach (array('errors', 'meta', 'included', 'jsonapi') as $optionalField) {
            $optionalValue = json_decode(json_encode($this->{$optionalField}), true);
            if ($optionalValue) {
                $result[$optionalField] = $optionalValue;
            }
        }

        return $result;
    }

    /**
     * @param Schema\Resource $resource
     */
    public function setData(Schema\Resource $resource)
    {
        $this->data = $resource;
    }

    /**
     * @param Schema\Resource $resource
     */
    public function addData(Schema\Resource $resource)
    {
        $this->data[] = $resource;
    }

    /**
     * @param Schema\Error $error
     */
    public function addError(Schema\Error $error)
    {
        $this->errors[] = $error;
    }

    /**
     * @param \Netlogix\JsonApiOrg\Schema\Resource $include
     */
    public function addIncluded(Schema\Resource $include)
    {
        $this->included[] = $include;
    }

}