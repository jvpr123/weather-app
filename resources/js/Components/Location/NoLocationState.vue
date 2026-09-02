<script setup lang="ts">
import { computed } from 'vue';
import type { GeolocationStatus } from '@/Composables/useGeolocation';
import type { Coordinates } from '@/Types/location';

interface StateContent {
  eyebrow: string;
  title: string;
  description: string;
}

const props = defineProps<{
  status: GeolocationStatus;
  coordinates: Coordinates | null;
  resolvingLocation: boolean;
}>();

const content: Record<GeolocationStatus, StateContent> = {
  idle: {
    eyebrow: 'Previsão local',
    title: 'Descubra o clima onde você está',
    description: 'Use sua localização atual ou procure uma cidade no topo para começar.',
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
    description: 'Você ainda pode procurar qualquer cidade no campo acima.',
  },
  unavailable: {
    eyebrow: 'Localização indisponível',
    title: 'Seu navegador não conseguiu localizar você',
    description: 'Tente novamente ou use a busca de cidades para continuar.',
  },
  timeout: {
    eyebrow: 'Tempo esgotado',
    title: 'Sua localização demorou a responder',
    description: 'Você pode tentar novamente ou procurar uma cidade agora.',
  },
};

const currentContent = computed<StateContent>(() => props.resolvingLocation
  ? {
      eyebrow: 'Localização encontrada',
      title: 'Identificando sua cidade',
      description: 'Estamos associando suas coordenadas a uma localidade próxima.',
    }
  : content[props.status]);

const locationDetail = computed(() => props.coordinates
  ? `${props.coordinates.latitude.toFixed(4)}, ${props.coordinates.longitude.toFixed(4)}`
  : null);
</script>

<template>
  <section class="flex flex-1 items-center justify-center py-8 text-center sm:py-10">
    <div class="w-full max-w-xl">
      <div class="relative mx-auto mb-6 flex size-24 items-center justify-center rounded-full border border-cyan-100/20 bg-cyan-100/10 shadow-2xl shadow-black/20 backdrop-blur sm:mb-8 sm:size-28 md:size-32">
        <span
          aria-hidden="true"
          class="absolute inset-3 rounded-full border border-cyan-200/10 motion-safe:animate-pulse"
        />
        <svg
          aria-hidden="true"
          class="size-10 text-cyan-100 sm:size-12 md:size-14"
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

      <div aria-live="polite">
        <p class="text-xs font-semibold tracking-[0.2em] text-cyan-200 uppercase">
          {{ currentContent.eyebrow }}
        </p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl md:text-5xl">
          {{ currentContent.title }}
        </h1>
        <p class="mx-auto mt-4 max-w-md text-base leading-7 opacity-70">
          {{ currentContent.description }}
        </p>
        <p
          v-if="locationDetail"
          class="mt-3 font-mono text-sm text-cyan-100"
        >
          {{ locationDetail }}
        </p>
      </div>

      <p class="mt-6 text-xs leading-5 opacity-50 sm:mt-8">
        Sua localização é usada somente para encontrar a previsão da sua região.
      </p>
    </div>
  </section>
</template>
