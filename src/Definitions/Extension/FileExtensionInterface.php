<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface FileExtensionInterface extends ExtensionInterface
{
    /**
     * e.g. "application/pdf"
     *
     * @param string|null $category One of: document, image, audio, video, archive, text - or any when null
     */
    public function mimeType(?string $category = null): string;

    /**
     * File extension without dot, e.g. "pdf"
     *
     * @param string|null $category One of: document, image, audio, video, archive, text - or any when null
     */
    public function extension(?string $category = null): string;

    /**
     * File name matching its type, e.g. "invoice_2024.pdf", "IMG_4821.jpg", "backup-3.zip"
     *
     * @param string|null $extension Extension to use (with or without dot), random when null
     */
    public function fileName(?string $extension = null): string;

    /**
     * MIME type of given extension (with or without dot, case insensitive), e.g. "jpg" gives "image/jpeg"
     */
    public function mimeTypeForExtension(string $extension): string;

    /**
     * Absolute directory path, e.g. "/home/anna/projects/reports" or "C:\\Users\\Anna\\Documents"
     *
     * @param string $os "unix" or "windows"
     */
    public function directoryPath(string $os = 'unix'): string;

    /**
     * Absolute file path, e.g. "/var/www/html/uploads/invoice_2024.pdf"
     *
     * @param string|null $extension Extension to use (with or without dot), random when null
     * @param string $os "unix" or "windows"
     */
    public function filePath(?string $extension = null, string $os = 'unix'): string;

    /**
     * CSV file contents - header and $rows records (id, name, email, city, amount, created_at), RFC 4180 quoting.
     * Override record() for other columns.
     */
    public function csv(int $rows = 10, string $separator = ','): string;

    /**
     * JSON file contents - pretty printed list of $items records (see csv())
     */
    public function json(int $items = 3): string;

    /**
     * Size in bytes, default between 1 KB and 10 MB
     */
    public function fileSize(int $minBytes = 1024, int $maxBytes = 10485760): int;

    /**
     * Human readable size (1024 based), e.g. "512 B", "2.4 MB"
     */
    public function fileSizeFormatted(int $minBytes = 1024, int $maxBytes = 10485760): string;
}
