<?php

namespace App\Message;

class AiProcessMessage
{
    public function __construct(
        private int $documentId,
        private string $action,
        private array $params = []
    ) {}

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}