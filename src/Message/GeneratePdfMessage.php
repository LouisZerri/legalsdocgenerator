<?php

namespace App\Message;

class GeneratePdfMessage
{
    public function __construct(
        private int $documentId
    ) {}

    public function getDocumentId(): int
    {
        return $this->documentId;
    }
}