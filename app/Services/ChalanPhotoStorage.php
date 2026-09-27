<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChalanPhotoStorage
{
    private const CLOUDINARY_DISK = 'cloudinary';

    /** @return array{disk: string, path: string} */
    public function store(UploadedFile $photo): array
    {
        $disk = (string) config('filesystems.delivery_photos_disk', 'delivery_photos');

        if ($disk === self::CLOUDINARY_DISK) {
            $asset = $this->cloudinary()->uploadApi()->upload($photo->getRealPath(), [
                'resource_type' => 'image',
                'type' => 'authenticated',
                'public_id' => 'school-feeding/chalans/'.Str::uuid(),
                'overwrite' => false,
            ]);

            $publicId = $asset['public_id'] ?? null;
            $format = $asset['format'] ?? null;
            if (! is_string($publicId) || ! is_string($format)) {
                throw new \RuntimeException('Cloudinary did not return a valid chalan reference.');
            }

            return [
                'disk' => self::CLOUDINARY_DISK,
                'path' => json_encode(['public_id' => $publicId, 'format' => $format], JSON_THROW_ON_ERROR),
            ];
        }

        $path = $photo->store('', $disk);
        if (! is_string($path)) {
            throw new \RuntimeException('The chalan photo could not be stored.');
        }

        return ['disk' => $disk, 'path' => $path];
    }

    public function delete(string $disk, string $path): void
    {
        if ($disk === self::CLOUDINARY_DISK) {
            $asset = $this->decodeCloudinaryReference($path);
            $this->cloudinary()->uploadApi()->destroy($asset['public_id'], [
                'resource_type' => 'image',
                'type' => 'authenticated',
            ]);

            return;
        }

        Storage::disk($disk)->delete($path);
    }

    public function response(string $disk, string $path): RedirectResponse|StreamedResponse
    {
        if ($disk === self::CLOUDINARY_DISK) {
            $asset = $this->decodeCloudinaryReference($path);
            $url = $this->cloudinary()->uploadApi()->privateDownloadUrl(
                $asset['public_id'],
                $asset['format'],
                [
                    'resource_type' => 'image',
                    'type' => 'authenticated',
                    'expires_at' => time() + 60,
                ],
            );

            return redirect()->away($url)
                ->header('Cache-Control', 'private, no-store')
                ->header('Referrer-Policy', 'no-referrer');
        }

        $storage = Storage::disk($disk);
        abort_unless($storage->exists($path), 404);

        return $storage->response(
            $path,
            basename($path),
            [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ],
            'inline',
        );
    }

    /** @return array{public_id: string, format: string} */
    private function decodeCloudinaryReference(string $path): array
    {
        $reference = json_decode($path, true);

        abort_unless(
            is_array($reference)
                && is_string($reference['public_id'] ?? null)
                && is_string($reference['format'] ?? null),
            404,
        );

        return ['public_id' => $reference['public_id'], 'format' => $reference['format']];
    }

    private function cloudinary(): Cloudinary
    {
        $url = config('services.cloudinary.url');
        if (! is_string($url) || $url === '') {
            throw new \RuntimeException('Configure CLOUDINARY_URL to use Cloudinary chalan storage.');
        }

        return new Cloudinary($url);
    }
}
