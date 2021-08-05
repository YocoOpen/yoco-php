<?php


namespace Yoco\Exceptions;


class InternalException extends YocoException
{
    public function __construct($error)
    {
        parent::__construct($error);
    }
}
