<?php
namespace Netlogix\JsonApiOrg\Resource\Resolver;

    /*
     * This file is part of the Netlogix.JsonApiOrg package.
     *
     * This package is Open Source Software. For the full copyright and license
     * information, please view the LICENSE file which was distributed with this
     * source code.
     */


/**
 * The ResourceResolver takes an incoming requestData data structure
 * and returns the according resource object.
 *
 * This can either be done by sub requests or by internal property mapping.
 */
interface ResourceResolverInterface
{

    /**
     * @param array $requestData
     * @return array
     */
    public function resourceRequest(array $requestData);

    /**
     * @param array $supportedMediaTypes
     */
    public function setSupportedMediaTypes(array $supportedMediaTypes);

}