<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Container\ContainerFactoryInterface;
use Quidam\Container\QuidamContainerInterface;
use Quidam\Container\SimpleContainerFactory;

/**
 * Third-party container adapter in miniature - counts built containers, delegates to the built-in one
 */
final class RecordingContainerFactory implements ContainerFactoryInterface
{
    public int $created = 0;

    public function create(array $definitions): QuidamContainerInterface
    {
        ++$this->created;

        return new RecordingContainer(new SimpleContainerFactory()->create($definitions), $this);
    }
}
