<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\FileExtensionInterface;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Exception\RuntimeException;

class File implements FileExtensionInterface
{
    /**
     * Commonly used MIME types => extensions, grouped by category
     *
     * @var array<string, array<string, list<string>>>
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/MIME_types/Common_types
     */
    protected const array MIME_TYPES = [
        'document' => [
            'application/pdf' => ['pdf'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'application/vnd.ms-excel' => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
            'application/vnd.ms-powerpoint' => ['ppt'],
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
            'application/vnd.oasis.opendocument.text' => ['odt'],
            'application/vnd.oasis.opendocument.spreadsheet' => ['ods'],
            'application/vnd.oasis.opendocument.presentation' => ['odp'],
            'application/rtf' => ['rtf'],
            'application/epub+zip' => ['epub'],
            'text/csv' => ['csv'],
        ],
        'image' => [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'image/avif' => ['avif'],
            'image/heic' => ['heic'],
            'image/svg+xml' => ['svg'],
            'image/bmp' => ['bmp'],
            'image/tiff' => ['tif', 'tiff'],
            'image/vnd.microsoft.icon' => ['ico'],
        ],
        'audio' => [
            'audio/mpeg' => ['mp3'],
            'audio/wav' => ['wav'],
            'audio/ogg' => ['ogg', 'oga'],
            'audio/flac' => ['flac'],
            'audio/aac' => ['aac'],
            'audio/mp4' => ['m4a'],
            'audio/webm' => ['weba'],
        ],
        'video' => [
            'video/mp4' => ['mp4'],
            'video/webm' => ['webm'],
            'video/quicktime' => ['mov'],
            'video/x-msvideo' => ['avi'],
            'video/x-matroska' => ['mkv'],
            'video/mpeg' => ['mpeg', 'mpg'],
            'video/ogg' => ['ogv'],
            'video/3gpp' => ['3gp'],
        ],
        'archive' => [
            'application/zip' => ['zip'],
            'application/gzip' => ['gz'],
            'application/x-tar' => ['tar'],
            'application/x-7z-compressed' => ['7z'],
            'application/vnd.rar' => ['rar'],
            'application/x-bzip2' => ['bz2'],
            'application/x-xz' => ['xz'],
        ],
        'text' => [
            'text/plain' => ['txt', 'log'],
            'text/html' => ['html', 'htm'],
            'text/css' => ['css'],
            'text/javascript' => ['js', 'mjs'],
            'text/markdown' => ['md'],
            'text/calendar' => ['ics'],
            'text/vcard' => ['vcf'],
            'application/json' => ['json'],
            'application/xml' => ['xml'],
            'application/yaml' => ['yaml', 'yml'],
            'application/sql' => ['sql'],
        ],
    ];

    /** @var array<string, list<string>> */
    protected const array BASE_NAMES = [
        'document' => [
            'agreement', 'analysis', 'budget', 'contract', 'invoice', 'letter', 'manual', 'minutes', 'notes', 'offer',
            'plan', 'presentation', 'proposal', 'receipt', 'report', 'resume', 'specification', 'statement', 'summary',
            'timesheet',
        ],
        'image' => [
            'avatar', 'background', 'banner', 'cover', 'diagram', 'header', 'icon', 'image', 'logo', 'photo', 'picture',
            'profile', 'scan', 'screenshot', 'thumbnail',
        ],
        'audio' => [
            'audio', 'interview', 'intro', 'meeting', 'podcast', 'recording', 'sample', 'song', 'track', 'voice-note',
        ],
        'video' => [
            'clip', 'demo', 'footage', 'movie', 'presentation', 'recording', 'trailer', 'tutorial', 'video', 'webinar',
        ],
        'archive' => [
            'archive', 'assets', 'backup', 'data', 'documents', 'export', 'files', 'photos', 'project', 'release',
        ],
        'text' => [
            'changelog', 'config', 'data', 'export', 'index', 'log', 'notes', 'readme', 'script', 'settings', 'styles',
            'todo',
        ],
    ];

    /** @var list<string> */
    protected const array NAME_SUFFIX = ['final', 'draft', 'v2', 'v3', 'copy', 'old', 'new'];

    /** @var list<string> */
    protected const array PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'heic', 'png'];

