<?php
namespace Netlogix\JsonApiOrg\Http\Request;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class SetFormatByAcceptHeaderAspect
{

    /**
     * Sets the request format according th the given accept header.
     *
     * @Flow\After("method(Neos\Flow\Mvc\ActionRequest->setArguments())")
     * @param JoinPointInterface $joinPoint The current joinpoint
     */
    public function setRequestFormatByAcceptHeader(JoinPointInterface $joinPoint)
    {

        /** @var \Neos\Flow\Mvc\ActionRequest $actionRequest */
        $actionRequest = $joinPoint->getProxy();

        /**
         * The Accept header should be the only one to determine which resulting
         * data format the client is able to understand. But since some JS libs
         * do send Accept XML but Content-Type Json even when the expect to process
         * jsonapi.org format data we care about both, the Accept header as well
         * as the Content-Type header.
         */
        foreach (array('Accept', 'Http-Accept', 'Content-Type') as $fieldName) {
            $headerValue = $actionRequest->getHttpRequest()->getHeader($fieldName);
            foreach (array('application/json', 'application/vnd.api+json') as $acceptableValue) {
                if (strpos($headerValue, $acceptableValue) !== false) {
                    $actionRequest->setFormat('json');

                    return;
                }
            }
        }
    }

}