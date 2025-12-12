<?php

namespace App\Twig;

use App\Service\MarkdownParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    public function __construct(
        private MarkdownParser $parser
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown_to_html', [$this, 'markdownToHtml'], ['is_safe' => ['html']]),
        ];
    }

    public function markdownToHtml(string $content): string
    {
        return $this->parser->parse($content);
    }
}