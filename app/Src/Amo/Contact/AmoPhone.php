<?php

namespace App\Src\Amo\Contact;

class AmoPhone
{
    public function __construct(
        public string $withCode,
        public string $withoutCode
    )
    {
    }
}