    /**
     * Base directories per OS, "%user%" is replaced with a user name
     *
     * @var array<string, list<string>>
     */
    protected const array DIRECTORIES = [
        'unix' => ['/home/%user%', '/var/www/html', '/var/log', '/tmp', '/opt/app', '/srv/data', '/usr/local/share', '/mnt/backup'],
        'windows' => ['C:\\Users\\%user%', 'C:\\Program Files', 'D:\\Data', 'C:\\Windows\\Temp', 'C:\\inetpub\\wwwroot'],
    ];

    /** @var list<string> */
    protected const array SUBDIRECTORIES = [
        'archive', 'assets', 'config', 'documents', 'downloads', 'export', 'projects', 'reports', 'shared', 'uploads',
    ];

    /** @var list<string> */
    protected const array USER_NAMES = ['admin', 'alex', 'anna', 'deploy', 'dev', 'john', 'maria'];

    private const array SIZE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly StringsExtensionInterface $strings,
        protected readonly PersonExtensionInterface $person,
        protected readonly InternetExtensionInterface $internet,
        protected readonly AddressExtensionInterface $address,
        protected readonly DateTimeExtensionInterface $dateTime,
    ) {
    }

    /**
     * e.g. "application/pdf"
     *
     * @param string|null $category One of: document, image, audio, video, archive, text - or any when null
     */
    public function mimeType(?string $category = null): string
    {
        return $this->randomizer->randomKey($this->mimeTypes($category));
    }

    /**
     * File extension without dot, e.g. "pdf"
     *
     * @param string|null $category One of: document, image, audio, video, archive, text - or any when null
     */
    public function extension(?string $category = null): string
    {
        return $this->randomizer->randomElement($this->randomizer->randomElement($this->mimeTypes($category)));
    }

    /**
     * File name matching its type, e.g. "invoice_2024.pdf", "IMG_4821.jpg", "backup-3.zip"
     *
     * @param string|null $extension Extension to use (with or without dot), random when null
     */
    public function fileName(?string $extension = null): string
    {
        $extension = $extension === null ? $this->extension() : ltrim($extension, '.');
        $category = $this->categoryForExtension($extension);

        $baseNames = $category === null ? array_merge(...array_values(static::BASE_NAMES)) : static::BASE_NAMES[$category];

        // camera style name, only for photo formats
        if (in_array(strtolower($extension), static::PHOTO_EXTENSIONS, true) && $this->randomizer->getBool(25)) {
            return $this->strings->numerify('IMG_####') . '.' . $extension;
        }

        $suffix = match ($this->randomizer->getInt(0, 4)) {
            0, 1 => '',
            2 => '-' . $this->randomizer->getInt(1, 99),
            3 => '_' . $this->randomizer->getInt(2000, 2030),
            default => '_' . $this->randomizer->randomElement(static::NAME_SUFFIX),
        };

        return $this->randomizer->randomElement($baseNames) . $suffix . '.' . $extension;
    }

    /**
     * MIME type of given extension (with or without dot, case insensitive), e.g. "jpg" gives "image/jpeg"
     */
    public function mimeTypeForExtension(string $extension): string
    {
        $extension = strtolower(ltrim($extension, '.'));

        foreach (static::MIME_TYPES as $mimeTypes) {
            foreach ($mimeTypes as $mimeType => $extensions) {
                if (in_array($extension, $extensions, true)) {
                    return $mimeType;
                }
            }
        }

        throw new InvalidArgumentException(sprintf('Unknown extension "%s"', $extension));
    }

    /**
     * Absolute directory path, e.g. "/home/anna/projects/reports" or "C:\\Users\\Anna\\Documents"
     *
     * @param string $os "unix" or "windows"
     */
    public function directoryPath(string $os = 'unix'): string
    {
        $windows = match ($os) {
            'unix' => false,
            'windows' => true,
            default => throw new InvalidArgumentException(sprintf('Unknown OS "%s", use "unix" or "windows"', $os)),
        };

        $user = $this->randomizer->randomElement(static::USER_NAMES);
        $parts = [str_replace('%user%', $windows ? ucfirst($user) : $user, $this->randomizer->randomElement(static::DIRECTORIES[$os]))];

        foreach ($this->randomizer->randomElements(static::SUBDIRECTORIES, $this->randomizer->getInt(0, 2), true) as $directory) {
            $parts[] = $windows ? ucfirst($directory) : $directory;
        }

        return implode($windows ? '\\' : '/', $parts);
    }

    /**
     * Absolute file path, e.g. "/var/www/html/uploads/invoice_2024.pdf"
     *
     * @param string|null $extension Extension to use (with or without dot), random when null
     * @param string $os "unix" or "windows"
     */
    public function filePath(?string $extension = null, string $os = 'unix'): string
    {
        return $this->directoryPath($os) . ($os === 'windows' ? '\\' : '/') . $this->fileName($extension);
    }

    /**
     * CSV file contents - header and $rows records (id, name, email, city, amount, created_at), RFC 4180 quoting.
     * Override record() for other columns.
     */
    public function csv(int $rows = 10, string $separator = ','): string
    {
        $records = $this->records($rows);
        $stream = fopen('php://memory', 'w+');

        if ($stream === false) {
            throw new RuntimeException('Cannot open memory stream');
        }

        // header from the first record - an empty file still gets it (from a record generated only for that)
        fputcsv($stream, array_keys($records[0] ?? $this->record(0)), $separator, '"', '');
        foreach ($records as $record) {
            fputcsv($stream, $record, $separator, '"', '');
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    /**
     * JSON file contents - pretty printed list of $items records (see csv())
     */
    public function json(int $items = 3): string
    {
        return json_encode($this->records($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Size in bytes, default between 1 KB and 10 MB
     */
    public function fileSize(int $minBytes = 1024, int $maxBytes = 10485760): int
    {
        if ($minBytes < 0) {
            throw new InvalidArgumentException('fileSize() $minBytes cannot be negative');
        }

        if ($maxBytes < $minBytes) {
            throw new InvalidArgumentException('fileSize() $maxBytes cannot be less than $minBytes');
        }

        return $this->randomizer->getInt($minBytes, $maxBytes);
    }

    /**
     * Human readable size (1024 based), e.g. "512 B", "2.4 MB"
     */
    public function fileSizeFormatted(int $minBytes = 1024, int $maxBytes = 10485760): string
    {
        $size = (float) $this->fileSize($minBytes, $maxBytes);
        $unit = 0;

        // compare rounded value, so 1023.96 KB becomes 1 MB, not 1024 KB
        while (round($size, 1) >= 1024 && $unit < count(self::SIZE_UNITS) - 1) {
            $size /= 1024;
            ++$unit;
        }

        return round($size, 1) . ' ' . self::SIZE_UNITS[$unit];
    }

    /** @return list<array<string, scalar>> */
    protected function records(int $count): array
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Number of records cannot be negative');
        }

        $records = [];
        for ($id = 1; $id <= $count; ++$id) {
            $records[] = $this->record($id);
        }

        return $records;
    }

    /**
     * One record of csv() / json() - keys are column names
     *
     * @return array<string, scalar>
     */
    protected function record(int $id): array
    {
        $firstName = $this->person->firstName();
        $lastName = $this->person->lastName();

        return [
            'id' => $id,
            'name' => $firstName . ' ' . $lastName,
            'email' => $this->internet->email($firstName, $lastName),
            'city' => $this->address->city(),
            'amount' => round($this->randomizer->getInt(100, 99999) / 100, 2),
            'created_at' => $this->dateTime->dateTimeBetween('-2 years', 'now')->format('Y-m-d H:i:s'),
        ];
    }

    /** @return array<string, list<string>> */
    protected function mimeTypes(?string $category): array
    {
        if ($category === null) {
            return array_merge(...array_values(static::MIME_TYPES));
        }

        if (!isset(static::MIME_TYPES[$category])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown file category "%s", use one of: %s',
                $category,
                implode(', ', array_keys(static::MIME_TYPES)),
            ));
        }

        return static::MIME_TYPES[$category];
    }

    protected function categoryForExtension(string $extension): ?string
    {
        $extension = strtolower($extension);

        foreach (static::MIME_TYPES as $category => $mimeTypes) {
            foreach ($mimeTypes as $extensions) {
                if (in_array($extension, $extensions, true)) {
                    return $category;
                }
            }
        }

        return null;
    }
}
