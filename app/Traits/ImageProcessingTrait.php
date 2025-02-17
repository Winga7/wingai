<?php

namespace App\Traits;

use App\Services\ImageService;

trait ImageProcessingTrait
{
  private function getImageService(): ImageService
  {
    return app(ImageService::class);
  }

  protected function processImageInput(string $image): string
  {
    if (filter_var($image, FILTER_VALIDATE_URL)) {
      return $image;
    }

    if (preg_match('/^data:image\/(\w+);base64,/', $image)) {
      return $image;
    }

    if (!file_exists($image)) {
      throw new \Exception("Le fichier image n'existe pas : " . $image);
    }

    return $this->getImageService()->optimizeImage($image);
  }
}
