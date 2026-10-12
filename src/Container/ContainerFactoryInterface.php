<?php

declare(strict_types = 1);

namespace Quidam\Container;

/**
 * Builds Quidam containers - SimpleContainerFactory by default. An adapter for another container (PHP-DI...)
 * implements this and QuidamContainerInterface, no change in Quidam needed:
 *
 *   Quidam::create(containerFactory: new MyPhpDiContainerFactory())
 *
 * Containers must understand Quidam definitions: class-string (autowired, interface id pointing to a class is an
 * alias), Closure factory (parameters resolved from the container by type), object (used as is), other values as is.
 */
interface ContainerFactoryInterface
{
    /** @param array<string, mixed> $definitions */
    public function create(array $definitions): QuidamContainerInterface;
}
