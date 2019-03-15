<?php
namespace Netlogix\JsonApiOrg\Resource\Information;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\Exception\FormatNotSupportedException;

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

    /**
     * Returns the public type string for a given class name.
     *
     * @param string $classIdentifier
     * @return string
     * @throws FormatNotSupportedException
     */
    public function getType($classIdentifier);

    /**
     * @param string $typeName
     * @return string
     * @throws FormatNotSupportedException
     */
    public function getClassName($typeName);

}