import { computed, onScopeDispose, ref } from 'vue';
import type { LocationData } from '@/Types/location';
import { isLocationData } from '@/Utils/location';

interface LocationSearchResponse {
  data: LocationData[];
}

const SEARCH_DELAY = 300;
const MINIMUM_QUERY_LENGTH = 2;

function isSearchResponse(value: unknown): value is LocationSearchResponse {
  return typeof value === 'object'
    && value !== null
    && Array.isArray((value as Record<string, unknown>).data)
    && (value as LocationSearchResponse).data.every(isLocationData);
}

export function useLocationSearch() {
  const results = ref<LocationData[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const hasSearched = ref(false);
  const isEmpty = computed(() => hasSearched.value && !loading.value && !error.value && results.value.length === 0);

  let debounceTimer: ReturnType<typeof setTimeout> | undefined;
  let controller: AbortController | undefined;
  let searchSequence = 0;

  function cancelPending(): void {
    if (debounceTimer !== undefined) {
      clearTimeout(debounceTimer);
      debounceTimer = undefined;
    }

    controller?.abort();
    controller = undefined;
  }

  function clear(): void {
    searchSequence += 1;
    cancelPending();
    results.value = [];
    loading.value = false;
    error.value = null;
    hasSearched.value = false;
  }

  async function execute(query: string, sequence: number): Promise<void> {
    controller = new AbortController();
    loading.value = true;
    error.value = null;
    hasSearched.value = false;

    try {
      const parameters = new URLSearchParams({ q: query });
      const response = await fetch(`/locations/search?${parameters.toString()}`, {
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });

      if (!response.ok) {
        throw new Error('Location search request failed.');
      }

      const payload: unknown = await response.json();

      if (!isSearchResponse(payload)) {
        throw new Error('Location search returned an invalid response.');
      }

      if (sequence === searchSequence) {
        results.value = payload.data;
        hasSearched.value = true;
      }
    } catch (reason: unknown) {
      if (reason instanceof DOMException && reason.name === 'AbortError') {
        return;
      }

      if (sequence === searchSequence) {
        results.value = [];
        error.value = 'Não foi possível buscar cidades agora. Tente novamente.';
        hasSearched.value = true;
      }
    } finally {
      if (sequence === searchSequence) {
        loading.value = false;
        controller = undefined;
      }
    }
  }

  function search(value: string): void {
    const query = value.trim();
    const sequence = ++searchSequence;

    cancelPending();

    if (query.length < MINIMUM_QUERY_LENGTH) {
      results.value = [];
      loading.value = false;
      error.value = null;
      hasSearched.value = false;
      return;
    }

    results.value = [];
    loading.value = true;
    error.value = null;
    hasSearched.value = false;

    debounceTimer = setTimeout(() => {
      debounceTimer = undefined;
      void execute(query, sequence);
    }, SEARCH_DELAY);
  }

  onScopeDispose(cancelPending);

  return {
    results,
    loading,
    error,
    isEmpty,
    search,
    clear,
  };
}
