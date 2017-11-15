<?php
namespace Netlogix\JsonApiOrg\Schema;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Netlogix\JsonApiOrg\Domain\Model\LinksAwareModelInterface;
use Netlogix\JsonApiOrg\Schema\Traits\ResourceBasedTrait;

/**
 * @see http://jsonapi.org/format/#document-links
 */
class Links extends AbstractSchemaElement implements \ArrayAccess
{

    use ResourceBasedTrait;

    protected $data = [];

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $payload = $this->getPayload();
        $links = $this->data;

        if (is_object($payload)) {
            if ($payload instanceof LinksAwareModelInterface) {
                foreach ((array)$payload->buildLinks($this->getResourceInformation()->getUriBuilder()) as $key => $value) {
                    if (!array_key_exists($key, $links)) {
                        $links[$key] = $value;
                    }
                }
            }
            if (empty($links['self'])) {
                try {
                    $links['self'] = (string)$this->getResourceInformation()->getPublicResourceUri($payload);
                } catch (\Exception $e) {
                }
            }
        }

        return $links;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->jsonSerialize()[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->jsonSerialize()[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * Since this would mean dropping existing link fields endirely,
     * calling offsetUnset is forbidden.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

}