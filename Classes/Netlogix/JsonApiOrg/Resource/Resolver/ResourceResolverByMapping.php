<?php
namespace Netlogix\JsonApiOrg\Resource\Resolver;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


use Netlogix\JsonApiOrg\Resource\RequestStack;
use Netlogix\JsonApiOrg\Schema\Resource;
use TYPO3\Flow\Annotations as Flow;

/**
 * The ResourceResolver takes an incoming requestData data structure
 * and returns the according resource object.
 */
class ResourceResolverByMapping implements ResourceResolverInterface
{

    /**
     * @var array
     */
    protected $automaticRequestHeader = array();

    /**
     * @var \TYPO3\Flow\Http\Client\InternalRequestEngine
     * @Flow\Inject
     */
    protected $requestEngine;

    /**
     * @var \TYPO3\Flow\Property\PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * @param array $requestData
     * @return array
     * @throws \Exception
     * @throws \TYPO3\Flow\Property\Exception
     * @throws \TYPO3\Flow\Security\Exception
     */
    public function resourceRequest(array $requestData)
    {

        /** @var Resource $resource */
        $resource = $this->propertyMapper->convert($requestData[RequestStack::RESULT_DATA_IDENTIFIER], Resource::class);

        if (!$resource) {
            throw new \Exception('This request data can not be processed', 1452854971);
        }

        return json_decode(json_encode($resource), true);
    }

    /**
     * @param array $supportedMediaTypes
     */
    public function setSupportedMediaTypes(array $supportedMediaTypes)
    {

    }

}