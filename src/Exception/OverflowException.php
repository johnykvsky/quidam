<?php

declare(strict_types = 1);

namespace Quidam\Exception;

/**
 * Thrown when strategy can't produce a value within retry limit (i.e. no unique values left)
 */
class OverflowException extends \OverflowException implements QuidamExceptionInterface
{
}
