import { onScopeDispose, ref } from 'vue';
import type { Coordinates, LocationData } from '@/Types/location';
import { isLocationData } from '@/Utils/location';
import { apiRequestError, safeRequestErrorMessage } from '@/Utils/api';

interface ReverseGeocodingResponse {
  data: LocationData | null;
}

function isReverseGeocodingResponse(value: unknown): value is ReverseGeocodingResponse {
  if (typeof value !== 'object' || value === null || !('data' in value)) {
    return false;
  }

  const data = (value as Record<string, unknown>).data;

  return data === null || isLocationData(data);
}

export function useReverseGeocoding() {
  const loading = ref(false);
  const error = ref<string | null>(null);

  let controller: AbortController | undefined;
  let requestSequence = 0;

  function clear(): void {
    requestSequence += 1;
    controller?.abort();
    controller = undefined;
    loading.value = false;
    error.value = null;
  }

  async function resolve(coordinates: Coordinates): Promise<LocationData | null> {
    const sequence = ++requestSequence;

    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    error.value = null;

    try {
      const parameters = new URLSearchParams({
        latitude: coordinates.latitude.toString(),
        longitude: coordinates.longitude.toString(),
      });
      const response = await fetch(`/locations/reverse?${parameters.toString()}`, {
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });

      if (!response.ok) {
        throw await apiRequestError(response, 'Não foi possível identificar sua cidade agora.');
      }

      const payload: unknown = await response.json();

      if (!isReverseGeocodingResponse(payload)) {
        throw new Error('Reverse geocoding returned an invalid response.');
      }

      return sequence === requestSequence ? payload.data : null;
    } catch (reason: unknown) {
      if (reason instanceof DOMException && reason.name === 'AbortError') {
        return null;
      }

      if (sequence === requestSequence) {
        error.value = safeRequestErrorMessage(
          reason,
          'Não foi possível identificar sua cidade agora.',
        );
      }

      return null;
    } finally {
      if (sequence === requestSequence) {
        loading.value = false;
        controller = undefined;
      }
    }
  }

  onScopeDispose(clear);

  return {
    loading,
    error,
    resolve,
    clear,
  };
}
