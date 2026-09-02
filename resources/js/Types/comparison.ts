import type { LocationData } from '@/Types/location';
import type { CurrentWeatherData } from '@/Types/weather';

export interface ComparedCityData {
  location: LocationData;
  current: CurrentWeatherData;
  rainProbability: number;
  outdoorScore: number;
}

export type ComparisonRecommendation = 'left' | 'right' | 'tie';

export interface CityComparisonData {
  left: ComparedCityData;
  right: ComparedCityData;
  recommendation: ComparisonRecommendation;
}
