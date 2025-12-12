<?php

namespace App\Service;

class MarkdownParser
{
    public function parse(string $markdown): string
    {
        $html = $markdown;
        
        // Headers
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        
        // Bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        
        // Italic
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        
        // Lists
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $html);
        
        // Paragraphs (lignes non vides qui ne sont pas déjà des tags)
        $lines = explode("\n", $html);
        $result = [];
        $inParagraph = false;
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            if (empty($trimmed)) {
                if ($inParagraph) {
                    $result[] = '</p>';
                    $inParagraph = false;
                }
                $result[] = '';
            } elseif (preg_match('/^<(h[1-6]|ul|li|ol|div|table|tr|td|th)/', $trimmed)) {
                if ($inParagraph) {
                    $result[] = '</p>';
                    $inParagraph = false;
                }
                $result[] = $line;
            } else {
                if (!$inParagraph && !preg_match('/<\/?(h[1-6]|ul|li|ol|div)>/', $trimmed)) {
                    $result[] = '<p>' . $line;
                    $inParagraph = true;
                } else {
                    $result[] = $line;
                }
            }
        }
        
        if ($inParagraph) {
            $result[] = '</p>';
        }
        
        return implode("\n", $result);
    }
}