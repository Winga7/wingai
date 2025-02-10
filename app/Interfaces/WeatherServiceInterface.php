<?php

namespace App\Interfaces;

interface WeatherServiceInterface
{
  public function getCurrentWeather(string $city): array;
  public function getForecast(string $city): array;
}
