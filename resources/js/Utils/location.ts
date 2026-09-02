import type { LocationData } from '@/Types/location';

export function isLocationData(value: unknown): value is LocationData {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const location = value as Record<string, unknown>;

  return (
    typeof location.name === 'string' &&
    (typeof location.state === 'string' || location.state === null) &&
    typeof location.country === 'string' &&
    typeof location.latitude === 'number' &&
    typeof location.longitude === 'number'
  );
}
