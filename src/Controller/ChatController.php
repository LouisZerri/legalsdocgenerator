<?php

namespace App\Controller;

use App\Service\AiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    public function __construct(
        private AiService $aiService
    ) {}

    #[Route('/send', name: 'app_chat_send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';
        $history = $data['history'] ?? [];

        if (empty($message)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        $context = "Tu es un assistant juridique expert. Tu aides les utilisateurs à rédiger des documents légaux, comprendre des termes juridiques, et répondre à leurs questions sur le droit des affaires, les contrats, et la conformité. Réponds en français de manière claire et professionnelle.\n\n";

        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? 'Utilisateur' : 'Assistant';
            $context .= $role . ": " . $msg['content'] . "\n\n";
        }

        $context .= "Utilisateur: " . $message . "\n\nAssistant:";

        try {
            $response = $this->aiService->generate($context, 'mistral', 2048);

            return $this->json([
                'response' => $response,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur IA: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    #[Route('/stream', name: 'app_chat_stream', methods: ['POST'])]
    public function streamResponse(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';
        $history = $data['history'] ?? [];

        if (empty($message)) {
            return new Response('Message vide', 400);
        }

        $context = "Tu es un assistant juridique expert français. Tu aides les utilisateurs à rédiger des documents légaux, comprendre des termes juridiques, et répondre à leurs questions sur le droit des affaires, les contrats, et la conformité. Réponds en français de manière claire et professionnelle.\n\n";

        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? 'Utilisateur' : 'Assistant';
            $context .= $role . ": " . $msg['content'] . "\n\n";
        }

        $context .= "Utilisateur: " . $message . "\n\nAssistant:";

        $aiService = $this->aiService;

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($aiService, $context) {
            foreach ($aiService->generateStream($context, 'mistral', 2048) as $token) {
                echo "data: " . json_encode(['token' => $token]) . "\n\n";
                ob_flush();
                flush();
            }
            echo "data: " . json_encode(['done' => true]) . "\n\n";
            ob_flush();
            flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
