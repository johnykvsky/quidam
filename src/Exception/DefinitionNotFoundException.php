<?php

declare(strict_types = 1);

namespace Quidam\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown when container has no definition for given id and it can't be autowired
 */
class DefinitionNotFoundException extends \RuntimeException implements QuidamExceptionInterface, NotFoundExceptionInterface
{
}
