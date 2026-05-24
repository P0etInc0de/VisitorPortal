<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Monitor;

use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class MonitorImageUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_jpeg_under_20_mb_is_accepted_and_exif_metadata_is_removed(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('monitor.jpg', $this->jpegWithExifMetadata());

        $response = $this->uploadMonitorImage($monitor, $upload);

        $response->assertRedirect(route('monitors.edit', $monitor));
        $response->assertSessionHasNoErrors();

        $monitor->refresh();

        $this->assertNotNull($monitor->image_path);
        $this->assertStringEndsWith('.jpg', $monitor->image_path);
        Storage::disk('public')->assertExists($monitor->image_path);

        $storedImage = Storage::disk('public')->get($monitor->image_path);

        $this->assertStringStartsWith("\xFF\xD8", $storedImage);
        $this->assertStringNotContainsString('Exif', $storedImage);
        $this->assertStringNotContainsString('GPSLatitude', $storedImage);
    }

    public function test_valid_png_under_20_mb_is_accepted_and_metadata_is_removed(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('monitor.png', $this->pngImage(1, 1, includeMetadata: true));

        $response = $this->uploadMonitorImage($monitor, $upload);

        $response->assertRedirect(route('monitors.edit', $monitor));
        $response->assertSessionHasNoErrors();

        $monitor->refresh();

        $this->assertNotNull($monitor->image_path);
        $this->assertStringEndsWith('.png', $monitor->image_path);
        Storage::disk('public')->assertExists($monitor->image_path);

        $storedImage = Storage::disk('public')->get($monitor->image_path);

        $this->assertStringStartsWith("\x89PNG\r\n\x1A\n", $storedImage);
        $this->assertStringNotContainsString('VisitorPortal metadata', $storedImage);
        $this->assertStringNotContainsString('tEXt', $storedImage);
    }

    public function test_valid_webp_under_20_mb_is_accepted_and_metadata_is_removed(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('monitor.webp', $this->webpImage(includeMetadata: true));

        $response = $this->uploadMonitorImage($monitor, $upload);

        $response->assertRedirect(route('monitors.edit', $monitor));
        $response->assertSessionHasNoErrors();

        $monitor->refresh();

        $this->assertNotNull($monitor->image_path);
        $this->assertStringEndsWith('.webp', $monitor->image_path);
        Storage::disk('public')->assertExists($monitor->image_path);

        $storedImage = Storage::disk('public')->get($monitor->image_path);

        $this->assertStringStartsWith('RIFF', $storedImage);
        $this->assertStringContainsString('WEBP', $storedImage);
        $this->assertStringNotContainsString('EXIF', $storedImage);
        $this->assertStringNotContainsString('WebP metadata GPS=48.137154', $storedImage);
    }

    public function test_invalid_webp_is_rejected(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('monitor.webp', $this->invalidWebpImage());

        $response = $this->uploadMonitorImage($monitor, $upload);

        $response->assertRedirect(route('monitors.edit', $monitor));
        $response->assertSessionHasErrors('image');

        $monitor->refresh();

        $this->assertNull($monitor->image_path);
        $this->assertEmpty(Storage::disk('public')->allFiles('images'));
    }

    public function test_fallback_page_image_upload_uses_normalized_raster_storage(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('fallback.png', $this->pngImage(1, 1, includeMetadata: true));

        $response = $this
            ->actingAs($this->monitorUser())
            ->put(route('monitors.fallback.update', $monitor), [
                'heading' => 'Welcome to VisitorPortal',
                'subheading' => 'Bitte melden Sie sich am Empfang.',
                'show_logo' => '1',
                'show_date' => '1',
                'background_source' => 'upload',
                'image' => $upload,
            ]);

        $response->assertRedirect(route('monitors.edit', $monitor).'#monitor-pages');
        $response->assertSessionHasNoErrors();

        $monitor->refresh();

        $this->assertNotNull($monitor->fallback_image_path);
        $this->assertStringEndsWith('.png', $monitor->fallback_image_path);
        Storage::disk('public')->assertExists($monitor->fallback_image_path);
        $this->assertStringNotContainsString('VisitorPortal metadata', Storage::disk('public')->get($monitor->fallback_image_path));
    }

    public function test_monitor_slide_image_upload_uses_normalized_raster_storage(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('slide.webp', $this->webpImage(includeMetadata: true));

        $response = $this
            ->actingAs($this->monitorUser())
            ->post(route('monitors.slides.store', $monitor), [
                'heading' => 'Herzlich willkommen!',
                'subheading' => 'Schön, dass Sie da sind!',
                'slide_number' => 1,
                'is_active' => '1',
                'show_logo' => '1',
                'show_date' => '1',
                'background_source' => 'upload',
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FIRST_INITIAL_LAST_NAME,
                'visitors' => json_encode([]),
                'image' => $upload,
            ]);

        $response->assertRedirect(route('monitors.edit', $monitor).'#monitor-pages');
        $response->assertSessionHasNoErrors();

        $slide = $monitor->monitorSlides()->sole();

        $this->assertSame('upload', $slide->background_source);
        $this->assertNotNull($slide->image_path);
        $this->assertStringEndsWith('.webp', $slide->image_path);
        Storage::disk('public')->assertExists($slide->image_path);
        $this->assertStringNotContainsString('WebP metadata GPS=48.137154', Storage::disk('public')->get($slide->image_path));
    }

    public function test_invalid_mime_type_is_rejected(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('monitor.txt', 'not an image');

        $response = $this->uploadMonitorImage($monitor, $upload);

        $response->assertRedirect(route('monitors.edit', $monitor));
        $response->assertSessionHasErrors('image');

        $monitor->refresh();

        $this->assertNull($monitor->image_path);
        $this->assertEmpty(Storage::disk('public')->allFiles('images'));
    }

    public function test_file_with_image_extension_but_invalid_content_is_rejected(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('monitor.jpg', 'not really a jpeg');

        $response = $this->uploadMonitorImage($monitor, $upload);

        $response->assertRedirect(route('monitors.edit', $monitor));
        $response->assertSessionHasErrors('image');

        $monitor->refresh();

        $this->assertNull($monitor->image_path);
        $this->assertEmpty(Storage::disk('public')->allFiles('images'));
    }

    public function test_image_over_20_mb_is_rejected(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()
            ->createWithContent('too-large.jpg', $this->jpegWithExifMetadata())
            ->size(20481);

        $response = $this->uploadMonitorImage($monitor, $upload);

        $response->assertRedirect(route('monitors.edit', $monitor));
        $response->assertSessionHasErrors('image');

        $monitor->refresh();

        $this->assertNull($monitor->image_path);
        $this->assertEmpty(Storage::disk('public')->allFiles('images'));
    }

    public function test_image_with_too_large_width_or_height_is_rejected(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('too-wide.png', $this->pngImage(20001, 1));

        $response = $this->uploadMonitorImage($monitor, $upload);

        $response->assertRedirect(route('monitors.edit', $monitor));
        $response->assertSessionHasErrors('image');

        $monitor->refresh();

        $this->assertNull($monitor->image_path);
        $this->assertEmpty(Storage::disk('public')->allFiles('images'));
    }

    public function test_image_with_too_many_pixels_is_rejected(): void
    {
        Storage::fake('public');

        $monitor = $this->monitor();
        $upload = UploadedFile::fake()->createWithContent('too-many-pixels.png', $this->pngImage(16000, 10000));

        $response = $this->uploadMonitorImage($monitor, $upload);

        $response->assertRedirect(route('monitors.edit', $monitor));
        $response->assertSessionHasErrors('image');

        $monitor->refresh();

        $this->assertNull($monitor->image_path);
        $this->assertEmpty(Storage::disk('public')->allFiles('images'));
    }

    private function monitor(): Monitor
    {
        return Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);
    }

    private function uploadMonitorImage(Monitor $monitor, UploadedFile $upload)
    {
        return $this
            ->actingAs($this->monitorUser())
            ->from(route('monitors.edit', $monitor))
            ->put(route('monitors.update', $monitor), [
                'transition_time_milliseconds' => 5000,
                'auto_generation_window_minutes' => 30,
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FIRST_INITIAL_LAST_NAME,
                'background_source' => 'upload',
                'background_overlay_enabled' => '0',
                'content_card_style' => Monitor::DEFAULT_CONTENT_CARD_STYLE,
                'fallback_heading' => 'Welcome to VisitorPortal',
                'fallback_subheading' => 'Bitte melden Sie sich am Empfang.',
                'fallback_show_logo' => '1',
                'fallback_show_date' => '1',
                'auto_generation' => '0',
                'image' => $upload,
            ]);
    }

    private function monitorUser()
    {
        return (new PermissionHelper)->getIndividualUser([
            'View:Monitor',
            'Edit:Monitor',
            'Create:MonitorSlide',
            'Update:MonitorSlide',
            'Delete:MonitorSlide',
        ], 'monitor-image-upload-user');
    }

    private function jpegWithExifMetadata(): string
    {
        $jpeg = file_get_contents(public_path('images/monitor/monitor-default-1.jpg'));
        $this->assertIsString($jpeg);
        $this->assertStringStartsWith("\xFF\xD8", $jpeg);

        $exif = "Exif\0\0GPSLatitude=48.137154;Camera=Test Camera";
        $exifSegment = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

        return substr($jpeg, 0, 2).$exifSegment.substr($jpeg, 2);
    }

    private function pngImage(int $width, int $height, bool $includeMetadata = false): string
    {
        $chunks = [
            $this->pngChunk('IHDR', pack('NNC5', $width, $height, 8, 6, 0, 0, 0)),
        ];

        if ($includeMetadata) {
            $chunks[] = $this->pngChunk('tEXt', "Comment\0VisitorPortal metadata GPS=48.137154");
        }

        $pixelData = $width === 1 && $height === 1
            ? "\x00\x00\x00\x00\x00"
            : "\x00";

        $chunks[] = $this->pngChunk('IDAT', gzcompress($pixelData));
        $chunks[] = $this->pngChunk('IEND', '');

        return "\x89PNG\r\n\x1A\n".implode('', $chunks);
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', hexdec(hash('crc32b', $type.$data)));
    }

    private function webpImage(bool $includeMetadata = false): string
    {
        $webp = base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA', true);
        $this->assertIsString($webp);

        if (! $includeMetadata) {
            return $webp;
        }

        return $this->webpContainer([
            substr($webp, 12),
            $this->webpChunk('EXIF', 'WebP metadata GPS=48.137154'),
        ]);
    }

    private function invalidWebpImage(): string
    {
        return $this->webpContainer([
            $this->webpChunk('VP8 ', 'invalid-webp-payload'),
        ]);
    }

    /**
     * @param  array<int, string>  $chunks
     */
    private function webpContainer(array $chunks): string
    {
        $payload = 'WEBP'.implode('', $chunks);

        return 'RIFF'.pack('V', strlen($payload)).$payload;
    }

    private function webpChunk(string $type, string $data): string
    {
        return $type.pack('V', strlen($data)).$data.(strlen($data) % 2 ? "\0" : '');
    }
}
