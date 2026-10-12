# File

`$generator->file()` - interface `Quidam\Definitions\Extension\FileExtensionInterface`, default implementation `Quidam\Core\File`

MIME types, extensions, file names matching their type, file sizes.

## Methods

| Method | Returns | Example |
|---|---|---|
| `mimeType(?string $category = null)` | `string` | `'video/ogg'`, `mimeType('image')` → `'image/jpeg'` |
| `extension(?string $category = null)` | `string` - without dot | `'ppt'` |
| `fileName(?string $extension = null)` | `string` - name matching type | `'assets-64.tar'`, `'presentation-57.pdf'`, `'IMG_0996.jpg'` |
| `mimeTypeForExtension(string $extension)` | `string` - case insensitive, dot optional | `'docx'` → `'application/vnd.openxmlformats-officedocument.wordprocessingml.document'` |
| `directoryPath(string $os = 'unix')` | `string` - absolute path | `'/home/anna/projects'`, `'C:\Program Files\Documents'` |
| `filePath(?string $extension = null, string $os = 'unix')` | `string` - directory and `fileName()` | `'/usr/local/share/config/photos_2010.rar'` |
| `csv(int $rows = 10, string $separator = ',')` | `string` - CSV file contents with header | see below |
| `json(int $items = 3)` | `string` - pretty printed JSON list | see below |
| `fileSize(int $minBytes = 1024, int $maxBytes = 10485760)` | `int` - bytes | `6542550` |
| `fileSizeFormatted(int $minBytes = 1024, int $maxBytes = 10485760)` | `string` - 1024 based, `B`/`KB`/`MB`/`GB`/`TB` | `'5 MB'`, `'17.8 MB'` |

Categories: `document`, `image`, `audio`, `video`, `archive`, `text`.

## Examples

```php
$file = $generator->file();

// attachments table - name, type and size consistent
$extension = $file->extension('document');
$attachment = [
    'name' => $file->fileName($extension),             // 'invoice_2024.pdf'
    'mimeType' => $file->mimeTypeForExtension($extension),
    'size' => $file->fileSize(10240, 5242880),
];

$file->fileName('.csv');                               // 'export_final.csv'
$file->fileSizeFormatted(1048576, 1073741824);         // '17.8 MB'
```

## File contents

`csv()` and `json()` give realistic contents for upload / import tests - records with `id`, `name`, `email`, `city`, `amount`, `created_at`:

```php
$generator->file()->csv(2);
// id,name,email,city,amount,created_at
// 1,"Charles Sanders",csanders79@example.org,Abigailhaven,461.11,"2025-08-15 01:56:29"
// 2,"Philip Morgan",philip075@example.org,"South Carl",972.05,"2024-11-16 22:09:44"

$generator->file()->json(1);
// [
//     {
//         "id": 1,
//         "name": "William Walsh",
//         "email": "wwalsh82@example.org",
//         ...
//     }
// ]

file_put_contents($path = sys_get_temp_dir() . '/import.csv', $generator->file()->csv(500, ';'));
```

CSV uses RFC 4180 quoting (no backslash escapes) with `\n` line endings. For other columns override the protected `record(int $id): array` in a subclass - keys become CSV header / JSON keys.

## Data

56 commonly used MIME types:

| Category | Extensions |
|---|---|
| document | pdf, doc, docx, xls, xlsx, ppt, pptx, odt, ods, odp, rtf, epub, csv |
| image | jpg, jpeg, png, gif, webp, avif, heic, svg, bmp, tif, tiff, ico |
| audio | mp3, wav, ogg, oga, flac, aac, m4a, weba |
| video | mp4, webm, mov, avi, mkv, mpeg, mpg, ogv, 3gp |
| archive | zip, gz, tar, 7z, rar, bz2, xz |
| text | txt, log, html, htm, css, js, mjs, md, ics, vcf, json, xml, yaml, yml, sql |

`fileName()` picks a base name of the extension's category (i.e. `invoice`, `report` for documents; `photo`, `avatar` for images; `backup`, `export` for archives), optionally with a suffix (`-12`, `_2024`, `_final`, `_v2`). Photos (`jpg`, `jpeg`, `heic`, `png`) sometimes get camera style name `IMG_1234`. Unknown extension gets a generic name.
