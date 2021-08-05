<?php


namespace Yoco;

class ChargeResult
{
    public $object;
    public $id;
    public $status;
    public $currency;
    public $amountInCents;
    public $liveMode;
    public $metadata;
    public $source;

    public function __construct($resultStdObject)
    {
        $this->object = $resultStdObject->object ?? null;
        $this->id = $resultStdObject->id ?? null;
        $this->status = $resultStdObject->status ?? null;
        $this->currency = $resultStdObject->currency ?? null;
        $this->amountInCents = $resultStdObject->amountInCents ?? null;
        $this->liveMode = $resultStdObject->liveMode ?? null;
        $this->metadata = $resultStdObject->metadata ?? null;
        $this->source = new ChargeResultSource($resultStdObject->source ?? json_decode("{}"));
    }
}
