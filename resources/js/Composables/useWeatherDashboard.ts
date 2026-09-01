import { onScopeDispose, ref } from 'vue';
import type { LocationData } from '@/Types/location';
import type { WeatherDashboardData } from '@/Types/weather';
import { isWeatherDashboard } from '@/Utils/weather';

interface DashboardResponse {
  data: WeatherDashboardData;
}

function isDashboardResponse(value: unknown): value is DashboardResponse {
  return typeof value === 'object'
    && value !== null
    && isWeatherDashboard((value as Record<string, unknown>).data);
}

export function useWeatherDashboard() {
  const dashboard = ref<WeatherDashboardData | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  let controller: AbortController | undefined;
  let requestSequence = 0;

  function clear(): void {
    requestSequence += 1;
    controller?.abort();
    controller = undefined;
    dashboard.value = null;
    loading.value = false;
    error.value = null;
  }

  async function load(location: LocationData): Promise<void> {
    const sequence = ++requestSequence;

    controller?.abort();
    controller = new AbortController();
    dashboard.value = null;
    loading.value = true;
    error.value = null;

    try {
      const parameters = new URLSearchParams({
        name: location.name,
        country: location.country,
        latitude: location.latitude.toString(),
        longitude: location.longitude.toString(),
      });

      if (location.state) {
        parameters.set('state', location.state);
      }

      const response = await fetch(`/weather/dashboard?${parameters.toString()}`, {
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });

      if (!response.ok) {
        throw new Error('Weather dashboard request failed.');
      }

      const payload: unknown = await response.json();

      if (!isDashboardResponse(payload)) {
        throw new Error('Weather dashboard returned an invalid response.');
      }

      if (sequence === requestSequence) {
        dashboard.value = payload.data;
      }
    } catch (reason: unknown) {
      if (reason instanceof DOMException && reason.name === 'AbortError') {
        return;
      }

      if (sequence === requestSequence) {
        error.value = 'Não foi possível carregar a previsão agora. Tente novamente.';
      }
    } finally {
      if (sequence === requestSequence) {
        loading.value = false;
        controller = undefined;
      }
    }
  }

  onScopeDispose(clear);

  return {
    dashboard,
    loading,
    error,
    load,
    clear,
  };
}
