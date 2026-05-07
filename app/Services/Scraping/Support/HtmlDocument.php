<?php

namespace App\Services\Scraping\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Symfony\Component\CssSelector\CssSelectorConverter;

class HtmlDocument
{
    protected DOMDocument $document;

    protected DOMXPath $xpath;

    protected CssSelectorConverter $converter;

    public function __construct(
        string $html,
        protected string $baseUrl = ''
    ) {
        $this->document = new DOMDocument('1.0', 'UTF-8');
        $this->converter = new CssSelectorConverter();

        $previous = libxml_use_internal_errors(true);
        $this->document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->xpath = new DOMXPath($this->document);
        $this->baseUrl = $this->detectBaseUrl($this->baseUrl);
    }

    /**
     * @return HtmlNode[]
     */
    public function filter(string $selector, ?DOMNode $contextNode = null): array
    {
        $expression = $this->converter->toXPath($selector);
        $nodes = $this->xpath->query($expression, $contextNode);

        if ($nodes === false) {
            return [];
        }

        $matches = [];

        foreach ($nodes as $node) {
            $matches[] = new HtmlNode($this, $node);
        }

        return $matches;
    }

    public function first(string $selector, ?DOMNode $contextNode = null): ?HtmlNode
    {
        return $this->filter($selector, $contextNode)[0] ?? null;
    }

    public function resolveUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            $scheme = parse_url($this->baseUrl, PHP_URL_SCHEME) ?: 'https';

            return $scheme . ':' . $url;
        }

        if ($this->baseUrl === '') {
            return $url;
        }

        $parts = parse_url($this->baseUrl);

        if (!is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        if (str_starts_with($url, '/')) {
            return "{$scheme}://{$host}{$port}{$url}";
        }

        $basePath = $parts['path'] ?? '/';
        $baseDir = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
        $prefix = $baseDir === '' || $baseDir === '.' ? '' : $baseDir;

        return "{$scheme}://{$host}{$port}{$prefix}/{$url}";
    }

    public function resolveArbitraryUrl(?string $url): ?string
    {
        return $this->resolveUrl($url);
    }

    protected function detectBaseUrl(string $fallback): string
    {
        $baseNodes = $this->document->getElementsByTagName('base');
        $href = $baseNodes->length > 0
            ? trim((string) $baseNodes->item(0)?->getAttribute('href'))
            : '';

        if ($href === '') {
            return $fallback;
        }

        if (preg_match('/^https?:\/\//i', $href)) {
            return $href;
        }

        if ($fallback === '') {
            return $href;
        }

        $parts = parse_url($fallback);

        if (!is_array($parts) || empty($parts['host'])) {
            return $href;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        if (str_starts_with($href, '/')) {
            return "{$scheme}://{$host}{$port}{$href}";
        }

        return "{$scheme}://{$host}{$port}/{$href}";
    }
}

class HtmlNode
{
    public function __construct(
        protected HtmlDocument $document,
        protected DOMNode $node
    ) {
    }

    public function domNode(): DOMNode
    {
        return $this->node;
    }

    public function tagName(): ?string
    {
        return $this->node instanceof DOMElement
            ? strtolower($this->node->tagName)
            : null;
    }

    /**
     * @return HtmlNode[]
     */
    public function filter(string $selector): array
    {
        return $this->document->filter($selector, $this->node);
    }

    public function first(string $selector): ?HtmlNode
    {
        return $this->document->first($selector, $this->node);
    }

    public function parent(): ?HtmlNode
    {
        return $this->node->parentNode
            ? new HtmlNode($this->document, $this->node->parentNode)
            : null;
    }

    /**
     * @return HtmlNode[]
     */
    public function ancestors(int $maxDepth = 6): array
    {
        $ancestors = [];
        $current = $this->parent();
        $depth = 0;

        while ($current && $depth < $maxDepth) {
            $ancestors[] = $current;
            $current = $current->parent();
            $depth++;
        }

        return $ancestors;
    }

    public function text(): string
    {
        $text = html_entity_decode($this->node->textContent ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    public function innerHtml(): string
    {
        $html = '';

        foreach ($this->node->childNodes as $childNode) {
            $html .= $this->node->ownerDocument?->saveHTML($childNode) ?? '';
        }

        return trim($html);
    }

    public function outerHtml(): string
    {
        return trim($this->node->ownerDocument?->saveHTML($this->node) ?? '');
    }

    public function attr(string $name): ?string
    {
        if (!$this->node instanceof DOMElement || !$this->node->hasAttribute($name)) {
            return null;
        }

        return trim($this->node->getAttribute($name));
    }

    public function resolvedAttr(string $name): ?string
    {
        return $this->document->resolveUrl($this->attr($name));
    }

    public function resolvedAttrValue(?string $value): ?string
    {
        return $this->document->resolveArbitraryUrl($value);
    }
}
