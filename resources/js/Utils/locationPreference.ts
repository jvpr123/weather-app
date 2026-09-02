import type { LocationData } from '@/Types/location';
import { isLocationData } from '@/Utils/location';

const STORAGE_KEY = 'weatherlens:last-location';
const EARTH_RADIUS_KILOMETERS = 6371;

export function readPreferredLocation(): LocationData | null {
  try {
    const storedLocation = localStorage.getItem(STORAGE_KEY);

    if (!storedLocation) {
      return null;
    }

    const location: unknown = JSON.parse(storedLocation);

    if (isLocationData(location)) {
      return location;
    }

    localStorage.removeItem(STORAGE_KEY);
  } catch {
    // Storage can be unavailable or contain data from an older application version.
  }

  return null;
}

export function rememberPreferredLocation(location: LocationData): void {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(location));
  } catch {
    // A location selection must still work when browser storage is unavailable.
  }
}

export function distanceBetweenLocations(first: LocationData, second: LocationData): number {
  const latitudeDelta = toRadians(second.latitude - first.latitude);
  const longitudeDelta = toRadians(second.longitude - first.longitude);
  const firstLatitude = toRadians(first.latitude);
  const secondLatitude = toRadians(second.latitude);
  const haversine =
    Math.sin(latitudeDelta / 2) ** 2 +
    Math.cos(firstLatitude) * Math.cos(secondLatitude) * Math.sin(longitudeDelta / 2) ** 2;
  const normalizedHaversine = Math.max(0, Math.min(1, haversine));

  return (
    EARTH_RADIUS_KILOMETERS *
    2 *
    Math.atan2(Math.sqrt(normalizedHaversine), Math.sqrt(1 - normalizedHaversine))
  );
}

async function geolocationPermission(): Promise<PermissionState | null> {
  if (typeof navigator === 'undefined' || !navigator.permissions) {
    return null;
  }

  try {
    return (await navigator.permissions.query({ name: 'geolocation' })).state;
  } catch {
    return null;
  }
}

export async function hasGrantedGeolocationPermission(): Promise<boolean> {
  return (await geolocationPermission()) === 'granted';
}

function toRadians(degrees: number): number {
  return degrees * (Math.PI / 180);
}
