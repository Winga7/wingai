<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ChatService;
use App\Services\ImageService;

class AppServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    $this->app->singleton(ImageService::class, function ($app) {
      return new ImageService();
    });

    $this->app->singleton(ChatService::class, function ($app) {
      return new ChatService($app->make(ImageService::class));
    });
  }
}
