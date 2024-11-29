<?php

namespace App\Src\Amo\Lead\Query;

class FindLeadsByText
{
    public function __construct(
        public int $pipelineId,
        public string $text,
    )
    {
    }
}
