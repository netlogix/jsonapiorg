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

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $payload = $this->getPayload();
        $links = array();
        if ($payload instanceof LinksAwareModelInterface) {
            $links = $payload->buildLinks($this->getResourceInformation()->getUriBuilder());
        }
        if (empty($links['self'])) {
            try {
                $links['self'] = (string)$this->getResourceInformation()->getPublicResourceUri($payload);
            } catch (\Exception $e) {}
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
     * Since this would mean creating entirely new link fields,
     * calling offsetSet is forbidden.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * Since this would mean dropping existing link fields endirely,
     * calling offsetUnset is forbidden.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
    }

}