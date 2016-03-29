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
 * @see http://jsonapi.org/format/#document-jsonapi-object
 */
class JsonApi
{

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * @var \Netlogix\JsonApiOrg\Schema\Meta
     */
    protected $meta;

}