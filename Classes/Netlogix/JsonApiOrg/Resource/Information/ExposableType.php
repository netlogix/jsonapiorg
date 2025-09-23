<?php

namespace Netlogix\JsonApiOrg\Resource\Information;


use JsonSerializable;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class ExposableType implements JsonSerializable
{
    public function __construct(
        public readonly string $className,
        public readonly string $typeName,
        public readonly ?string $apiVersion = null,
        public readonly ?string $replaces = null,
    ) {
    }

    public function getVersionType(): string
    {
        $result = $this->typeName;
        if ($this->apiVersion && $this->apiVersion !== ExposableTypeMapInterface::NEXT_VERSION) {
            $result .= '@' . $this->apiVersion;
        }
        return $result;
    }

    public function getVersionTypeForProperty(string $propertyName): string
    {
        return $this->getVersionType() . ':->' . $propertyName;
    }

    public function jsonSerialize(): string
    {
        return $this->getVersionType();
    }
}