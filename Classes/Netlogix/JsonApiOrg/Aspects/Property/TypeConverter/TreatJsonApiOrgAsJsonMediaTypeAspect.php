<?php
namespace Netlogix\JsonApiOrg\Aspects\Property\TypeConverter;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * Since the default MediaTypeConverter does not recognize the media type
 * suggested by jsonapi.org, this aspect treats every incoming jsonapi.org
 * format like default json.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class TreatJsonApiOrgAsJsonMediaTypeAspect
{

    /**
     * @Flow\Around("within(TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface) && method(.*->convertMediaType())")
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return mixed
     */
    public function rewriteJsonApiOrgMediaTypeToJson(JoinPointInterface $joinPoint)
    {

        $mediaType = $joinPoint->getMethodArgument('mediaType');
        if (strpos($mediaType, 'application/vnd.api+json') !== false) {
            $joinPoint->setMethodArgument('mediaType', 'application/json');
        }

        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

}