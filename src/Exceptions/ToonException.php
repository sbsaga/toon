<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Exceptions;

use Exception;

class ToonException extends Exception
{
    /**
     * ToonException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
