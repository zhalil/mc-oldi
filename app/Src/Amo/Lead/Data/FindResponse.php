<?php

namespace App\Src\Amo\Lead\Data;

class FindResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $pipelineId,
        public string $pipelineName,
        public string $contactName,
        public Status $status,
    )
    {
    }
}
