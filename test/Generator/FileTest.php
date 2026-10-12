<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use PHPUnit\Framework\Attributes\DataProvider;
use Quidam\Definitions\Extension\FileExtensionInterface;
use Quidam\Exception\InvalidArgumentException;

final class FileTest extends GeneratorTestCase
{
    public function testMimeTypesAndExtensions(): void
    {
        $file = $this->dummy->ext(FileExtensionInterface::class);

        $this->assertAlwaysMatches('/^(application|image|audio|video|text)\/[a-z0-9.+-]+$/', static fn (): string => $file->mimeType());
        $this->assertAlwaysMatches('/^image\//', static fn (): string => $file->mimeType('image'));
        $this->assertAlwaysMatches('/^[a-z0-9]{2,4}$/', static fn (): string => $file->extension());

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertStringStartsWith('video/', $file->mimeTypeForExtension($file->extension('video')));
            $extension = $file->extension();
            self::assertNotEmpty($file->mimeTypeForExtension($extension));
        }
    }

    public function testFileNameMatchesCategory(): void
    {
        $file = $this->dummy->ext(FileExtensionInterface::class);

        $this->assertAlwaysMatches('/^[a-z-]+([-_][a-z0-9]+)?\.[a-z0-9]+$|^IMG_\d{4}\.[a-z]+$/', static fn (): string => $file->fileName());
        $this->assertAlwaysMatches('/\.pdf$/', static fn (): string => $file->fileName('pdf'));
        $this->assertAlwaysMatches('/\.pdf$/', static fn (): string => $file->fileName('.pdf'));
        $this->assertAlwaysMatches('/\.custom$/', static fn (): string => $file->fileName('custom'));

        $images = ['avatar', 'background', 'banner', 'cover', 'diagram', 'header', 'icon', 'image', 'logo', 'photo', 'picture', 'profile', 'scan', 'screenshot', 'thumbnail'];
        $pattern = '/^((' . implode('|', $images) . ')([-_][a-z0-9]+)?|IMG_\d{4})\.png$/';
        $this->assertAlwaysMatches($pattern, static fn (): string => $file->fileName('png'));
        $this->assertAlwaysMatches('/^(?!IMG_)[a-z]+([-_][a-z0-9]+)?\.ico$/', static fn (): string => $file->fileName('ico'));
    }

    public function testMimeTypeForExtension(): void
    {
        $file = $this->dummy->ext(FileExtensionInterface::class);

        self::assertSame('image/jpeg', $file->mimeTypeForExtension('jpg'));
        self::assertSame('image/jpeg', $file->mimeTypeForExtension('.JPEG'));
        self::assertSame('application/pdf', $file->mimeTypeForExtension('pdf'));
        self::assertSame('application/yaml', $file->mimeTypeForExtension('yml'));
        self::assertSame('text/markdown', $file->mimeTypeForExtension('md'));
    }

    public function testFileSize(): void
    {
        $file = $this->dummy->ext(FileExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $size = $file->fileSize();
            self::assertGreaterThanOrEqual(1024, $size);
            self::assertLessThanOrEqual(10485760, $size);
        }

        $this->assertAlwaysMatches('/^\d+(\.\d)? (KB|MB)$/', static fn (): string => $file->fileSizeFormatted());
    }

    /** @return iterable<array{int, string}> */
    public static function formattedSizes(): iterable
    {
        yield [0, '0 B'];
        yield [1023, '1023 B'];
        yield [1024, '1 KB'];
        yield [1536, '1.5 KB'];
        yield [1048575, '1 MB'];
        yield [2516582, '2.4 MB'];
        yield [1073741824, '1 GB'];
    }

    #[DataProvider('formattedSizes')]
    public function testFileSizeFormatted(int $bytes, string $expected): void
    {
        self::assertSame($expected, $this->dummy->ext(FileExtensionInterface::class)->fileSizeFormatted($bytes, $bytes));
    }

    public function testUnknownCategory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('use one of: document, image');
        $this->dummy->ext(FileExtensionInterface::class)->mimeType('spreadsheet');
    }

    public function testUnknownExtension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(FileExtensionInterface::class)->mimeTypeForExtension('xyz');
    }

    public function testInvalidSizeRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(FileExtensionInterface::class)->fileSize(10, 5);
    }

    public function testPaths(): void
    {
        $file = $this->dummy->ext(FileExtensionInterface::class);

        $this->assertAlwaysMatches('#^(/[a-z0-9._-]+)+$#', static fn (): string => $file->directoryPath());
        $this->assertAlwaysMatches('#^(/[a-z0-9._-]+)+/[A-Za-z0-9_-]+\.pdf$#', static fn (): string => $file->filePath('pdf'));
        $this->assertAlwaysMatches('/^[CD]:(\\\\[A-Za-z0-9 ._-]+)+$/', static fn (): string => $file->directoryPath('windows'));
        $this->assertAlwaysMatches('/^[CD]:(\\\\[A-Za-z0-9 ._-]+)+\\\\[A-Za-z0-9_-]+\.csv$/', static fn (): string => $file->filePath('.csv', 'windows'));

        $this->expectException(InvalidArgumentException::class);
        $file->directoryPath('macos');
    }

    public function testCsv(): void
    {
        $csv = $this->dummy->ext(FileExtensionInterface::class)->csv(3, ';');
        $rows = array_map(static fn (string $line): array => str_getcsv($line, ';', '"', ''), explode("\n", trim($csv)));

        self::assertCount(4, $rows);
        self::assertSame(['id', 'name', 'email', 'city', 'amount', 'created_at'], $rows[0]);
        self::assertSame(['1', '2', '3'], array_column(array_slice($rows, 1), 0));
        self::assertMatchesRegularExpression('/@example\.(com|org|net)$/', (string) $rows[1][2]);
        self::assertSame("id,name,email,city,amount,created_at\n", $this->dummy->ext(FileExtensionInterface::class)->csv(0));
    }

    public function testJson(): void
    {
        $items = json_decode($this->dummy->ext(FileExtensionInterface::class)->json(2), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($items);
        self::assertCount(2, $items);
        self::assertIsArray($items[0]);
        self::assertSame(['id', 'name', 'email', 'city', 'amount', 'created_at'], array_keys($items[0]));
        self::assertSame('[]', $this->dummy->ext(FileExtensionInterface::class)->json(0));

        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(FileExtensionInterface::class)->json(-1);
    }
}
