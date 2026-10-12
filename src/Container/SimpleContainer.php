<?php

declare(strict_types = 1);

namespace Quidam\Container;

use Closure;
use Psr\Container\ContainerInterface;
use Quidam\Exception\ContainerException;
use Quidam\Exception\DefinitionNotFoundException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Minimal immutable autowiring container - default container of Quidam. Definition can be:
 * - class-string: autowired; when it differs from id it's an alias - both ids share one instance
 * - Closure: factory called once, its parameters are resolved by type like constructor parameters
 * - object: used as is (shared between containers created by withDefinitions())
 * - any other value: returned as is
 *
 * Any existing class can be fetched without definition - it will be autowired. Every entry is a singleton within given
 * container.
 */
final class SimpleContainer implements QuidamContainerInterface
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, true> */
    private array $resolving = [];

    /** @param array<string, mixed> $definitions */
    public function __construct(
        private readonly array $definitions = [],
    ) {
    }

    public function definitions(): array
    {
        return $this->definitions;
    }

    /**
     * New container with given definitions added or replaced - resolved instances are not carried over
     */
    public function withDefinitions(array $definitions): QuidamContainerInterface
    {
        // one copy of the array and a few assignments - cheaper than spreading all definitions into a new array
        $merged = $this->definitions;
        foreach ($definitions as $id => $definition) {
            $merged[$id] = $definition;
        }

        return new self($merged);
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->definitions) || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new ContainerException(sprintf(
                'Circular dependency detected: %s -> %s',
                implode(' -> ', array_keys($this->resolving)),
                $id,
            ));
        }

        $this->resolving[$id] = true;

        try {
            $instance = $this->resolve($id);
        } finally {
            unset($this->resolving[$id]);
        }

        return $this->instances[$id] = $instance;
    }

    private function resolve(string $id): mixed
    {
        if (!array_key_exists($id, $this->definitions)) {
            return $this->autowire($id);
        }

        $definition = $this->definitions[$id];

        return match (true) {
            $definition instanceof Closure => $this->call($id, $definition),
            is_string($definition) && $definition !== $id && ($this->has($definition) || interface_exists($definition)) => $this->get($definition),
            is_string($definition) && $definition === $id => $this->autowire($id),
            default => $definition,
        };
    }

    private function call(string $id, Closure $factory): mixed
    {
        $arguments = array_map(
            fn (ReflectionParameter $parameter): mixed => $this->resolveParameter($id, $parameter),
            new ReflectionFunction($factory)->getParameters(),
        );

        return $factory(...$arguments);
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new DefinitionNotFoundException(sprintf('No definition found for "%s" and it is not an existing class.', $class));
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(sprintf('Class "%s" is not instantiable.', $class));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = array_map(
            fn (ReflectionParameter $parameter): mixed => $this->resolveParameter($class, $parameter),
            $constructor->getParameters(),
        );

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(string $id, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();

            if (in_array($typeName, [self::class, ContainerInterface::class, QuidamContainerInterface::class], true)) {
                return $this;
            }

            if ($this->has($typeName)) {
                return $this->get($typeName);
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ContainerException(sprintf(
            'Cannot resolve parameter $%s of "%s". Register it in the container with a definition.',
            $parameter->getName(),
            $id,
        ));
    }
}
