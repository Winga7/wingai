<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\WeatherServiceInterface;
use App\Services\OpenWeatherMapService;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->bind(WeatherServiceInterface::class, OpenWeatherMapService::class);
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    //
  }
}
