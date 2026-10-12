<?php

declare(strict_types = 1);

namespace Quidam\Addon;

use Quidam\Definitions\Addon\RichTextInterface;
use Quidam\Definitions\Extension\LoremExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * Lorem ipsum as HTML or Markdown - headings, paragraphs with inline formatting and links, lists, quotes, code,
 * tables and images. For WYSIWYG editors, HTML sanitizers, Markdown renderers, CMS and email templates.
 *
 * Both formats are rendered from the same blocks - with the same seed, html() and markdown() have the same content.
 * Links and images point to example.com.
 *
 * @phpstan-type Inline list<array{type: 'text'|'strong'|'em'|'code'|'link', text: string, href?: string}>
 * @phpstan-type Block array{type: 'heading', level: int, text: string}
 *                   |array{type: 'paragraph'|'quote', inline: Inline}
 *                   |array{type: 'list', ordered: bool, items: list<Inline>}
 *                   |array{type: 'code', code: string}
 *                   |array{type: 'table', header: list<string>, rows: list<list<string>>}
 *                   |array{type: 'image', alt: string, src: string}
 */
class RichText implements RichTextInterface
{
    /** @var array<string, int> block type => weight */
    protected const array BLOCK_WEIGHTS = [
        'paragraph' => 40, 'heading' => 14, 'list' => 14, 'quote' => 8, 'code' => 8, 'table' => 8, 'image' => 8,
    ];

