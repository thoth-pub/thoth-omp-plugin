<?php

/**
 * @file plugins/generic/thoth/classes/formatters/ThothMarkupFormatter.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMarkupFormatter
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Formats HTML markup for Thoth text fields
 */

namespace APP\plugins\generic\thoth\classes\formatters;

class ThothMarkupFormatter
{
    public function format(string $content): string
    {
        $content = trim($content);
        if (!$this->needsStructuralFormatting($content)) {
            return $content;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div>' . $content . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$loaded) {
            return $content;
        }

        $wrapper = $this->getValueWrapper($document) ?? $document->getElementsByTagName('div')->item(0);
        if ($wrapper === null) {
            return $content;
        }

        $blocks = [];
        $inlineContent = '';
        foreach (iterator_to_array($wrapper->childNodes) as $node) {
            $this->appendMarkupNode($document, $node, $blocks, $inlineContent);
        }
        $this->flushParagraph($blocks, $inlineContent);

        return $this->removeBreaks(implode('', $blocks));
    }

    private function needsStructuralFormatting(string $content): bool
    {
        return preg_match('/<div\b[^>]*class=["\'][^"\']*\bvalue\b/i', $content) === 1
            || preg_match('/<br\b/i', $content) === 1
            || preg_match('/<\/?(ul|ol)\b/i', $content) === 1;
    }

    private function getValueWrapper(\DOMDocument $document): ?\DOMElement
    {
        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " value ")]');
        $node = $nodes !== false ? $nodes->item(0) : null;

        return $node instanceof \DOMElement ? $node : null;
    }

    private function appendMarkupNode(
        \DOMDocument $document,
        \DOMNode $node,
        array &$blocks,
        string &$inlineContent
    ): void {
        if ($node instanceof \DOMElement) {
            $tagName = strtolower($node->tagName);

            if ($tagName === 'br') {
                $this->flushParagraph($blocks, $inlineContent);
                return;
            }

            if ($tagName === 'p') {
                $this->flushParagraph($blocks, $inlineContent);
                $this->appendParagraphNode($document, $node, $blocks);
                return;
            }

            if (in_array($tagName, ['ul', 'ol'], true)) {
                $this->flushParagraph($blocks, $inlineContent);
                $blocks[] = trim($document->saveHTML($node));
                return;
            }
        }

        $inlineContent .= $document->saveHTML($node);
    }

    private function appendParagraphNode(\DOMDocument $document, \DOMElement $paragraph, array &$blocks): void
    {
        $inlineContent = '';
        foreach (iterator_to_array($paragraph->childNodes) as $node) {
            $this->appendMarkupNode($document, $node, $blocks, $inlineContent);
        }
        $this->flushParagraph($blocks, $inlineContent);
    }

    private function flushParagraph(array &$blocks, string &$inlineContent): void
    {
        $content = trim($inlineContent);
        if ($content !== '') {
            $blocks[] = sprintf('<p>%s</p>', $content);
        }

        $inlineContent = '';
    }

    private function removeBreaks(string $content): string
    {
        return preg_replace('/<br\b[^>]*>/i', ' ', $content) ?? $content;
    }
}
