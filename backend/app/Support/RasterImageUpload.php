<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use App\Rules\SafeRasterImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class RasterImageUpload
{
    /**
     * @return array<int, mixed>
     */
    public static function rules(bool $nullable = true): array
    {
        return [
            $nullable ? 'nullable' : 'required',
            'file',
            'mimetypes:'.implode(',', self::allowedMimeTypes()),
            'max:'.self::maxSizeKilobytes(),
            new SafeRasterImage,
        ];
    }

    /**
     * @return array{mime: string, width: int, height: int, pixels: int}
     */
    public static function inspect(UploadedFile $file): array
    {
        $path = self::uploadedFilePath($file);

        if ($file->getSize() > self::maxSizeKilobytes() * 1024) {
            throw new InvalidArgumentException(__('Die Bilddatei darf maximal :size MB groß sein.', [
                'size' => (int) floor(self::maxSizeKilobytes() / 1024),
            ]));
        }

        $detectedMime = self::normalizeMimeType(self::detectMimeType($path));

        if (! in_array($detectedMime, self::allowedMimeTypes(), true)) {
            throw new InvalidArgumentException(__('Nur JPEG-, PNG- oder WebP-Bilder sind erlaubt.'));
        }

        $imageInfo = @getimagesize($path);

        if (! is_array($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1])) {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }

        $imageMime = self::normalizeMimeType($imageInfo['mime'] ?? null);

        if (! in_array($imageMime, self::allowedMimeTypes(), true) || $imageMime !== $detectedMime) {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $pixels = $width * $height;

        if ($width > self::maxWidth()) {
            throw new InvalidArgumentException(__('Das Bild ist zu breit. Maximal erlaubt sind :max Pixel.', [
                'max' => self::maxWidth(),
            ]));
        }

        if ($height > self::maxHeight()) {
            throw new InvalidArgumentException(__('Das Bild ist zu hoch. Maximal erlaubt sind :max Pixel.', [
                'max' => self::maxHeight(),
            ]));
        }

        if ($pixels > self::maxPixels()) {
            throw new InvalidArgumentException(__('Das Bild hat zu viele Pixel. Maximal erlaubt sind :max Megapixel.', [
                'max' => (int) floor(self::maxPixels() / 1000000),
            ]));
        }

        self::normalizeContent($path, $imageMime);

        return [
            'mime' => $imageMime,
            'width' => $width,
            'height' => $height,
            'pixels' => $pixels,
        ];
    }

    public static function store(UploadedFile $file, string $directory = 'images', string $disk = 'public', string $attribute = 'image'): string
    {
        try {
            $inspection = self::inspect($file);
            $content = self::normalizeContent(self::uploadedFilePath($file), $inspection['mime']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                $attribute => $exception->getMessage(),
            ]);
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.'.self::extensionForMimeType($inspection['mime']);

        if (! Storage::disk($disk)->put($path, $content)) {
            throw new RuntimeException('Normalized image upload could not be stored.');
        }

        return $path;
    }

    public static function maxSizeKilobytes(): int
    {
        return max(1, (int) config('upload.images.max_size_kb', 20480));
    }

    /**
     * @return array<int, string>
     */
    private static function allowedMimeTypes(): array
    {
        return array_values(array_filter(
            (array) config('upload.images.allowed_mime_types', ['image/jpeg', 'image/png', 'image/webp']),
            static fn (mixed $mimeType): bool => is_string($mimeType) && $mimeType !== '',
        ));
    }

    private static function maxWidth(): int
    {
        return max(1, (int) config('upload.images.max_width', 20000));
    }

    private static function maxHeight(): int
    {
        return max(1, (int) config('upload.images.max_height', 20000));
    }

    private static function maxPixels(): int
    {
        return max(1, (int) config('upload.images.max_pixels', 150000000));
    }

    private static function uploadedFilePath(UploadedFile $file): string
    {
        $path = $file->getRealPath() ?: $file->getPathname();

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }

        return $path;
    }

    private static function detectMimeType(string $path): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        return is_string($mimeType) ? $mimeType : null;
    }

    private static function normalizeMimeType(?string $mimeType): ?string
    {
        return is_string($mimeType) ? strtolower(trim($mimeType)) : null;
    }

    private static function normalizeContent(string $path, string $mimeType): string
    {
        $content = file_get_contents($path);

        if (! is_string($content)) {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }

        return match ($mimeType) {
            'image/jpeg' => self::stripJpegMetadata($content),
            'image/png' => self::stripPngMetadata($content),
            'image/webp' => self::stripWebpMetadata($content),
            default => throw new InvalidArgumentException(__('Nur JPEG-, PNG- oder WebP-Bilder sind erlaubt.')),
        };
    }

    private static function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new InvalidArgumentException(__('Nur JPEG-, PNG- oder WebP-Bilder sind erlaubt.')),
        };
    }

    private static function stripJpegMetadata(string $content): string
    {
        $length = strlen($content);

        if ($length < 4 || substr($content, 0, 2) !== "\xFF\xD8") {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }

        $position = 2;
        $output = "\xFF\xD8";
        $foundStartOfFrame = false;

        while ($position < $length) {
            if (ord($content[$position]) !== 0xFF) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            $markerStart = $position;

            while ($position < $length && ord($content[$position]) === 0xFF) {
                $position++;
            }

            if ($position >= $length) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            $marker = ord($content[$position]);
            $position++;

            if ($marker === 0x00) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            if ($marker === 0xD9) {
                if (! $foundStartOfFrame) {
                    throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
                }

                return $output."\xFF\xD9";
            }

            if (self::isStandaloneJpegMarker($marker)) {
                $output .= substr($content, $markerStart, $position - $markerStart);

                continue;
            }

            if ($position + 2 > $length) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            $segmentLength = self::readUInt16BigEndian($content, $position);

            if ($segmentLength < 2) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            $segmentEnd = $position + $segmentLength;

            if ($segmentEnd > $length) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            $segment = substr($content, $markerStart, $segmentEnd - $markerStart);

            if (self::isJpegStartOfFrameMarker($marker)) {
                $foundStartOfFrame = true;
            }

            if ($marker === 0xDA) {
                if (! $foundStartOfFrame) {
                    throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
                }

                $scanData = substr($content, $segmentEnd);
                $endOfImageOffset = strpos($scanData, "\xFF\xD9");

                if ($endOfImageOffset === false) {
                    throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
                }

                return $output.$segment.substr($scanData, 0, $endOfImageOffset + 2);
            }

            if (! self::isJpegMetadataMarker($marker)) {
                $output .= $segment;
            }

            $position = $segmentEnd;
        }

        throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
    }

    private static function stripPngMetadata(string $content): string
    {
        $signature = "\x89PNG\r\n\x1A\n";
        $length = strlen($content);

        if ($length < 33 || substr($content, 0, 8) !== $signature) {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }

        $position = 8;
        $output = $signature;
        $seenHeader = false;
        $seenImageData = false;
        $metadataChunks = ['tEXt', 'zTXt', 'iTXt', 'eXIf', 'tIME', 'iCCP'];

        while ($position < $length) {
            if ($position + 12 > $length) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            $chunkLength = self::readUInt32BigEndian($content, $position);
            $chunkType = substr($content, $position + 4, 4);
            $chunkDataStart = $position + 8;
            $chunkEnd = $chunkDataStart + $chunkLength + 4;

            if ($chunkEnd > $length || ! preg_match('/^[A-Za-z]{4}$/', $chunkType)) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            $chunkData = substr($content, $chunkDataStart, $chunkLength);
            $chunkCrc = substr($content, $chunkDataStart + $chunkLength, 4);

            if (! hash_equals(strtolower(bin2hex($chunkCrc)), hash('crc32b', $chunkType.$chunkData))) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            if ($chunkType === 'IHDR') {
                if ($seenHeader || $position !== 8 || $chunkLength !== 13) {
                    throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
                }

                $seenHeader = true;
            } elseif (! $seenHeader) {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            if ($chunkType === 'IDAT') {
                $seenImageData = true;
            }

            if (! in_array($chunkType, $metadataChunks, true)) {
                $output .= substr($content, $position, $chunkEnd - $position);
            }

            $position = $chunkEnd;

            if ($chunkType === 'IEND') {
                if ($chunkLength !== 0 || ! $seenImageData || $position !== $length) {
                    throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
                }

                return $output;
            }
        }

        throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
    }

    private static function stripWebpMetadata(string $content): string
    {
        $length = strlen($content);

        if ($length < 20 || substr($content, 0, 4) !== 'RIFF' || substr($content, 8, 4) !== 'WEBP') {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }

        $riffSize = self::readUInt32LittleEndian($content, 4);

        if ($riffSize + 8 > $length) {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }

        $position = 12;
        $output = 'WEBP';
        $metadataChunks = ['EXIF', 'XMP ', 'ICCP'];
        $seenImageChunk = false;

        while ($position + 8 <= $riffSize + 8) {
            $chunkType = substr($content, $position, 4);
            $chunkLength = self::readUInt32LittleEndian($content, $position + 4);
            $chunkDataStart = $position + 8;
            $chunkDataEnd = $chunkDataStart + $chunkLength;
            $nextPosition = $chunkDataEnd + ($chunkLength % 2);

            if ($chunkDataEnd > $length || $nextPosition > $length || $chunkType === '') {
                throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
            }

            $chunkData = substr($content, $chunkDataStart, $chunkLength);

            if (in_array($chunkType, ['VP8 ', 'VP8L', 'ANMF'], true)) {
                self::ensureValidWebpImageChunk($chunkType, $chunkData);
                $seenImageChunk = true;
            }

            if (! in_array($chunkType, $metadataChunks, true)) {
                if ($chunkType === 'VP8X' && $chunkLength >= 10) {
                    $chunkData[0] = chr(ord($chunkData[0]) & ~0x2C);
                }

                $output .= $chunkType.pack('V', strlen($chunkData)).$chunkData.(strlen($chunkData) % 2 ? "\0" : '');
            }

            $position = $nextPosition;
        }

        if (! $seenImageChunk || $position !== $riffSize + 8) {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }

        return 'RIFF'.pack('V', strlen($output)).$output;
    }

    private static function readUInt16BigEndian(string $content, int $offset): int
    {
        return unpack('n', substr($content, $offset, 2))[1];
    }

    private static function ensureValidWebpImageChunk(string $chunkType, string $chunkData): void
    {
        $isValid = match ($chunkType) {
            'VP8 ' => strlen($chunkData) >= 10 && substr($chunkData, 3, 3) === "\x9D\x01\x2A",
            'VP8L' => strlen($chunkData) >= 5 && $chunkData[0] === "\x2F",
            'ANMF' => strlen($chunkData) >= 16,
            default => false,
        };

        if (! $isValid) {
            throw new InvalidArgumentException(__('Die hochgeladene Datei ist kein gültiges Bild.'));
        }
    }

    private static function readUInt32BigEndian(string $content, int $offset): int
    {
        return unpack('N', substr($content, $offset, 4))[1];
    }

    private static function readUInt32LittleEndian(string $content, int $offset): int
    {
        return unpack('V', substr($content, $offset, 4))[1];
    }

    private static function isStandaloneJpegMarker(int $marker): bool
    {
        return $marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7);
    }

    private static function isJpegMetadataMarker(int $marker): bool
    {
        return ($marker >= 0xE0 && $marker <= 0xEF) || $marker === 0xFE;
    }

    private static function isJpegStartOfFrameMarker(int $marker): bool
    {
        return in_array($marker, [
            0xC0, 0xC1, 0xC2, 0xC3,
            0xC5, 0xC6, 0xC7,
            0xC9, 0xCA, 0xCB,
            0xCD, 0xCE, 0xCF,
        ], true);
    }
}
