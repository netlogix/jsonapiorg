<?php
namespace Netlogix\JsonApiOrg\Resource;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Netlogix\JsonApiOrg\Schema;
use Neos\Flow\Annotations as Flow;

/**
 * Just a simple stack that knows about current, future and past
 * requests.
 */
class RequestStack
{

    const POSITION_DATA = 'data';
    const POSITION_DATACOLLECTION = 'data[]';
    const POSITION_INCLUDE = 'include';

    const RESULT_RESOURCE = 'resource';
    const RESULT_POSITION = 'position';
    const RESULT_DATA = 'data';
    const RESULT_NESTING_PATHS = 'nestingPaths';

    protected $open = [];

    protected $results = [];

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
     * @param object $resource
     * @param string $position
     * @param string $nestingPath
     */
    public function push($resource, $position = self::POSITION_DATA, $nestingPath = '')
    {
        if (is_null($resource)) {
            return;
        }
        $hash = spl_object_hash($resource);
        if (array_key_exists($hash, $this->results)) {
            $this->results[$hash][self::RESULT_NESTING_PATHS][$nestingPath] = $nestingPath;
            return;

        }
        $this->results[$hash] = [
            self::RESULT_RESOURCE => $resource,
            self::RESULT_POSITION => $position,
            self::RESULT_DATA => null,
            self::RESULT_NESTING_PATHS => [$nestingPath => $nestingPath]
        ];
        $this->open[] = $hash;
    }

    /**
     * @param array $identifier
     * @param string $position
     * @param string $nestingPath
     */
    public function pushIdentifier(array $identifier, $position = self::POSITION_INCLUDE, $nestingPath = '')
    {
        $resource = $this->propertyMapper->convert((string)$identifier['id'], $this->exposableTypeMap->getClassName($identifier['type']));
        $this->push($resource, $position, $nestingPath);
    }

    /**
     * @return mixed
     */
    public function pop()
    {
        if (!count($this->open)) {
            return null;
        }
        $uri = array_pop($this->open);

        return $this->results[$uri];
    }

    /**
     * @param object $resource
     * @param Schema\ResourceInterface $content
     */
    public function finalize($resource, Schema\ResourceInterface $content)
    {
        $hash = spl_object_hash($resource);
        $this->results[$hash][self::RESULT_DATA] = $content;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param object $resource
     * @return mixed
     */
    public function getNestingPaths($resource)
    {
        $hash = spl_object_hash($resource);
        return $this->results[$hash][self::RESULT_NESTING_PATHS];
    }

}