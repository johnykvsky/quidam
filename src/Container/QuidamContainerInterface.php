<?php

declare(strict_types = 1);

namespace Quidam\Container;

use Psr\Container\ContainerInterface;

/**
 * Immutable container of Quidam definitions - every change returns a new container with fresh instances
 */
interface QuidamContainerInterface extends ContainerInterface
{
    /**
     * Definitions the container was built from, by id
     *
     * @return array<string, mixed>
     */
    public function definitions(): array;

    /**
     * New container with given definitions added or replaced
     *
     * @param array<string, mixed> $definitions
     */
    public function withDefinitions(array $definitions): self;
}
