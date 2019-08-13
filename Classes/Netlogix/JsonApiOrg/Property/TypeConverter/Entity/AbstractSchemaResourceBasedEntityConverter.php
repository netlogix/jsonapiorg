<?php

namespace Netlogix\JsonApiOrg\Property\TypeConverter\Entity;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;

/**
 */
abstract class AbstractSchemaResourceBasedEntityConverter extends PersistentObjectConverter
{
    /**
     * @var \Neos\Flow\Property\PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;
    /**
     * @var \Netlogix\JsonApiOrg\Resource\Information\ExposableTypeMapInterface
     * @Flow\Inject
     */
    protected $exposableTypeMap;

    /**
     * @var array<object>
     */
    private static $scope;

    /**
     * All properties in the source array except __identity are sub-properties.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return [];
    }

    /**
     * @param $source
     * @return bool
     */
    protected function hasOnlyIdentifierProperties($source)
    {
        if (!is_array($source)) {
            return false;
        } elseif (count($source) === 1 && array_key_exists('type', $source)) {
            return true;
        } elseif (count($source) === 2 && array_key_exists('type', $source) && array_key_exists('id', $source)) {
            return true;
        }

        return false;
    }

    /**
     * @param $source
     * @return mixed
     */
    protected function convertIdentifierProperties($source)
    {
        $result = $this->getFromScope($source);
        if ($result) {
            return $result;
        } else {
            $identifier = array_key_exists('id', $source) ? $source['id'] : [];
            $result = $this->propertyMapper->convert(
                $identifier,
                $this->exposableTypeMap->getClassName($source['type'])
            );
            $this->addToScope($source, $result);
            return $result;
        }
    }

    protected function asBatchScope(callable $callable, ... $callableArguments)
    {
        $executor = function() use (&$callable, &$callableArguments) {
            return $callable(... $callableArguments);
        };

        if (is_array(self::$scope)) {
            return $executor();
        } else {
            try {
                $scope = self::$scope;
                self::$scope = [];
                return $executor();
            } finally {
                self::$scope = $scope;
            }
        }
    }

    protected function addToScope(array $source, $subject)
    {
        if (!is_array(self::$scope)) {
            return;
        }
        self::$scope[self::getScopeIdentifier($source)] = $subject;
    }

    protected function getFromScope(array $source)
    {
        if (!is_array(self::$scope)) {
            return null;
        }
        return self::$scope[self::getScopeIdentifier($source)] ?? null;
    }

    protected static function getScopeIdentifier(array $source): string
    {
        return ($source['type'] ?? '') . PHP_EOL . ($source['id'] ?? '');
    }

}