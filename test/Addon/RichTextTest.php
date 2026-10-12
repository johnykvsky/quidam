<?php

declare(strict_types = 1);

namespace Quidam\Test\Addon;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;
use Quidam\Definitions\Addon\RichTextInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;

final class RichTextTest extends TestCase
{
    public function testHtmlIsWellFormed(): void
    {
        $richText = Quidam::create()->ext(RichTextInterface::class);

        for ($i = 0; $i < 30; ++$i) {
            $html = $richText->html(8);
            $document = new DOMDocument();

            self::assertTrue($document->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR));
            self::assertSame(8, new DOMXPath($document)->query('/html/body/*')->length ?? 0, $html);
        }
    }

    public function testHtmlDocument(): void
    {
        $html = Quidam::create()->ext(RichTextInterface::class)->htmlDocument(3);

        self::assertStringStartsWith("<!DOCTYPE html>\n<html lang=\"en\">", $html);
        self::assertMatchesRegularExpression('/<title>([^<]+)<\/title>.*<h1>\1<\/h1>/s', $html);
        self::assertStringEndsWith("</body>\n</html>\n", $html);
    }

    public function testHtmlAndMarkdownHaveTheSameContent(): void
    {
        $html = Quidam::create(seed: 11)->ext(RichTextInterface::class)->html(10);
        $markdown = Quidam::create(seed: 11)->ext(RichTextInterface::class)->markdown(10);

        // image alt as text, tags as spaces
        $htmlText = strip_tags(str_replace('<', ' <', (string) preg_replace('/<img [^>]*alt="([^"]*)"[^>]*>/', ' $1 ', $html)));
        // link / image targets and markup characters removed
        $markdownText = (string) preg_replace('/\]\([^)]*\)|[#>*_`|\[\]!-]/', ' ', $markdown);

        $htmlWords = str_word_count(strtolower($htmlText), 1);
        $markdownWords = str_word_count(strtolower($markdownText), 1);

        self::assertSame($htmlWords, $markdownWords);
    }

    public function testMarkdownSyntax(): void
    {
        $richText = Quidam::create(seed: 1)->ext(RichTextInterface::class);
        $markdown = implode("\n\n", array_map(static fn (): string => $richText->markdown(20), range(1, 5)));

        self::assertMatchesRegularExpression('/^#{2,3} [A-Z]/m', $markdown);
        self::assertMatchesRegularExpression('/^(- |1\. )[A-Z]/m', $markdown);
        self::assertMatchesRegularExpression('/^> [A-Z]/m', $markdown);
        self::assertMatchesRegularExpression("/^```\nconst [a-z]+ = [a-z]+\\(\\d+\\);/m", $markdown);
        self::assertMatchesRegularExpression('/^\| .+ \|\n\|( --- \|)+$/m', $markdown);
        self::assertMatchesRegularExpression('/!\[[A-Z][a-z ]+\]\(https:\/\/example\.com\/images\/[a-z-]+\.jpg\)/', $markdown);
        self::assertMatchesRegularExpression('/\[[a-z ]+\]\(https:\/\/example\.com\/[a-z-]+\)/', $markdown);
    }

    public function testStartsWithParagraph(): void
    {
        self::assertStringStartsWith('<p>', Quidam::create()->ext(RichTextInterface::class)->html(1));
    }

    public function testInvalidBlocks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quidam::create()->ext(RichTextInterface::class)->markdown(0);
    }
}
