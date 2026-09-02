import type { LocationData } from '@/Types/location';

export function isLocationData(value: unknown): value is LocationData {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const location = value as Record<string, unknown>;

  return (
    typeof location.name === 'string' &&
    location.name.trim() !== '' &&
    (typeof location.state === 'string' || location.state === null) &&
    typeof location.country === 'string' &&
    location.country.length === 2 &&
    typeof location.latitude === 'number' &&
    Number.isFinite(location.latitude) &&
    location.latitude >= -90 &&
    location.latitude <= 90 &&
    typeof location.longitude === 'number' &&
    Number.isFinite(location.longitude) &&
    location.longitude >= -180 &&
    location.longitude <= 180
  );
}
