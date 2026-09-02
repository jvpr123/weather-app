import { onScopeDispose, ref } from 'vue';
import type { CityComparisonData } from '@/Types/comparison';
import type { LocationData } from '@/Types/location';
import { isCityComparison } from '@/Utils/comparison';
import { apiRequestError, safeRequestErrorMessage } from '@/Utils/api';

interface ComparisonResponse {
  data: CityComparisonData;
}

function isComparisonResponse(value: unknown): value is ComparisonResponse {
  return (
    typeof value === 'object' &&
    value !== null &&
    isCityComparison((value as Record<string, unknown>).data)
  );
}

function appendLocation(
  parameters: URLSearchParams,
  side: 'left' | 'right',
  location: LocationData,
): void {
  parameters.set(`${side}[name]`, location.name);
  parameters.set(`${side}[country]`, location.country);
  parameters.set(`${side}[latitude]`, location.latitude.toString());
  parameters.set(`${side}[longitude]`, location.longitude.toString());

  if (location.state) {
    parameters.set(`${side}[state]`, location.state);
  }
}

export function useCityComparison() {
  const comparison = ref<CityComparisonData | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  let controller: AbortController | undefined;
  let requestSequence = 0;

  function clear(): void {
    requestSequence += 1;
    controller?.abort();
    controller = undefined;
    comparison.value = null;
    loading.value = false;
    error.value = null;
  }

  async function compare(left: LocationData, right: LocationData): Promise<void> {
    const sequence = ++requestSequence;

    controller?.abort();
    controller = new AbortController();
    comparison.value = null;
    loading.value = true;
    error.value = null;

    try {
      const parameters = new URLSearchParams();
      appendLocation(parameters, 'left', left);
      appendLocation(parameters, 'right', right);

      const response = await fetch(`/weather/compare/results?${parameters.toString()}`, {
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });

      if (!response.ok) {
        throw await apiRequestError(
          response,
          'Não foi possível comparar as cidades agora. Tente novamente.',
        );
      }

      const payload: unknown = await response.json();

      if (!isComparisonResponse(payload)) {
        throw new Error('City comparison returned an invalid response.');
      }

      if (sequence === requestSequence) {
        comparison.value = payload.data;
      }
    } catch (reason: unknown) {
      if (reason instanceof DOMException && reason.name === 'AbortError') {
        return;
      }

      if (sequence === requestSequence) {
        error.value = safeRequestErrorMessage(
          reason,
          'Não foi possível comparar as cidades agora. Tente novamente.',
        );
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
    comparison,
    loading,
    error,
    compare,
    clear,
  };
}
