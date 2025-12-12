<?php

namespace App\MessageHandler;

use App\Message\GeneratePdfMessage;
use App\Repository\DocumentRepository;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GeneratePdfMessageHandler
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private PdfGenerator $pdfGenerator,
        private EntityManagerInterface $em
    ) {}

    public function __invoke(GeneratePdfMessage $message): void
    {
        $document = $this->documentRepository->find($message->getDocumentId());
        
        if (!$document) {
            return;
        }

        // Générer et sauvegarder le PDF
        $pdfPath = $this->pdfGenerator->generate($document);
        
        // Stocker le chemin relatif
        $relativePath = str_replace($this->getStoragePath() . '/', '', $pdfPath);
        $document->setPdfPath($relativePath);
        
        $this->em->flush();
    }

    private function getStoragePath(): string
    {
        return dirname(__DIR__, 2) . '/var/storage';
    }
}