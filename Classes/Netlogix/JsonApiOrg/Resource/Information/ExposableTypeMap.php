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

use function vsprintf;

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
    private static ?string $forcedApiVersion = null;

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
     * @var array<string, ExposableType>
     */
    private $classIdentifierToTypeNameMap = [];

    /**
     * Example:
     *   [
     *     'unstructured' => [
     *       'v1' => new ExposableType(
     *         className: 'Neos\ContentRepository\Domain\Model\Node',
     *         typeName: 'unstructured',
     *         apiVersion: 'v1'
     *       )
     *     ],
     *     'content-node' => [
     *        'v1' => new ExposableType(
     *          className: 'Neos\ContentRepository\Domain\Model\Node',
     *          typeName: 'content-node',
     *          apiVersion: 'v1'
     *        )
     *      ],
     *     'collection-node' => [
     *        'v1' => new ExposableType(
     *          className: 'Neos\ContentRepository\Domain\Model\Node',
     *          typeName: 'collection-node',
     *          apiVersion: 'v1'
     *        )
     *      ]
     *   ];
     *
     * @var array<array<string, ExposableType>>
     */
    private $typeNameToClassIdentifierMap = [];

    /**
     * Key/Value pairs mapping public properties of type names to class names
     *
     * Type names and property names are to be split by "->" (arrow sign, like the PHP property access).
     *
     * Example:
     *
     *   [
     *     'unstructured@v1->options' => 'array<string>',
     *     'unstructured@v1->firstname' => 'string',
     *   ];
     */
    private $typeAndPropertyNameToClassIdentifierMap = [];

    /**
     * @template T of class-string
     * @var array<T, ExposableType>
     */
    private $oneToOneTypeToClassMap = [];

    public function registerExposableType(ExposableType $exposableType): void
    {
        $this->classIdentifierToTypeNameMap[$exposableType->className] = $exposableType;

        if (array_key_exists($exposableType->getVersionType(), $this->oneToOneTypeToClassMap)) {
            // FIXME: Conflict resolution isn't quite there, yet, because multiple levels of replacement are not handled.
            $conflict = $this->oneToOneTypeToClassMap[$exposableType->getVersionType()];
            if ($conflict->replaces === $exposableType->className) {
                // Conflict is already the "better" one
                return;
            } elseif ($conflict->className === $exposableType->replaces) {
                // the new one is the "better" one
            } else {
                throw new \RuntimeException(
                    vsprintf(
                        'There is already an ExposableType registered for type "%s" (%s::class, %s::class)',
                        [
                            $exposableType->getVersionType(),
                            $exposableType->className,
                            $conflict->className,
                        ]
                    ),
                    1758557659
                );
            }
        }
        $this->oneToOneTypeToClassMap[$exposableType->getVersionType()] = $exposableType;
        $this->typeNameToClassIdentifierMap[$exposableType->typeName] = $this->typeNameToClassIdentifierMap[$exposableType->typeName] ?? [];
        $this->typeNameToClassIdentifierMap[$exposableType->typeName][$exposableType->apiVersion] = $exposableType;
    }

    public function registerExposableTypeProperty(ExposableType $exposableType, $propertyName, $propertyType)
    {
        $propertyIdentifier = $exposableType->getVersionType() . '->' . $propertyName;
        $this->typeAndPropertyNameToClassIdentifierMap[$propertyIdentifier] = $propertyType;
    }

    public function getExposableTypeByClassIdentifier(string $classIdentifier): ExposableType
    {
        return $this->classIdentifierToTypeNameMap[$classIdentifier] ?? throw new FormatNotSupportedException(
            'There is no target type for class name "' . $classIdentifier . '"',
            1451995790
        );
    }

    public function getExposableTypeByTypeName(string $typeName, string $apiVersion): ExposableType
    {
        $apiVersion = self::$forcedApiVersion ?? $apiVersion;
        return $this->typeNameToClassIdentifierMap[$typeName][$apiVersion] ?? throw new FormatNotSupportedException(
            'There is no target class name for type "' . $typeName . '" with api version "' . $apiVersion . '"',
            1451995976
        );
    }

    public function getExposableTypeByVersionedTypeName(string $versionedTypeName): ExposableType
    {
        if (self::$forcedApiVersion) {
            $versionedTypeName = explode('@', $versionedTypeName)[0];
            if (self::$forcedApiVersion !== ExposableTypeMapInterface::NEXT_VERSION) {
                $versionedTypeName .= '@' . self::$forcedApiVersion;
            }
        }
        return $this->oneToOneTypeToClassMap[$versionedTypeName] ?? throw new FormatNotSupportedException(
            'There is no target class name for type "' . $versionedTypeName . '"',
            1758629156
        );
    }

    public function getPropertyType(
        string $typeName,
        string $apiVersion,
        string $propertyName
    ): string {
        $apiVersion = self::$forcedApiVersion ?? $apiVersion;
        $key = strtolower($typeName . '->' . $propertyName);
        if (array_key_exists($key, $this->typeAndPropertyNameToClassIdentifierMap)) {
            return $this->typeAndPropertyNameToClassIdentifierMap[$key];
        } else {
            throw new FormatNotSupportedException(
                'There is no target class name for property "' . $key . '" with api version "' . $apiVersion . '"',
                1560943398
            );
        }
    }

    final public static function forceApiVersion(?string $apiVersion, callable $do): mixed
    {
        $apiVersionBefore = static::$forcedApiVersion;
        static::$forcedApiVersion = $apiVersion ?? ExposableTypeMapInterface::NEXT_VERSION;
        try {
            return $do();
        } finally {
            static::$forcedApiVersion = $apiVersionBefore;
        }
    }

}