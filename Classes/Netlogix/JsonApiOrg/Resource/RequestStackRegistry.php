<?php

namespace Netlogix\JsonApiOrg\Resource;

use Neos\Flow\Annotations as Flow;

/**
 * Keeps soft references to all currently active RequestStack objects.
 *
 * Each RequestStack registers itself here on creation. As the registry only
 * holds weak references, stacks that are no longer referenced elsewhere (e.g.
 * once a rendering pass has finished) vanish automatically without preventing
 * garbage collection.
 *
 * @Flow\Scope("singleton")
 */
class RequestStackRegistry
{
    /**
     * @var \WeakMap<RequestStack, true>
     */
    protected $requestStacks;

    public function initializeObject(): void
    {
        $this->requestStacks = new \WeakMap();
    }

    public function register(RequestStack $requestStack): void
    {
        $this->requestStacks[$requestStack] = true;
    }

    /**
     * @return iterable<RequestStack>
     */
    public function getRequestStacks(): iterable
    {
        foreach ($this->requestStacks as $requestStack => $_) {
            yield $requestStack;
        }
    }

    /**
     * Returns the first resource matching the given type and id that is held by
     * any active RequestStack, or null if none of them knows it yet.
     */
    public function findResource(string $type, string $id): ?object
    {
        foreach ($this->getRequestStacks() as $requestStack) {
            $resource = $requestStack->findResource($type, $id);
            if ($resource !== null) {
                return $resource;
            }
        }

        return null;
    }
}
