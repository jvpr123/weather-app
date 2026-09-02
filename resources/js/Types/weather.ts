import type { LocationData } from '@/Types/location';

export type WeatherTheme =
  'clear-day' | 'clear-night' | 'cloudy-day' | 'cloudy-night' | 'rain-day' | 'rain-night';

export interface CurrentWeatherData {
  temperature: number;
  feelsLike: number;
  minTemperature: number;
  maxTemperature: number;
  humidity: number;
  pressure: number;
  windSpeed: number;
  weatherCode: number;
  condition: string;
  description: string;
  icon: string;
  sunrise: number;
  sunset: number;
  timestamp: number;
}

export interface ForecastPeriodData {
  datetime: number;
  temperature: number;
  minTemperature: number;
  maxTemperature: number;
  condition: string;
  weatherCode: number;
  isDaytime: boolean;
  probabilityOfPrecipitation: number;
  windSpeed: number;
}

export interface DailyForecastData {
  date: string;
  minTemperature: number;
  maxTemperature: number;
  dominantCondition: string;
  maxRainProbability: number;
}

export interface WeatherDashboardData {
  location: LocationData;
  current: CurrentWeatherData;
  hourly: ForecastPeriodData[];
  daily: DailyForecastData[];
  timezoneOffset: number;
  theme: WeatherTheme;
}
