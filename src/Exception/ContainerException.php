<?php

declare(strict_types = 1);

namespace Quidam\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Thrown when container entry can't be built or isn't what was asked for
 */
class ContainerException extends \RuntimeException implements QuidamExceptionInterface, ContainerExceptionInterface
{
}
