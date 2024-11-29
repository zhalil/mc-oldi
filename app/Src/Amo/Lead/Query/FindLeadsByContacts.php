<?php

namespace App\Src\Amo\Lead\Query;

class FindLeadsByContacts
{
    public function __construct(
        public int $pipelineId,
        public string $text,
    )
    {
    }
}
