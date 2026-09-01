<script setup lang="ts">
import { computed, ref } from 'vue';
import LocationSearch from '@/Components/Location/LocationSearch.vue';
import { useGeolocation } from '@/Composables/useGeolocation';
import type { GeolocationStatus } from '@/Composables/useGeolocation';
import { useReverseGeocoding } from '@/Composables/useReverseGeocoding';
import type { LocationData } from '@/Types/location';

interface StateContent {
  eyebrow: string;
  title: string;
  description: string;
}

const content: Record<GeolocationStatus, StateContent> = {
  idle: {
    eyebrow: 'Previsão local',
    title: 'Descubra o clima onde você está',
    description: 'Use sua localização atual ou procure uma cidade para começar.',
  },
  requesting: {
    eyebrow: 'Localizando',
    title: 'Procurando você no mapa',
    description: 'Isso deve levar apenas alguns segundos.',
  },
  available: {
    eyebrow: 'Localização encontrada',
    title: 'Tudo pronto para consultar o clima',
    description: 'Suas coordenadas foram encontradas com segurança pelo navegador.',
  },
  denied: {
    eyebrow: 'Permissão necessária',
    title: 'Não conseguimos acessar sua localização',
    description: 'Você ainda pode procurar qualquer cidade manualmente.',
  },
  unavailable: {
    eyebrow: 'Localização indisponível',
    title: 'Seu navegador não conseguiu localizar você',
    description: 'Tente novamente ou use a busca manual para continuar.',
  },
  timeout: {
    eyebrow: 'Tempo esgotado',
    title: 'Sua localização demorou a responder',
    description: 'Você pode tentar novamente ou procurar uma cidade agora.',
  },
};

const query = ref('');
const manualSearchOpen = ref(false);
const selectedLocation = ref<LocationData | null>(null);
const {
  status,
  coordinates,
  request,
  reset: resetGeolocation,
} = useGeolocation();
const {
  loading: resolvingLocation,
  resolve: resolveLocation,
  clear: clearLocationResolution,
} = useReverseGeocoding();

const locationRequestPending = computed(() => status.value === 'requesting' || resolvingLocation.value);
const locationButtonLabel = computed(() => {
  if (resolvingLocation.value) {
    return 'Identificando cidade...';
  }

  return status.value === 'requesting'
    ? 'Buscando localização...'
    : 'Usar minha localização';
});

const currentContent = computed<StateContent>(() => {
  if (selectedLocation.value) {
    return {
      eyebrow: 'Cidade selecionada',
      title: selectedLocation.value.name,
      description: 'Localização pronta para consultar o clima.',
    };
  }

  if (resolvingLocation.value) {
    return {
      eyebrow: 'Localização encontrada',
      title: 'Identificando sua cidade',
      description: 'Estamos associando suas coordenadas a uma localidade próxima.',
    };
  }

  return content[status.value];
});

const locationDetail = computed(() => {
  if (selectedLocation.value) {
    return [selectedLocation.value.state, selectedLocation.value.country]
      .filter(Boolean)
      .join(', ');
  }

  if (coordinates.value) {
    return `${coordinates.value.latitude.toFixed(4)}, ${coordinates.value.longitude.toFixed(4)}`;
  }

  return null;
});

async function useCurrentLocation(): Promise<void> {
  selectedLocation.value = null;
  clearLocationResolution();

  const foundCoordinates = await request();

  if (foundCoordinates) {
    selectedLocation.value = await resolveLocation(foundCoordinates);
  }
}

function openManualSearch(): void {
  clearLocationResolution();
  resetGeolocation();
  manualSearchOpen.value = true;
}

function selectLocation(location: LocationData): void {
  clearLocationResolution();
  resetGeolocation();
  selectedLocation.value = location;
}
</script>

<template>
  <main class="relative isolate min-h-screen overflow-hidden bg-slate-950 text-white">
    <div
      aria-hidden="true"
      class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_50%_-10%,#247a9a_0%,#0d3a50_36%,#071b2a_68%,#050d16_100%)]"
    />
    <div
      aria-hidden="true"
      class="absolute top-16 left-1/2 -z-10 h-64 w-64 -translate-x-1/2 rounded-full bg-cyan-200/10 blur-3xl md:h-96 md:w-96"
    />

    <div class="mx-auto flex min-h-screen w-full max-w-3xl flex-col px-4 pt-10 pb-4 sm:px-6 md:justify-center md:py-12">
      <header class="flex items-center justify-between px-2">
        <p class="text-sm font-semibold tracking-[0.22em] text-cyan-100 uppercase">
          WeatherLens
        </p>
        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">
          Agora
        </span>
      </header>

      <section class="flex flex-1 flex-col justify-end pt-12 md:flex-none md:pt-16">
        <div class="flex flex-1 flex-col items-center justify-center px-4 pb-12 text-center md:flex-none md:pb-14">
          <div class="relative mb-8 flex size-28 items-center justify-center rounded-full border border-cyan-100/20 bg-cyan-100/10 shadow-2xl shadow-cyan-950/40 backdrop-blur md:size-32">
            <span
              aria-hidden="true"
              class="absolute inset-3 animate-pulse rounded-full border border-cyan-200/10"
            />
            <svg
              aria-hidden="true"
              class="size-12 text-cyan-100 md:size-14"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11Z"
              />
              <circle
                cx="12"
                cy="10"
                r="2.25"
              />
            </svg>
          </div>

          <div
            aria-live="polite"
            class="max-w-xl"
          >
            <p class="text-xs font-semibold tracking-[0.2em] text-cyan-200 uppercase">
              {{ currentContent.eyebrow }}
            </p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl md:text-5xl">
              {{ currentContent.title }}
            </h1>
            <p class="mx-auto mt-4 max-w-md text-base leading-7 text-slate-300">
              {{ currentContent.description }}
            </p>
            <p
              v-if="locationDetail"
              class="mt-3 font-mono text-sm text-cyan-100"
            >
              {{ locationDetail }}
            </p>
          </div>
        </div>

        <div class="rounded-4xl border border-white/10 bg-white/10 p-4 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-6">
          <div class="grid gap-3 sm:grid-cols-2">
            <button
              type="button"
              :disabled="locationRequestPending"
              class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-cyan-100 px-5 py-3 font-semibold text-slate-950 transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100 disabled:cursor-wait disabled:opacity-60"
              @click="useCurrentLocation"
            >
              <span
                v-if="locationRequestPending"
                aria-hidden="true"
                class="size-4 animate-spin rounded-full border-2 border-slate-950/25 border-t-slate-950"
              />
              {{ locationButtonLabel }}
            </button>

            <button
              type="button"
              class="min-h-12 rounded-2xl border border-white/15 bg-white/5 px-5 py-3 font-semibold text-white transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100"
              @click="openManualSearch"
            >
              Adicionar local manualmente
            </button>
          </div>

          <div
            v-if="manualSearchOpen"
            class="mt-4 border-t border-white/10 pt-4"
          >
            <LocationSearch
              v-model="query"
              placement="top"
              @select="selectLocation"
            />
          </div>

          <p class="mt-4 text-center text-xs leading-5 text-slate-400">
            Sua localização é usada somente para encontrar a previsão da sua região.
          </p>
        </div>
      </section>
    </div>
  </main>
</template>
