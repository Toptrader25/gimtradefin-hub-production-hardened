<?php
namespace App\Contracts;

use App\DTO\SourceDefinition;
use App\DTO\FetchResult;
use App\DTO\CandidateRecord;

interface SourceConnector
{
    public function fetch(SourceDefinition $source): FetchResult;
    /** @return CandidateRecord[] */
    public function parse(SourceDefinition $source, FetchResult $result): array;
}
