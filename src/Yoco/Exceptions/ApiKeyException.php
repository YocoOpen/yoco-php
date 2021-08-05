<?php


namespace Yoco\Exceptions;


class ApiKeyException extends YocoException
{
    public function __construct($error)
    {
        parent::__construct($error);
    }
}
