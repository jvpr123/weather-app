import type {
  CurrentWeatherData,
  DailyForecastData,
  ForecastPeriodData,
  WeatherDashboardData,
  WeatherTheme,
} from '@/Types/weather';
import { isLocationData } from '@/Utils/location';

const WEATHER_THEMES: WeatherTheme[] = [
  'clear-day',
  'clear-night',
  'cloudy-day',
  'cloudy-night',
  'rain-day',
  'rain-night',
];

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null;
}

function hasNumbers(value: Record<string, unknown>, keys: string[]): boolean {
  return keys.every((key) => typeof value[key] === 'number' && Number.isFinite(value[key]));
}

function isCurrentWeather(value: unknown): value is CurrentWeatherData {
  if (!isRecord(value)) {
    return false;
  }

  return hasNumbers(value, [
    'temperature',
    'feelsLike',
    'minTemperature',
    'maxTemperature',
    'humidity',
    'pressure',
    'windSpeed',
    'weatherCode',
    'sunrise',
    'sunset',
    'timestamp',
  ])
    && typeof value.condition === 'string'
    && typeof value.description === 'string'
    && typeof value.icon === 'string';
}

function isForecastPeriod(value: unknown): value is ForecastPeriodData {
  return isRecord(value)
    && hasNumbers(value, [
      'datetime',
      'temperature',
      'minTemperature',
      'maxTemperature',
      'weatherCode',
      'probabilityOfPrecipitation',
      'windSpeed',
    ])
    && typeof value.condition === 'string'
    && typeof value.isDaytime === 'boolean';
}

function isDailyForecast(value: unknown): value is DailyForecastData {
  return isRecord(value)
    && typeof value.date === 'string'
    && typeof value.dominantCondition === 'string'
    && hasNumbers(value, [
      'minTemperature',
      'maxTemperature',
      'maxRainProbability',
    ]);
}

export function isWeatherDashboard(value: unknown): value is WeatherDashboardData {
  if (!isRecord(value)) {
    return false;
  }

  return isLocationData(value.location)
    && isCurrentWeather(value.current)
    && Array.isArray(value.hourly)
    && value.hourly.every(isForecastPeriod)
    && Array.isArray(value.daily)
    && value.daily.every(isDailyForecast)
    && typeof value.timezoneOffset === 'number'
    && Number.isFinite(value.timezoneOffset)
    && typeof value.theme === 'string'
    && WEATHER_THEMES.includes(value.theme as WeatherTheme);
}

export function weatherSymbol(condition: string, isDaytime = true): string {
  switch (condition) {
    case 'Clear':
      return isDaytime ? '☀️' : '🌙';
    case 'Rain':
    case 'Drizzle':
      return '🌧️';
    case 'Thunderstorm':
      return '⛈️';
    case 'Snow':
      return '❄️';
    default:
      return '☁️';
  }
}
