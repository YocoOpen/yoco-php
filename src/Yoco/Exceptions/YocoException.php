<?php


namespace Yoco\Exceptions;


class YocoException extends \Exception
{
    public $errorType;
    public $errorCode;
    public $errorMessage;
    public $displayMessage;

    public function __construct($error)
    {
        $this->errorType = $error->errorType;
        $this->errorCode = $error->errorCode;
        $this->errorMessage = $error->errorMessage;
        $this->displayMessage = $error->displayMessage;
        parent::__construct($this->errorMessage ?? "Unknown Error");
    }
}
