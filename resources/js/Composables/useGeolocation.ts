import { computed, ref } from 'vue';
import type { Coordinates } from '@/Types/location';

export type GeolocationStatus = 'idle'
  | 'requesting'
  | 'available'
  | 'denied'
  | 'unavailable'
  | 'timeout';

const DEFAULT_OPTIONS: PositionOptions = {
  enableHighAccuracy: false,
  timeout: 10_000,
  maximumAge: 300_000,
};

function validCoordinates(coordinates: GeolocationCoordinates): boolean {
  return Number.isFinite(coordinates.latitude)
    && coordinates.latitude >= -90
    && coordinates.latitude <= 90
    && Number.isFinite(coordinates.longitude)
    && coordinates.longitude >= -180
    && coordinates.longitude <= 180;
}

export function useGeolocation() {
  const status = ref<GeolocationStatus>('idle');
  const coordinates = ref<Coordinates | null>(null);
  const supported = computed(() => typeof navigator !== 'undefined' && 'geolocation' in navigator);

  let requestSequence = 0;

  function mapError(error: GeolocationPositionError): GeolocationStatus {
    if (error.code === error.PERMISSION_DENIED) {
      return 'denied';
    }

    if (error.code === error.TIMEOUT) {
      return 'timeout';
    }

    return 'unavailable';
  }

  function request(options: PositionOptions = DEFAULT_OPTIONS): Promise<Coordinates | null> {
    const sequence = ++requestSequence;
    coordinates.value = null;

    if (!supported.value) {
      status.value = 'unavailable';
      return Promise.resolve(null);
    }

    status.value = 'requesting';

    return new Promise((resolve) => {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          if (sequence !== requestSequence) {
            resolve(null);
            return;
          }

          if (!validCoordinates(position.coords)) {
            status.value = 'unavailable';
            resolve(null);
            return;
          }

          coordinates.value = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
          };
          status.value = 'available';
          resolve(coordinates.value);
        },
        (error) => {
          if (sequence !== requestSequence) {
            resolve(null);
            return;
          }

          status.value = mapError(error);
          resolve(null);
        },
        options,
      );
    });
  }

  function reset(): void {
    requestSequence += 1;
    coordinates.value = null;
    status.value = 'idle';
  }

  return {
    status,
    coordinates,
    supported,
    request,
    reset,
  };
}
