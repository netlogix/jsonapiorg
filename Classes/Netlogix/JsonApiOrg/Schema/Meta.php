<?php

namespace Netlogix\JsonApiOrg\Schema;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * @see http://jsonapi.org/format/#document-meta
 */
class Meta implements \ArrayAccess, \JsonSerializable
{
    protected $data = [];

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $flatData = self::getFlatDataMap($offset, json_decode(json_encode($value), true));
        foreach ($flatData as $key => $singleValue) {
            $this->data[$key] = $singleValue;
        }
        return $this;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    function jsonSerialize()
    {
        $data = array_map(function ($key, $value) {
            return 'meta[' . str_replace('.', '][', urlencode($key)) . ']' . '=' . urlencode($value);
        }, array_keys($this->data), array_values($this->data));
        if (!$data) {
            return [];
        }
        parse_str(join('&', $data), $result);
        return $result['meta'];
    }

    private static function getFlatDataMap($offset, $value)
    {
        if (is_iterable($value)) {
            foreach ($value as $itemKey => $itemValue) {
                yield from self::getFlatDataMap($offset . '.' . $itemKey, $itemValue);
            }
        } else {
            yield $offset => $value;
        }
    }

}