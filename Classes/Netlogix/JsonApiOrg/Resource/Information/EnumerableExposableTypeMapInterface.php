<?php

namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An ExposableTypeMap implementing this interface can enumerate all
 * registered exposable types, e.g. for endpoint discovery or API
 * documentation generation.
 */
interface EnumerableExposableTypeMapInterface
{
    /**
     * @return list<ExposableType>
     */
    public function getExposableTypes(): array;
}
