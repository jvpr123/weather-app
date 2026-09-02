<script setup lang="ts">
import { computed } from 'vue';
import type { CityComparisonData, ComparedCityData } from '@/Types/comparison';
import { weatherSymbol } from '@/Utils/weather';

const props = defineProps<{
  comparison: CityComparisonData;
}>();

const cities = computed(() => [props.comparison.left, props.comparison.right]);
const recommendation = computed(() => {
  if (props.comparison.recommendation === 'tie') {
    return 'As duas cidades apresentam condições equivalentes para atividades ao ar livre.';
  }

  const city = props.comparison[props.comparison.recommendation];

  return `${city.location.name} apresenta as melhores condições para atividades ao ar livre.`;
});

function locationDetail(city: ComparedCityData): string {
  return [city.location.state, city.location.country].filter(Boolean).join(', ');
}
</script>

<template>
  <section aria-labelledby="comparison-result-title" class="mt-8">
    <h2 id="comparison-result-title" class="sr-only">Resultado da comparação</h2>

    <div
      class="grid grid-cols-2 overflow-hidden rounded-2xl border border-white/15 bg-white/10 backdrop-blur-xl sm:rounded-3xl"
    >
      <article
        v-for="(city, index) in cities"
        :key="`${city.location.latitude}:${city.location.longitude}`"
        class="min-w-0 px-2.5 py-5 text-center sm:px-6 sm:py-6"
        :class="{ 'border-l border-white/15': index === 1 }"
      >
        <h3
          class="line-clamp-2 min-h-12 content-center text-base leading-6 font-semibold sm:min-h-0 sm:text-xl"
        >
          {{ city.location.name }}
        </h3>
        <p class="mt-1 truncate text-xs opacity-70 sm:text-sm">
          {{ locationDetail(city) }}
        </p>
        <p class="mt-4 text-4xl font-light tracking-tight sm:mt-5 sm:text-6xl">
          {{ Math.round(city.current.temperature) }}°
        </p>
        <p
          class="mt-2 flex min-h-12 flex-col items-center justify-center gap-1 text-sm capitalize sm:min-h-0 sm:flex-row sm:gap-2 sm:text-base"
        >
          <span aria-hidden="true">
            {{ weatherSymbol(city.current.condition, !city.current.icon.endsWith('n')) }}
          </span>
          <span class="line-clamp-2">{{ city.current.description }}</span>
        </p>

        <dl class="mt-5 grid gap-3 text-xs sm:mt-6 sm:text-sm">
          <div class="flex justify-between gap-2">
            <dt class="opacity-75">Umidade</dt>
            <dd class="font-medium">{{ city.current.humidity }}%</dd>
          </div>
          <div class="flex justify-between gap-2">
            <dt class="opacity-75">Chuva</dt>
            <dd class="font-medium">{{ Math.round(city.rainProbability * 100) }}%</dd>
          </div>
          <div class="flex justify-between gap-2">
            <dt class="opacity-75">Vento</dt>
            <dd class="font-medium">{{ Math.round(city.current.windSpeed * 3.6) }} km/h</dd>
          </div>
        </dl>
      </article>
    </div>

    <div
      class="mt-5 rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-xl sm:mt-6 sm:rounded-3xl sm:p-6"
    >
      <h3 class="text-sm font-semibold tracking-[0.12em] uppercase opacity-75">Outdoor Score</h3>
      <div class="mt-5 grid gap-5">
        <div
          v-for="city in cities"
          :key="`score-${city.location.latitude}:${city.location.longitude}`"
        >
          <div class="flex items-center justify-between gap-4 text-sm">
            <span class="truncate font-medium">{{ city.location.name }}</span>
            <strong class="tabular-nums">{{ city.outdoorScore.toFixed(1) }}</strong>
          </div>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-black/20">
            <div
              role="progressbar"
              :aria-label="`Outdoor Score de ${city.location.name}`"
              :aria-valuenow="city.outdoorScore"
              aria-valuemin="0"
              aria-valuemax="10"
              class="h-full rounded-full bg-cyan-200 transition-[width] duration-500"
              :style="{ width: `${city.outdoorScore * 10}%` }"
            />
          </div>
        </div>
      </div>
      <p class="mt-6 border-t border-white/10 pt-5 text-sm leading-6 text-cyan-50">
        {{ recommendation }}
      </p>
      <p class="mt-2 text-xs opacity-70">
        Score heurístico da aplicação; não representa um índice científico.
      </p>
    </div>
  </section>
</template>
