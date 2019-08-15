<?php
declare(strict_types=1);

namespace Netlogix\JsonApiOrg\Property\TypeConverter\Entity;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class BatchScope
{
    protected static $instance;

    protected $entities = [];

    protected $isMute = false;

    public static function wrap(callable $callable, ... $callableArguments)
    {
        $executor = function () use (&$callable, &$callableArguments) {
            return $callable(... $callableArguments);
        };

        if (self::$instance) {
            return $executor();
        } else {
            try {
                self::$instance = new self();
                return $executor();
            } finally {
                self::$instance = null;
            }
        }
    }

    public static function instance(): self
    {
        static $mute;
        if (!$mute) {
            $mute = new self();
            $mute->isMute = true;
        }
        return self::$instance ?? $mute;
    }

    public function addObject(array $source, $subject): self
    {
        if (!$this->isMute && $subject) {
            $this->entities[self::getScopeIdentifier($source)] = $subject;
        }
        return $this;
    }

    public function findObject(array $source)
    {
        return $this->entities[self::getScopeIdentifier($source)] ?? null;
    }

    protected static function getScopeIdentifier(array $source): string
    {
        return ($source['type'] ?? '') . PHP_EOL . ($source['id'] ?? '');
    }
}