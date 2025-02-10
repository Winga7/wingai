<?php

namespace App\Services;

use App\Interfaces\WeatherServiceInterface;
use Illuminate\Support\Facades\Http;

class OpenWeatherMapService implements WeatherServiceInterface
{
  private string $apiKey;
  private string $baseUrl = 'https://api.openweathermap.org/data/2.5';

  public function __construct()
  {
    $this->apiKey = config('services.openweathermap.key');
  }

  public function getCurrentWeather(string $city): array
  {
    $response = Http::get("{$this->baseUrl}/weather", [
      'q' => $city,
      'appid' => $this->apiKey,
      'units' => 'metric',
      'lang' => 'fr'
    ]);

    return $response->json();
  }

  public function getForecast(string $city): array
  {
    $response = Http::get("{$this->baseUrl}/forecast", [
      'q' => $city,
      'appid' => $this->apiKey,
      'units' => 'metric',
      'lang' => 'fr'
    ]);

    return $response->json();
  }
}
