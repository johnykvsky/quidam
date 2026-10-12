# Lorem

`$generator->lorem()` - interface `Quidam\Definitions\Extension\LoremExtensionInterface`, default implementation `Quidam\Core\Lorem`

Lorem ipsum words, sentences, paragraphs and text with length limit.

## Methods

| Method | Returns | Example |
|---|---|---|
| `word()` | `string` | `'architecto'` |
| `words(int $wordCount = 3)` | `list<string>` | `['voluptates', 'laudantium', 'magnam']` |
| `sentence(int $wordCount = 6, bool $variableWordCount = true)` | `string` | `'Pariatur veniam repudiandae saepe tempora.'` |
| `sentences(int $sentenceCount = 3)` | `list<string>` | |
| `paragraph(int $sentenceCount = 3, bool $variableSentenceCount = true)` | `string` | `'Veritatis itaque et repellat. Tenetur nostrum quasi quidem numquam cum. ...'` |
| `paragraphs(int $paragraphCount = 3)` | `list<string>` | |
| `text(int $maxCharacters = 200)` | `string` - never longer than limit | `'Repellendus ex dolores in modi magnam.'` |

## Examples

```php
$lorem = $generator->lorem();

$post = [
    'title' => $lorem->sentence(4, false),          // exactly 4 words: 'Temporibus dolores ea repudiandae.'
    'lead' => $lorem->text(160),                    // max 160 characters
    'body' => implode("\n\n", $lorem->paragraphs(4)),
    'tags' => $lorem->words(3),
];

// testing length validation: description limited to 100 characters
$tooLong = $lorem->text(150) . $lorem->text(150);
```

## Notes

* `variable...Count` randomizes the count by +/- 40% (`sentence(6)` gives 4-9 words)
* `text()` is built from words (limit < 25), sentences (< 100) or paragraphs - minimum limit is 5
* word list: 182 unique Latin words (Cicero's *De finibus*, as in classic lorem ipsum)
* for realistic text with natural word frequency (i.e. for full-text search tests) use the [Text](../addons/text.md) add-on, for HTML / Markdown the [Rich text](../addons/rich-text.md) add-on
