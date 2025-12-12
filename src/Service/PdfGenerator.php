<?php

namespace App\Service;

use App\Entity\Document;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGenerator
{
    public function __construct(
        private Environment $twig,
        private string $storagePath
    ) {}

    public function generate(Document $document): string
    {
        // Configuration DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        
        // Générer le HTML depuis Twig
        $html = $this->twig->render('pdf/document.html.twig', [
            'document' => $document,
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Créer le dossier si nécessaire
        $directory = $this->storagePath . '/documents';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Sauvegarder le fichier
        $filename = sprintf('document_%d_%s.pdf', $document->getId(), date('Ymd_His'));
        $filepath = $directory . '/' . $filename;
        
        file_put_contents($filepath, $dompdf->output());
        
        return $filepath;
    }

    public function stream(Document $document): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        
        $html = $this->twig->render('pdf/document.html.twig', [
            'document' => $document,
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    }
}