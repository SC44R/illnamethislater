<?php

namespace App\Exceptions;

/** Thrown when the player does something the game rules do not allow. Controllers turn it into a 422 answer. */
class GameRuleException extends \RuntimeException
{
    public function __construct(public string $errorCode, string $message = '')
    {
        parent::__construct($message ?: $errorCode);
    }
}
