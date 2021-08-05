<?php


namespace Yoco\Exceptions;


class DeclinedException extends YocoException
{
    public function __construct($error)
    {
        parent::__construct($error);
    }
}
