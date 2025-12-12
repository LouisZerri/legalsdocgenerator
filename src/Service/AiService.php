<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiService
{
    private string $baseUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        string $ollamaHost = 'ollama'
    ) {
        $this->baseUrl = "http://{$ollamaHost}:11434";
    }

    public function generate(string $prompt, string $model = 'tinyllama', int $maxTokens = 2048): ?string
    {
        try {
            $response = $this->httpClient->request('POST', "{$this->baseUrl}/api/generate", [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'num_predict' => $maxTokens,
                        'temperature' => 0.7,
                    ],
                ],
                'timeout' => 300,
            ]);

            $data = $response->toArray();
            return $data['response'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function improveDocument(string $content, string $tone = 'formel'): ?string
    {
        $prompt = <<<PROMPT
            Tu es un assistant juridique français expert. Améliore ce document juridique pour le rendre plus {$tone} et professionnel. Garde la même structure. Réponds UNIQUEMENT avec le document amélioré complet.

            Document :
            {$content}

            Document amélioré :
            PROMPT;

        return $this->generate($prompt, 'tinyllama', 4096);
    }

    public function generateClause(string $type, array $context = []): ?string
    {
        $contextStr = '';
        foreach ($context as $key => $value) {
            $contextStr .= "- {$key}: {$value}\n";
        }

        $prompt = <<<PROMPT
            Tu es un assistant juridique français. Génère une clause "{$type}" pour un contrat.
            {$contextStr}
            Réponds UNIQUEMENT avec la clause, sans explication.

            Clause :
            PROMPT;

        return $this->generate($prompt, 'tinyllama', 1024);
    }

    public function reformulate(string $content, string $style): ?string
    {
        $styleInstructions = match ($style) {
            'simple' => 'Simplifie le langage, évite le jargon juridique.',
            'formel' => 'Utilise un langage juridique formel.',
            'concis' => 'Rends le texte plus court et direct.',
            'detaille' => 'Développe chaque point avec plus de détails.',
            default => 'Améliore la clarté.',
        };

        $prompt = <<<PROMPT
            Tu es un assistant juridique français. {$styleInstructions}

            Texte original :
            {$content}

            Texte reformulé (complet) :
            PROMPT;

        return $this->generate($prompt, 'tinyllama', 4096);
    }

    public function summarize(string $content): ?string
    {
        $prompt = <<<PROMPT
            Tu es un assistant juridique français. Résume ce document en points clés :
            - Parties impliquées
            - Objet du contrat  
            - Obligations principales
            - Durée et conditions

            Document :
            {$content}

            Résumé structuré :
            PROMPT;

        return $this->generate($prompt, 'tinyllama', 1024);
    }

    public function checkCompliance(string $content): ?string
    {
        $prompt = <<<PROMPT
            Tu es un juriste français. Analyse ce document et liste :
            1. Clauses manquantes importantes
            2. Formulations à améliorer
            3. Points d'attention juridiques

            Document :
            {$content}

            Analyse :
            PROMPT;

        return $this->generate($prompt, 'tinyllama', 1024);
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->baseUrl}/api/tags", [
                'timeout' => 5,
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function generateStream(string $prompt, string $model = 'mistral', int $maxTokens = 2048): \Generator
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => "{$this->baseUrl}/api/generate",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'prompt' => $prompt,
                'stream' => true,
                'options' => [
                    'num_predict' => $maxTokens,
                    'temperature' => 0.7,
                ],
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$buffer) {
                $buffer .= $data;
                return strlen($data);
            },
            CURLOPT_TIMEOUT => 300,
        ]);

        $buffer = '';

        // On utilise une approche différente avec file_get_contents et stream context
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode([
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => true,
                    'options' => [
                        'num_predict' => $maxTokens,
                        'temperature' => 0.7,
                    ],
                ]),
                'timeout' => 300,
            ],
        ]);

        $stream = fopen("{$this->baseUrl}/api/generate", 'r', false, $context);

        if ($stream) {
            while (!feof($stream)) {
                $line = fgets($stream);
                if ($line) {
                    $json = json_decode($line, true);
                    if (isset($json['response'])) {
                        yield $json['response'];
                    }
                    if (isset($json['done']) && $json['done']) {
                        break;
                    }
                }
            }
            fclose($stream);
        }
    }
}
