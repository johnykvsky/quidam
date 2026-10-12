<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Container\QuidamContainerInterface;

/**
 * Container of RecordingContainerFactory - rebuilds through its factory, like an adapter of another container would
 */
final readonly class RecordingContainer implements QuidamContainerInterface
{
    public function __construct(
        private QuidamContainerInterface $inner,
        private RecordingContainerFactory $factory,
    ) {
    }

    public function get(string $id): mixed
    {
        return $this->inner->get($id);
    }

    public function has(string $id): bool
    {
        return $this->inner->has($id);
    }

    public function definitions(): array
    {
        return $this->inner->definitions();
    }

    public function withDefinitions(array $definitions): QuidamContainerInterface
    {
        return $this->factory->create([...$this->definitions(), ...$definitions]);
    }
}
