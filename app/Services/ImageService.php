<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageService
{
  private const MAX_WIDTH = 800;
  private const JPEG_QUALITY = 85;
  private const SUPPORTED_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif'
  ];
  private const SUPPORTED_TYPES = [
    IMAGETYPE_JPEG => ['ext' => 'jpeg', 'mime' => 'image/jpeg'],
    IMAGETYPE_PNG => ['ext' => 'png', 'mime' => 'image/png'],
    IMAGETYPE_WEBP => ['ext' => 'webp', 'mime' => 'image/webp'],
    IMAGETYPE_GIF => ['ext' => 'gif', 'mime' => 'image/gif']
  ];

  public function store($image, $folder = 'chat-images'): string
  {
    $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
    $path = $image->storeAs($folder, $filename, 'public');
    return Storage::url($path);
  }

  public function optimize($image, $maxSize = 2048): \Intervention\Image\Image
  {
    return Image::make($image)
      ->resize($maxSize, $maxSize, function ($constraint) {
        $constraint->aspectRatio();
        $constraint->upsize();
      })
      ->encode('jpg', 80);
  }

  public function optimizeAndStore(UploadedFile $image, string $folder = 'chat-images'): array
  {
    try {
      // Vérification du type MIME
      if (!in_array($image->getMimeType(), [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif'
      ])) {
        throw new \Exception('Format d\'image non supporté');
      }

      $base64 = $this->optimizeImage($image->path());
      $extension = self::SUPPORTED_TYPES[exif_imagetype($image->path())]['ext'];
      $filename = Str::uuid() . '.' . $extension;
      $path = "$folder/$filename";

      Storage::put("public/$path", base64_decode(explode(',', $base64)[1]));

      logger()->info('Image optimisée et stockée', [
        'path' => $path,
        'hasBase64' => !empty($base64)
      ]);

      return [
        'path' => $path,
        'url' => Storage::url($path),
        'base64' => $base64
      ];
    } catch (\Exception $e) {
      logger()->error('Erreur optimisation image:', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
  }

  private function optimizeImage(string $sourcePath): string
  {
    if (!file_exists($sourcePath)) {
      throw new \Exception("Le fichier image n'existe pas : " . $sourcePath);
    }

    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
      throw new \Exception("Impossible de lire les informations de l'image");
    }

    [$width, $height, $type] = $imageInfo;

    if (!isset(self::SUPPORTED_TYPES[$type])) {
      throw new \Exception('Format d\'image non supporté');
    }

    $ratio = $width / $height;
    $newWidth = min($width, self::MAX_WIDTH);
    $newHeight = (int)($newWidth / $ratio);

    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP || $type === IMAGETYPE_GIF) {
      imagealphablending($newImage, false);
      imagesavealpha($newImage, true);
      $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
      imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    $sourceImage = match ($type) {
      IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
      IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
      IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
      IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
      default => throw new \Exception('Format non supporté'),
    };

    imagecopyresampled(
      $newImage,
      $sourceImage,
      0,
      0,
      0,
      0,
      $newWidth,
      $newHeight,
      $width,
      $height
    );

    ob_start();
    match ($type) {
      IMAGETYPE_JPEG => imagejpeg($newImage, null, self::JPEG_QUALITY),
      IMAGETYPE_PNG => imagepng($newImage, null, 9),
      IMAGETYPE_WEBP => imagewebp($newImage, null, self::JPEG_QUALITY),
      IMAGETYPE_GIF => imagegif($newImage),
    };
    $imageData = ob_get_clean();

    imagedestroy($sourceImage);
    imagedestroy($newImage);

    $mimeType = self::SUPPORTED_TYPES[$type]['mime'];
    return "data:{$mimeType};base64," . base64_encode($imageData);
  }

  public function delete(string $path): bool
  {
    return Storage::delete("public/$path");
  }

  public function getBase64ForPath(?string $path): ?string
  {
    if (!$path) {
      logger()->warning('Tentative de conversion en base64 d\'un chemin null');
      return null;
    }

    try {
      $fullPath = storage_path('app/public/' . $path);
      if (!file_exists($fullPath)) {
        logger()->warning('Image introuvable:', ['path' => $fullPath]);
        return null;
      }

      $optimizedData = $this->optimizeImage($fullPath);
      if (!$optimizedData) {
        return null;
      }

      $base64 = base64_encode($optimizedData);
      return "data:image/jpeg;base64,{$base64}";
    } catch (\Exception $e) {
      logger()->error('Erreur lors de la conversion en base64:', [
        'path' => $path,
        'error' => $e->getMessage()
      ]);
      return null;
    }
  }
}
