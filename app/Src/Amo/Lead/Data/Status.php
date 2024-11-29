<?php

namespace App\Src\Amo\Lead\Data;

class Status
{
    public function __construct(
        public string $name,
        public string $color,
        public int $statusId,
    )
    {
    }
}
