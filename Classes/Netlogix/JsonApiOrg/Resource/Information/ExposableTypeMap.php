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
 *
 * @Flow\Scope("singleton")
 */
class ExposableTypeMap implements ExposableTypeMapInterface
{

    /**
     * Key/Value pairs mapping an internal type identifier to a public type name.
     *
     * Example:
     *
     *   array(
     *     'Neos\ContentRepository\Domain\Model\Node::unstructured' => 'unstructured',
     *     'Neos\ContentRepository\Domain\Model\Node::Neos.Neos:Content' => 'content-node',
     *     'Neos\ContentRepository\Domain\Model\Node::Neos.Neos:ContentCollection' => 'collection-node',
     *   );
     *
     * @var array<string>
     */
    protected $classIdentifierToTypeNameMap = array();

    /**
     * Key/Value pairs mapping a public type name to an internal class identifier.
     *
     * Example:
     *
     *   array(
     *     'unstructured' => 'Neos\ContentRepository\Domain\Model\Node'
     *     'content-node' => 'Neos\ContentRepository\Domain\Model\Node'
     *     'collection-node' => 'Neos\ContentRepository\Domain\Model\Node'
     *   );
     *
     * @var array<string>
     */
    protected $typeNameToClassIdentifierMap = array();

    /**
     * Key/Value pairs mapping an actual PHP class name to a public type name.
     *
     * @var array
     */
    protected $oneToOneTypeToClassMap = array();

    /**
     *
     */
    public function initializeObject()
    {
        foreach ($this->oneToOneTypeToClassMap as $className => $typeName) {
            $this->typeNameToClassIdentifierMap[$typeName] = $className;
            $this->classIdentifierToTypeNameMap[$className] = $typeName;
        }
    }

    /**
     * Returns the public type string for a given class name.
     *
     * @param string $classIdentifier
     * @return string
     * @throws FormatNotSupportedException
     */
    public function getType($classIdentifier)
    {
        if (array_key_exists($classIdentifier, $this->classIdentifierToTypeNameMap)) {
            return $this->classIdentifierToTypeNameMap[$classIdentifier];
        } else {
            throw new FormatNotSupportedException('There is no target type for class name "' . $classIdentifier . '"',
                1451995790);
        }
    }

    /**
     * @param string $typeName
     * @return string
     * @throws FormatNotSupportedException
     */
    public function getClassName($typeName)
    {
        if (array_key_exists($typeName, $this->typeNameToClassIdentifierMap)) {
            return $this->typeNameToClassIdentifierMap[$typeName];
        } else {
            throw new FormatNotSupportedException('There is no target class name for type "' . $typeName . '"',
                1451995976);
        }
    }

}