<?php

namespace App\MessageHandler;

use App\Message\AiProcessMessage;
use App\Repository\DocumentRepository;
use App\Service\AiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AiProcessMessageHandler
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private AiService $aiService,
        private EntityManagerInterface $em
    ) {}

    public function __invoke(AiProcessMessage $message): void
    {
        $document = $this->documentRepository->find($message->getDocumentId());
        
        if (!$document) {
            return;
        }

        $content = $document->getGeneratedContent();
        $result = null;

        switch ($message->getAction()) {
            case 'improve':
                $tone = $message->getParams()['tone'] ?? 'formel';
                $result = $this->aiService->improveDocument($content, $tone);
                break;
            
            case 'reformulate':
                $style = $message->getParams()['style'] ?? 'formel';
                $result = $this->aiService->reformulate($content, $style);
                break;
        }

        // Stocker le résultat en cache ou notifier l'utilisateur
        // Pour l'instant on log juste
        if ($result) {
            error_log("AI processed document {$message->getDocumentId()}: " . substr($result, 0, 100));
        }
    }
}