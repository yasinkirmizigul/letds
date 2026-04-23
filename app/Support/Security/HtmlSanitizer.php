<?php

namespace App\Support\Security;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'a',
        'blockquote',
        'br',
        'code',
        'div',
        'em',
        'figcaption',
        'figure',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'hr',
        'img',
        'li',
        'ol',
        'p',
        'pre',
        'span',
        'strong',
        'table',
        'tbody',
        'td',
        'th',
        'thead',
        'tr',
        'u',
        'ul',
    ];

    private const DROP_WITH_CONTENT = [
        'applet',
        'base',
        'button',
        'embed',
        'form',
        'iframe',
        'input',
        'link',
        'meta',
        'object',
        'script',
        'select',
        'style',
        'textarea',
    ];

    private const GLOBAL_ALLOWED_ATTRIBUTES = [
        'class',
        'title',
    ];

    private const TAG_ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    public static function sanitize(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><body><div id="__sanitizer_root__">' . $html . '</div></body></html>';
        $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $root = self::findRootElement($document);

        if ($root instanceof DOMElement) {
            self::sanitizeNode($root);
        }

        $result = '';

        if ($root instanceof DOMElement) {
            foreach ($root->childNodes as $child) {
                $result .= $document->saveHTML($child);
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $result = trim($result);

        return $result !== '' ? $result : null;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        for ($child = $node->firstChild; $child !== null; $child = $next) {
            $next = $child->nextSibling;

            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if ($child instanceof DOMText) {
                continue;
            }

            if (!$child instanceof DOMElement) {
                $node->removeChild($child);
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                self::unwrapNode($child);
                continue;
            }

            self::sanitizeAttributes($child, $tag);
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = array_merge(
            self::GLOBAL_ALLOWED_ATTRIBUTES,
            self::TAG_ALLOWED_ATTRIBUTES[$tag] ?? []
        );

        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);

            if ($attribute === null) {
                continue;
            }

            $name = strtolower($attribute->nodeName);
            $value = trim((string) $attribute->nodeValue);

            if (str_starts_with($name, 'on') || !in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if ($name === 'href' && !self::isSafeUrl($value, true)) {
                $element->removeAttribute('href');
                continue;
            }

            if ($name === 'src' && !self::isSafeUrl($value, false)) {
                $element->removeAttribute('src');
                continue;
            }

            if (in_array($name, ['width', 'height', 'colspan', 'rowspan'], true) && !preg_match('/^\d{1,4}$/', $value)) {
                $element->removeAttribute($name);
                continue;
            }

            if ($name === 'target') {
                $target = strtolower($value);

                if (!in_array($target, ['_blank', '_self'], true)) {
                    $element->removeAttribute('target');
                    continue;
                }

                if ($target === '_blank') {
                    $rel = strtolower(trim((string) $element->getAttribute('rel')));
                    $tokens = collect(preg_split('/\s+/', $rel ?: ''))
                        ->filter(fn ($token) => is_string($token) && $token !== '')
                        ->map(fn ($token) => strtolower($token))
                        ->push('noopener')
                        ->push('noreferrer')
                        ->unique()
                        ->values()
                        ->all();

                    $element->setAttribute('rel', implode(' ', $tokens));
                }
            }
        }
    }

    private static function isSafeUrl(string $value, bool $allowAnchors): bool
    {
        if ($value === '') {
            return false;
        }

        $normalized = strtolower($value);

        if (str_starts_with($normalized, 'javascript:') || str_starts_with($normalized, 'data:')) {
            return false;
        }

        if ($allowAnchors && str_starts_with($value, '#')) {
            return true;
        }

        if (preg_match('#^(https?:|mailto:|tel:)#i', $value)) {
            return true;
        }

        return str_starts_with($value, '/');
    }

    private static function unwrapNode(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function findRootElement(DOMDocument $document): ?DOMElement
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if (!$body instanceof DOMElement) {
            return null;
        }

        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement && $child->getAttribute('id') === '__sanitizer_root__') {
                return $child;
            }
        }

        return null;
    }
}
