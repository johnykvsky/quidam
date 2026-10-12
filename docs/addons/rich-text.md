# Rich text

`$generator->ext(RichTextInterface::class)` - opt-in add-on, interface `Quidam\Definitions\Addon\RichTextInterface`, default implementation `Quidam\Addon\RichText`

Lorem ipsum as HTML or Markdown (FakerPHP's `randomHtml()`, with Markdown from the same blocks): headings, paragraphs with bold / italic / links / inline code, lists, quotes, code blocks, tables and images. For WYSIWYG editors, HTML sanitizers, Markdown renderers, CMS content and email templates.

```php
use Quidam\Definitions\Addon\RichTextInterface;

$richText = $generator->ext(RichTextInterface::class);

$richText->html();              // 5 block elements
$richText->html(20);            // longer article
$richText->htmlDocument();      // full HTML5 page with <title> and <h1>
$richText->markdown(8);         // CommonMark / GitHub flavoured Markdown
```

## Methods

| Method | Returns |
|---|---|
| `html(int $blocks = 5)` | `string` - HTML fragment of `$blocks` block elements |
| `htmlDocument(int $blocks = 5)` | `string` - `<!DOCTYPE html>` document: title, `<h1>` and `$blocks` blocks |
| `markdown(int $blocks = 5)` | `string` - Markdown of `$blocks` blocks, separated by blank lines |

## Output

```markdown
In ratione repudiandae fugit autem. [Voluptates illum](https://example.com/voluptates-illum) minus quia atque. Ipsa optio repellendus iste fugit quia. Nam dolorum ullam labore `esse` magni aut aut placeat.

### Harum tempore dignissimos itaque

- Fugit nihil **hic sequi** sit.
- Eos molestiae esse aspernatur modi.

| Quia | Sunt | Dolor |
| --- | --- | --- |
| Ipsum | 412 | 87 |
```

Blocks: `<p>` (always first), `<h2>` / `<h3>`, `<ul>` / `<ol>`, `<blockquote>`, `<pre><code>`, `<table>` with `<thead>`, `<figure><img>`. Inline: `<strong>`, `<em>`, `<a>`, `<code>` - never across a sentence end.

## Examples

```php
// CMS article
$article = [
    'title' => $generator->lorem()->sentence(5),
    'body' => $richText->html(12),
];

// sanitizer test - output must keep the allowed tags
$clean = $sanitizer->sanitize($richText->html(10));
self::assertStringContainsString('<p>', $clean);
```

## Notes

* `html()` and `markdown()` are rendered from the same blocks - with the same seed both have the same content
* links and images point to `https://example.com/...` (reserved domain) - images are not real files, they don't load
* text is lorem ipsum from [`Lorem`](../extensions/lorem.md) - replacing `LoremExtensionInterface` with `withDefinition()` changes it too
* HTML is escaped, `<img>` has `alt`; Markdown tables use GFM syntax (CommonMark core has no tables)
* block mix is set by `BLOCK_WEIGHTS`, inline styles by `INLINE_WEIGHTS` - override them in a subclass
* for hostile input (scripts, broken markup) see [Edge cases](edge-cases.md) category `injection`
