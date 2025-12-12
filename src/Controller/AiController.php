<?php

namespace App\Controller;

use App\Entity\Document;
use App\Service\AiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai')]
#[IsGranted('ROLE_USER')]
class AiController extends AbstractController
{
    public function __construct(
        private AiService $aiService
    ) {}

    #[Route('/status', name: 'app_ai_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse([
            'available' => $this->aiService->isAvailable(),
        ]);
    }

    #[Route('/document/{id}/improve', name: 'app_ai_improve', methods: ['POST'])]
    public function improve(Document $document, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $document);

        if (!$this->aiService->isAvailable()) {
            return new JsonResponse(['error' => 'Service IA indisponible'], 503);
        }

        $data = json_decode($request->getContent(), true);
        $tone = $data['tone'] ?? 'formel';

        $improved = $this->aiService->improveDocument($document->getGeneratedContent(), $tone);

        if (!$improved) {
            return new JsonResponse(['error' => 'Erreur lors de la génération'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'content' => $improved,
        ]);
    }

    #[Route('/document/{id}/reformulate', name: 'app_ai_reformulate', methods: ['POST'])]
    public function reformulate(Document $document, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $document);

        if (!$this->aiService->isAvailable()) {
            return new JsonResponse(['error' => 'Service IA indisponible'], 503);
        }

        $data = json_decode($request->getContent(), true);
        $style = $data['style'] ?? 'formel';

        $reformulated = $this->aiService->reformulate($document->getGeneratedContent(), $style);

        if (!$reformulated) {
            return new JsonResponse(['error' => 'Erreur lors de la reformulation'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'content' => $reformulated,
        ]);
    }

    #[Route('/document/{id}/summarize', name: 'app_ai_summarize', methods: ['POST'])]
    public function summarize(Document $document): JsonResponse
    {
        $this->denyAccessUnlessGranted('view', $document);

        if (!$this->aiService->isAvailable()) {
            return new JsonResponse(['error' => 'Service IA indisponible'], 503);
        }

        $summary = $this->aiService->summarize($document->getGeneratedContent());

        if (!$summary) {
            return new JsonResponse(['error' => 'Erreur lors du résumé'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    #[Route('/document/{id}/check', name: 'app_ai_check', methods: ['POST'])]
    public function check(Document $document): JsonResponse
    {
        $this->denyAccessUnlessGranted('view', $document);

        if (!$this->aiService->isAvailable()) {
            return new JsonResponse(['error' => 'Service IA indisponible'], 503);
        }

        $analysis = $this->aiService->checkCompliance($document->getGeneratedContent());

        if (!$analysis) {
            return new JsonResponse(['error' => 'Erreur lors de l\'analyse'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'analysis' => $analysis,
        ]);
    }

    #[Route('/document/{id}/apply', name: 'app_ai_apply', methods: ['POST'])]
    public function apply(Document $document, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $document);

        if ($document->getStatus() !== Document::STATUS_DRAFT) {
            return new JsonResponse(['error' => 'Seuls les brouillons peuvent être modifiés'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $newContent = $data['content'] ?? null;

        if (!$newContent) {
            return new JsonResponse(['error' => 'Contenu manquant'], 400);
        }

        $document->setGeneratedContent($newContent);
        $document->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Document mis à jour avec les suggestions IA.');

        return new JsonResponse([
            'success' => true,
        ]);
    }

    #[Route('/generate-clause', name: 'app_ai_generate_clause', methods: ['POST'])]
    public function generateClause(Request $request): JsonResponse
    {
        if (!$this->aiService->isAvailable()) {
            return new JsonResponse(['error' => 'Service IA indisponible'], 503);
        }

        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? 'confidentialité';
        $context = $data['context'] ?? [];

        $clause = $this->aiService->generateClause($type, $context);

        if (!$clause) {
            return new JsonResponse(['error' => 'Erreur lors de la génération'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'clause' => $clause,
        ]);
    }
}