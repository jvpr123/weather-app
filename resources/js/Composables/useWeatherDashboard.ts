import { onScopeDispose, ref } from 'vue';
import type { LocationData } from '@/Types/location';
import type { WeatherDashboardData } from '@/Types/weather';
import { isWeatherDashboard } from '@/Utils/weather';
import { apiRequestError, safeRequestErrorMessage } from '@/Utils/api';

interface DashboardResponse {
  data: WeatherDashboardData;
}

function isDashboardResponse(value: unknown): value is DashboardResponse {
  return (
    typeof value === 'object' &&
    value !== null &&
    isWeatherDashboard((value as Record<string, unknown>).data)
  );
}

export function useWeatherDashboard() {
  const dashboard = ref<WeatherDashboardData | null>(null);
  const loading = ref(false);
  const refreshing = ref(false);
  const error = ref<string | null>(null);
  const lastLocation = ref<LocationData | null>(null);

  let controller: AbortController | undefined;
  let requestSequence = 0;

  function clear(): void {
    requestSequence += 1;
    controller?.abort();
    controller = undefined;
    dashboard.value = null;
    loading.value = false;
    refreshing.value = false;
    error.value = null;
  }

  async function request(location: LocationData, forceRefresh: boolean): Promise<void> {
    const sequence = ++requestSequence;

    controller?.abort();
    controller = new AbortController();
    if (!forceRefresh) {
      dashboard.value = null;
    }
    loading.value = !forceRefresh;
    refreshing.value = forceRefresh;
    error.value = null;
    lastLocation.value = location;

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

      if (forceRefresh) {
        parameters.set('refresh', '1');
      }

      const response = await fetch(`/weather/dashboard?${parameters.toString()}`, {
        cache: forceRefresh ? 'no-store' : 'default',
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });

      if (!response.ok) {
        throw await apiRequestError(
          response,
          'Não foi possível atualizar o clima agora. Tente novamente.',
        );
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
        error.value = safeRequestErrorMessage(
          reason,
          'Não foi possível atualizar o clima agora. Tente novamente.',
        );
      }
    } finally {
      if (sequence === requestSequence) {
        loading.value = false;
        refreshing.value = false;
        controller = undefined;
      }
    }
  }

  async function load(location: LocationData): Promise<void> {
    await request(location, false);
  }

  function refresh(): void {
    if (lastLocation.value && dashboard.value) {
      void request(lastLocation.value, true);
    }
  }

  function retry(): void {
    if (lastLocation.value) {
      void load(lastLocation.value);
    }
  }

  onScopeDispose(clear);

  return {
    dashboard,
    loading,
    refreshing,
    error,
    load,
    refresh,
    retry,
    clear,
  };
}
