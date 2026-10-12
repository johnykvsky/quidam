<?php

declare(strict_types = 1);

namespace Quidam\Exception;

/**
 * Thrown when the environment fails, not the arguments - i.e. a memory stream can't be opened
 */
class RuntimeException extends \RuntimeException implements QuidamExceptionInterface
{
}
