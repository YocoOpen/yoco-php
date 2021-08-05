<?php


namespace Yoco;


class ChargeResultSource
{
    public $id;
    public $brand;
    public $maskedCard;
    public $expiryMonth;
    public $expiryYear;
    public $fingerprint;
    public $object;
    public $country;

    public function __construct($resultSourceStdObject)
    {
        $this->id = $resultSourceStdObject->id ?? null;
        $this->object = $resultSourceStdObject->object ?? null;
        $this->brand = $resultSourceStdObject->brand ?? null;
        $this->maskedCard = $resultSourceStdObject->maskedCard ?? null;
        $this->expiryMonth = $resultSourceStdObject->expiryMonth ?? null;
        $this->expiryYear = $resultSourceStdObject->expiryYear ?? null;
        $this->fingerprint = $resultSourceStdObject->fingerprint ?? null;
        $this->country = $resultSourceStdObject->country ?? null;
    }
}

