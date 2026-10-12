<?php

declare(strict_types = 1);

namespace Quidam\Container;

/**
 * Builds the built-in container - default of Quidam::create()
 */
final readonly class SimpleContainerFactory implements ContainerFactoryInterface
{
    public function create(array $definitions): QuidamContainerInterface
    {
        return new SimpleContainer($definitions);
    }
}
