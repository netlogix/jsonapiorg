<?php

namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\Exception\FormatNotSupportedException;

/**
 * This class holds information about:
 *
 * - Internal type identifiers provided by Resource objects
 * - External type identifiers exposed to the public
 * - Class names related to those type identifiers
 *
 * An internal type identifier provided by a Resource object does not necessarily
 * reflect an actual class name.
 */
interface ExposableTypeMapInterface
{
    public const string NEXT_VERSION = 'next';

    public function getExposableTypeByClassIdentifier(string $classIdentifier): ExposableType;

    public function getExposableTypeByTypeName(string $typeName, string $apiVersion): ExposableType;

    public function getExposableTypeByVersionedTypeName(string $versionedTypeName): ExposableType;

    public function getPropertyType(string $typeName, string $apiVersion, string $propertyName): string;

    /**
     * @template T
     * @param callable():T $do
     * @return T
     */
    public static function forceApiVersion(string $apiVersion, callable $do): mixed;

}