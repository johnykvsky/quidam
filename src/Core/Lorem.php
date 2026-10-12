<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\LoremExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

class Lorem implements LoremExtensionInterface
{
    /** @var list<string> */
    protected const array WORDS = [
        'alias', 'consequatur', 'aut', 'perferendis', 'sit', 'voluptatem', 'accusantium', 'doloremque', 'aperiam',
        'eaque', 'ipsa', 'quae', 'ab', 'illo', 'inventore', 'veritatis', 'et', 'quasi', 'architecto', 'beatae', 'vitae',
        'dicta', 'sunt', 'explicabo', 'aspernatur', 'odit', 'fugit', 'sed', 'quia', 'consequuntur', 'magni', 'dolores',
        'eos', 'qui', 'ratione', 'sequi', 'nesciunt', 'neque', 'dolorem', 'ipsum', 'dolor', 'amet', 'consectetur',
        'adipisci', 'velit', 'non', 'numquam', 'eius', 'modi', 'tempora', 'incidunt', 'ut', 'labore', 'dolore',
        'magnam', 'aliquam', 'quaerat', 'enim', 'ad', 'minima', 'veniam', 'quis', 'nostrum', 'exercitationem', 'ullam',
        'corporis', 'nemo', 'ipsam', 'voluptas', 'suscipit', 'laboriosam', 'nisi', 'aliquid', 'ex', 'ea', 'commodi',
        'autem', 'vel', 'eum', 'iure', 'reprehenderit', 'in', 'voluptate', 'esse', 'quam', 'nihil', 'molestiae',
        'iusto', 'odio', 'dignissimos', 'ducimus', 'blanditiis', 'praesentium', 'laudantium', 'totam', 'rem',
        'voluptatum', 'deleniti', 'atque', 'corrupti', 'quos', 'quas', 'molestias', 'excepturi', 'sint', 'occaecati',
        'cupiditate', 'provident', 'perspiciatis', 'unde', 'omnis', 'iste', 'natus', 'error', 'similique', 'culpa',
        'officia', 'deserunt', 'mollitia', 'animi', 'id', 'est', 'laborum', 'dolorum', 'fuga', 'harum', 'quidem',
        'rerum', 'facilis', 'expedita', 'distinctio', 'nam', 'libero', 'tempore', 'cum', 'soluta', 'nobis', 'eligendi',
        'optio', 'cumque', 'impedit', 'quo', 'porro', 'quisquam', 'minus', 'quod', 'maxime', 'placeat', 'facere',
        'possimus', 'assumenda', 'repellendus', 'temporibus', 'quibusdam', 'illum', 'fugiat', 'nulla', 'pariatur', 'at',
        'vero', 'accusamus', 'officiis', 'debitis', 'necessitatibus', 'saepe', 'eveniet', 'voluptates', 'repudiandae',
        'recusandae', 'itaque', 'earum', 'hic', 'tenetur', 'a', 'sapiente', 'delectus', 'reiciendis', 'voluptatibus',
        'maiores', 'doloribus', 'asperiores', 'repellat',
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    public function word(): string
    {
        return $this->randomizer->randomElement(static::WORDS);
    }

    /** @return list<string> */
    public function words(int $wordCount = 3): array
    {
        $words = [];
        for ($i = 0; $i < $wordCount; ++$i) {
            $words[] = $this->word();
        }

        return $words;
    }

    /** @param bool $variableWordCount If true, word count is randomized by +/- 40% */
    public function sentence(int $wordCount = 6, bool $variableWordCount = true): string
    {
        if ($wordCount < 1) {
            throw new InvalidArgumentException('$wordCount should be at least 1');
        }

        if ($variableWordCount) {
            $wordCount = $this->randomizeNumberOfElements($wordCount);
        }

        return ucfirst(implode(' ', $this->words($wordCount))) . '.';
    }

    /** @return list<string> */
    public function sentences(int $sentenceCount = 3): array
    {
        $sentences = [];
        for ($i = 0; $i < $sentenceCount; ++$i) {
            $sentences[] = $this->sentence();
        }

        return $sentences;
    }

    /** @param bool $variableSentenceCount If true, sentence count is randomized by +/- 40% */
    public function paragraph(int $sentenceCount = 3, bool $variableSentenceCount = true): string
    {
        if ($sentenceCount < 1) {
            throw new InvalidArgumentException('$sentenceCount should be at least 1');
        }

        if ($variableSentenceCount) {
            $sentenceCount = $this->randomizeNumberOfElements($sentenceCount);
        }

        return implode(' ', $this->sentences($sentenceCount));
    }

    /** @return list<string> */
    public function paragraphs(int $paragraphCount = 3): array
    {
        $paragraphs = [];
        for ($i = 0; $i < $paragraphCount; ++$i) {
            $paragraphs[] = $this->paragraph();
        }

        return $paragraphs;
    }

    /**
     * Text no longer than $maxCharacters. Built from paragraphs (>= 100 chars), sentences (>= 25 chars) or words.
     */
    public function text(int $maxCharacters = 200): string
    {
        if ($maxCharacters < 5) {
            throw new InvalidArgumentException('$maxCharacters should be at least 5');
        }

        if ($maxCharacters >= 25) {
            $chunk = $maxCharacters < 100 ? $this->sentence(...) : $this->paragraph(...);

            for ($attempt = 0; $attempt < 10; ++$attempt) {
                $text = $this->joinUpTo($chunk, $maxCharacters);

                if ($text !== '') {
                    return $text;
                }
            }
        }

        // words form a single sentence, one character is reserved for the trailing dot
        do {
            $text = $this->joinUpTo($this->word(...), $maxCharacters - 1);
        } while ($text === '');

        return ucfirst($text) . '.';
    }

    /**
     * Joins chunks with spaces as long as result fits in $limit characters
     *
     * @param \Closure(): string $chunk
     */
    protected function joinUpTo(\Closure $chunk, int $limit): string
    {
        $text = '';

        while (true) {
            $candidate = ($text === '' ? '' : $text . ' ') . $chunk();

            if (mb_strlen($candidate) > $limit) {
                return $text;
            }

            $text = $candidate;
        }
    }

    protected function randomizeNumberOfElements(int $number): int
    {
        return (int) ($number * $this->randomizer->getInt(60, 140) / 100) + 1;
    }
}