    /** @var array<string, int> inline style => weight */
    protected const array INLINE_WEIGHTS = ['strong' => 3, 'em' => 3, 'link' => 3, 'code' => 1];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly LoremExtensionInterface $lorem,
    ) {
    }

    /**
     * HTML fragment of $blocks block elements (h2/h3, p, ul/ol, blockquote, pre, table, figure)
     */
    public function html(int $blocks = 5): string
    {
        return implode("\n", array_map($this->renderHtmlBlock(...), $this->blocks($blocks))) . "\n";
    }

    /**
     * Complete HTML5 document with title, h1 and $blocks block elements
     */
    public function htmlDocument(int $blocks = 5): string
    {
        $title = $this->escape($this->title());

        return "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n<meta charset=\"utf-8\">\n<title>" . $title . "</title>\n</head>\n<body>\n"
            . '<h1>' . $title . "</h1>\n"
            . $this->html($blocks)
            . "</body>\n</html>\n";
    }

    /**
     * CommonMark / GitHub flavoured Markdown of $blocks blocks (tables use GFM syntax)
     */
    public function markdown(int $blocks = 5): string
    {
        return implode("\n\n", array_map($this->renderMarkdownBlock(...), $this->blocks($blocks))) . "\n";
    }

    /** @return list<Block> */
    protected function blocks(int $count): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Number of blocks must be at least 1');
        }

        $blocks = [];
        for ($i = 0; $i < $count; ++$i) {
            $blocks[] = $this->block($i === 0 ? 'paragraph' : $this->randomizer->weightedElement(static::BLOCK_WEIGHTS));
        }

        return $blocks;
    }

    /** @return Block */
    protected function block(string $type): array
    {
        return match ($type) {
            'heading' => ['type' => 'heading', 'level' => $this->randomizer->getInt(2, 3), 'text' => $this->title()],
            'list' => [
                'type' => 'list',
                'ordered' => $this->randomizer->getBool(30),
                'items' => array_map(fn (): array => $this->inline(3, 8), range(1, $this->randomizer->getInt(2, 5))),
            ],
            'quote' => ['type' => 'quote', 'inline' => $this->inline(8, 20)],
            'code' => ['type' => 'code', 'code' => $this->code()],
            'table' => $this->table(),
            'image' => [
                'type' => 'image',
                'alt' => ucfirst(implode(' ', $this->lorem->words($this->randomizer->getInt(2, 5)))),
                'src' => 'https://example.com/images/' . implode('-', $this->lorem->words(2)) . '.jpg',
            ],
            default => ['type' => 'paragraph', 'inline' => $this->inline(20, 60)],
        };
    }

    /**
     * Sentences of $min-$max words in total, some words styled (bold, italic, link, code) - never across sentence end
     *
     * @return Inline
     */
    protected function inline(int $min, int $max): array
    {
        $words = $this->sentenceWords($this->randomizer->getInt($min, $max));
        $segments = [];
        $plain = [];

        for ($i = 0; $i < count($words); ++$i) {
            if ($i > 0 && !str_ends_with($words[$i], '.') && $this->randomizer->getBool(8)) {
                if ($plain !== []) {
                    $segments[] = ['type' => 'text', 'text' => implode(' ', $plain) . ' '];
                    $plain = [];
                }

                // up to 3 words, stopping before the word which ends the sentence
                $length = 1;
                $wanted = $this->randomizer->getInt(1, 3);
                while ($length < $wanted && !str_ends_with($words[$i + $length], '.')) {
                    ++$length;
                }

                $text = implode(' ', array_slice($words, $i, $length));
                $segments[] = match ($this->randomizer->weightedElement(static::INLINE_WEIGHTS)) {
                    'strong' => ['type' => 'strong', 'text' => $text],
                    'em' => ['type' => 'em', 'text' => $text],
                    'code' => ['type' => 'code', 'text' => $text],
                    default => ['type' => 'link', 'text' => $text, 'href' => 'https://example.com/' . strtolower(str_replace(' ', '-', $text))],
                };
                $segments[] = ['type' => 'text', 'text' => ' '];
                $i += $length - 1;
                continue;
            }

            $plain[] = $words[$i];
        }

        $segments[] = ['type' => 'text', 'text' => implode(' ', $plain)];

        return $segments;
    }

    /**
     * $count words split into sentences of 5-14 words - first word capitalized, last one with a dot
     *
     * @return list<string>
     */
    protected function sentenceWords(int $count): array
    {
        $words = [];

        while ($count > 0) {
            $length = min($count, $this->randomizer->getInt(5, 14));
            // no 1-2 word sentence at the end - rest is added to this one
            if ($count - $length < 3) {
                $length = $count;
            }

            $sentence = $this->lorem->words($length);
            $sentence[0] = ucfirst($sentence[0]);
            $sentence[$length - 1] .= '.';
            array_push($words, ...$sentence);
            $count -= $length;
        }

        return $words;
    }

    /** @return array{type: 'table', header: list<string>, rows: list<list<string>>} */
    protected function table(): array
    {
        $columns = $this->randomizer->getInt(2, 4);
        $rows = [];

        for ($row = $this->randomizer->getInt(2, 5); $row > 0; --$row) {
            $cells = [ucfirst($this->lorem->word())];
            for ($column = 1; $column < $columns; ++$column) {
                $cells[] = (string) $this->randomizer->getInt(1, 999);
            }

            $rows[] = $cells;
        }

        return ['type' => 'table', 'header' => array_map(ucfirst(...), $this->lorem->words($columns)), 'rows' => $rows];
    }

    protected function code(): string
    {
        $lines = [];
        for ($line = $this->randomizer->getInt(1, 4); $line > 0; --$line) {
            [$variable, $function] = $this->lorem->words(2);
            $lines[] = sprintf('const %s = %s(%d);', $variable, $function, $this->randomizer->getInt(0, 100));
        }

        return implode("\n", $lines);
    }

    protected function title(): string
    {
        return ucfirst(implode(' ', $this->lorem->words($this->randomizer->getInt(2, 6))));
    }

    /** @param Block $block */
    protected function renderHtmlBlock(array $block): string
    {
        return match ($block['type']) {
            'heading' => sprintf('<h%d>%s</h%1$d>', $block['level'], $this->escape($block['text'])),
            'paragraph' => '<p>' . $this->renderHtmlInline($block['inline']) . '</p>',
            'quote' => '<blockquote><p>' . $this->renderHtmlInline($block['inline']) . '</p></blockquote>',
            'list' => sprintf(
                "<%s>\n%s\n</%1\$s>",
                $block['ordered'] ? 'ol' : 'ul',
                implode("\n", array_map(fn (array $item): string => '<li>' . $this->renderHtmlInline($item) . '</li>', $block['items'])),
            ),
            'code' => '<pre><code>' . $this->escape($block['code']) . '</code></pre>',
            'table' => "<table>\n<thead>\n<tr>" . $this->htmlCells('th', $block['header']) . "</tr>\n</thead>\n<tbody>\n"
                . implode("\n", array_map(fn (array $row): string => '<tr>' . $this->htmlCells('td', $row) . '</tr>', $block['rows']))
                . "\n</tbody>\n</table>",
            'image' => sprintf('<figure><img src="%s" alt="%s"></figure>', $this->escape($block['src']), $this->escape($block['alt'])),
        };
    }

    /** @param Inline $inline */
    protected function renderHtmlInline(array $inline): string
    {
        $html = '';
        foreach ($inline as $segment) {
            $text = $this->escape($segment['text']);
            $html .= match ($segment['type']) {
                'strong' => '<strong>' . $text . '</strong>',
                'em' => '<em>' . $text . '</em>',
                'code' => '<code>' . $text . '</code>',
                'link' => sprintf('<a href="%s">%s</a>', $this->escape($segment['href'] ?? ''), $text),
                'text' => $text,
            };
        }

        return $html;
    }

    /** @param list<string> $cells */
    protected function htmlCells(string $tag, array $cells): string
    {
        return implode('', array_map(fn (string $cell): string => sprintf('<%s>%s</%1$s>', $tag, $this->escape($cell)), $cells));
    }

    /** @param Block $block */
    protected function renderMarkdownBlock(array $block): string
    {
        return match ($block['type']) {
            'heading' => str_repeat('#', $block['level']) . ' ' . $block['text'],
            'paragraph' => $this->renderMarkdownInline($block['inline']),
            'quote' => '> ' . $this->renderMarkdownInline($block['inline']),
            'list' => implode("\n", array_map(
                fn (int $index, array $item): string => ($block['ordered'] ? ($index + 1) . '.' : '-') . ' ' . $this->renderMarkdownInline($item),
                array_keys($block['items']),
                $block['items'],
            )),
            'code' => "```\n" . $block['code'] . "\n```",
            'table' => '| ' . implode(' | ', $block['header']) . " |\n"
                . '|' . str_repeat(' --- |', count($block['header'])) . "\n"
                . implode("\n", array_map(static fn (array $row): string => '| ' . implode(' | ', $row) . ' |', $block['rows'])),
            'image' => sprintf('![%s](%s)', $block['alt'], $block['src']),
        };
    }

    /** @param Inline $inline */
    protected function renderMarkdownInline(array $inline): string
    {
        $markdown = '';
        foreach ($inline as $segment) {
            $markdown .= match ($segment['type']) {
                'strong' => '**' . $segment['text'] . '**',
                'em' => '_' . $segment['text'] . '_',
                'code' => '`' . $segment['text'] . '`',
                'link' => sprintf('[%s](%s)', $segment['text'], $segment['href'] ?? ''),
                'text' => $segment['text'],
            };
        }

        return $markdown;
    }

    protected function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
