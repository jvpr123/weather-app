import type { CityComparisonData, ComparedCityData } from '@/Types/comparison';
import { isLocationData } from '@/Utils/location';
import { isCurrentWeatherData } from '@/Utils/weather';

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null;
}

function isComparedCity(value: unknown): value is ComparedCityData {
  if (!isRecord(value)) {
    return false;
  }

  return isLocationData(value.location)
    && isCurrentWeatherData(value.current)
    && typeof value.rainProbability === 'number'
    && Number.isFinite(value.rainProbability)
    && typeof value.outdoorScore === 'number'
    && Number.isFinite(value.outdoorScore);
}

export function isCityComparison(value: unknown): value is CityComparisonData {
  if (!isRecord(value)) {
    return false;
  }

  return isComparedCity(value.left)
    && isComparedCity(value.right)
    && (value.recommendation === 'left'
      || value.recommendation === 'right'
      || value.recommendation === 'tie');
}
