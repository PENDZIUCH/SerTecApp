<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * Trait HasFileUploads
 * 
 * Provides file upload, validation, and management capabilities.
 * Supports image thumbnails, MIME validation, and secure storage.
 * 
 * @package App\Traits
 */
trait HasFileUploads
{
    /**
     * Upload a file and return file information.
     * 
     * @param UploadedFile $file
     * @param string $directory
     * @param array $options
     * @return array
     */
    public function uploadFile(UploadedFile $file, string $directory = 'uploads', array $options = []): array
    {
        // Validate file
        $this->validateUploadedFile($file);

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs($directory, $filename, 'public');

        $fileData = [
            'file_name' => $filename,
            'original_file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];

        // Generate thumbnail for images
        if ($this->isImage($file) && ($options['generate_thumbnail'] ?? true)) {
            $fileData['thumbnail_path'] = $this->generateThumbnail($file, $directory);
        }

        return $fileData;
    }

    /**
     * Delete a file from storage.
     * 
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Validate uploaded file using real MIME detection.
     * 
     * @param UploadedFile $file
     * @return void
     * @throws \Exception
     */
    protected function validateUploadedFile(UploadedFile $file): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            throw new \Exception("Tipo de archivo no permitido: {$mimeType}");
        }

        // Check file size (10MB max)
        if ($file->getSize() > 10485760) {
            throw new \Exception("El archivo excede el tamaño máximo permitido de 10MB");
        }
    }

    /**
     * Check if file is an image.
     * 
     * @param UploadedFile $file
     * @return bool
     */
    protected function isImage(UploadedFile $file): bool
    {
        return Str::startsWith($file->getMimeType(), 'image/');
    }

    /**
     * Generate thumbnail for an image.
     * 
     * @param UploadedFile $file
     * @param string $directory
     * @return string|null
     */
    protected function generateThumbnail(UploadedFile $file, string $directory): ?string
    {
        try {
            $filename = 'thumb_' . Str::uuid() . '.jpg';
            $path = $directory . '/' . $filename;
            $fullPath = storage_path('app/public/' . $path);

            // Create thumbnail directory if not exists
            $thumbnailDir = dirname($fullPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Generate 300x300 thumbnail using GD
            $sourceImage = imagecreatefromstring(file_get_contents($file->getRealPath()));
            if ($sourceImage === false) {
                return null;
            }

            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);
            
            $thumbSize = 300;
            $thumbImage = imagecreatetruecolor($thumbSize, $thumbSize);
            
            imagecopyresampled(
                $thumbImage, $sourceImage,
                0, 0, 0, 0,
                $thumbSize, $thumbSize,
                $sourceWidth, $sourceHeight
            );

            imagejpeg($thumbImage, $fullPath, 80);
            imagedestroy($sourceImage);
            imagedestroy($thumbImage);

            return $path;
        } catch (\Exception $e) {
            return null;
        }
    }
}
