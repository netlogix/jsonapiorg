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
use TYPO3\Flow\Annotations as Flow;

/**
 * Just a simple stack that knows about current, future and past
 * requests.
 */
class RequestStack
{

    const POSITION_DATA = 'data';
    const POSITION_DATACOLLECTION = 'data[]';
    const POSITION_INCLUDE = 'include';

    const RESULT_URI = 'uri';
    const RESULT_POSITION = 'position';
    const RESULT_DATA = 'data';
    const RESULT_DATA_IDENTIFIER = 'id';
    const RESULT_NESTING_PATHS = 'nestingPaths';

    protected $open = array();

    protected $results = array();

    /**
     * @param string $uri
     * @param array $dataIdentifier
     * @param string $position
     * @param string $nestingPath
     */
    public function push($uri, array $dataIdentifier, $position = self::POSITION_DATA, $nestingPath = '')
    {
        if (array_key_exists($uri, $this->results)) {
            $this->results[$uri][self::RESULT_NESTING_PATHS][$nestingPath] = $nestingPath;

            return;
        }

        $this->results[$uri] = array(
            self::RESULT_URI => $uri,
            self::RESULT_POSITION => $position,
            self::RESULT_DATA_IDENTIFIER => $dataIdentifier,
            self::RESULT_DATA => null,
            self::RESULT_NESTING_PATHS => array($nestingPath => $nestingPath)
        );
        $this->open[] = $uri;
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
     * @param string $uri
     * @param Schema\Resource $content
     */
    public function finalize($uri, Schema\Resource $content)
    {
        $this->results[$uri][self::RESULT_DATA] = $content;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param $uri
     * @return mixed
     */
    public function getNestingPaths($uri)
    {
        return $this->results[$uri][self::RESULT_NESTING_PATHS];
    }

}