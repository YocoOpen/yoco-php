<?php


namespace Yoco;

class RefundResult
{
    public $id;
    public $status;
    public $message;
    public $metaData;

    public function __construct($resultStdObject)
    {
        $this->id = $resultStdObject->id ?? null;
        $this->status = $resultStdObject->status ?? null;
        $this->message = $resultStdObject->message ?? null;
        $this->metaData = $resultStdObject->metaData ?? null;
    }
}
