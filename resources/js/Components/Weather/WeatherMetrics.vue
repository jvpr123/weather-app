<script setup lang="ts">
import { computed } from 'vue';
import type { CurrentWeatherData } from '@/Types/weather';
import { Droplets, Gauge, Thermometer, Wind } from '@lucide/vue';

const props = defineProps<{
  current: CurrentWeatherData;
}>();

const metrics = computed(() => [
  {
    icon: Thermometer,
    label: 'Sensação',
    value: `${Math.round(props.current.feelsLike)}°`,
  },
  {
    icon: Droplets,
    label: 'Umidade',
    value: `${props.current.humidity}%`,
  },
  {
    icon: Wind,
    label: 'Vento',
    value: `${Math.round(props.current.windSpeed * 3.6)} km/h`,
  },
  {
    icon: Gauge,
    label: 'Pressão',
    value: `${props.current.pressure} hPa`,
  },
]);
</script>

<template>
  <section
    aria-label="Métricas atuais"
    class="grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-4"
  >
    <article
      v-for="metric in metrics"
      :key="metric.label"
      class="min-w-0 rounded-2xl border border-white/15 bg-white/10 px-3 py-4 backdrop-blur sm:rounded-3xl sm:px-4 sm:py-5"
    >
      <div class="flex items-center gap-2 opacity-65">
        <component
          :is="metric.icon"
          aria-hidden="true"
          class="size-4 shrink-0 sm:size-5"
          :stroke-width="1.75"
        />
        <p class="text-xs font-semibold tracking-wide uppercase">
          {{ metric.label }}
        </p>
      </div>
      <p class="mt-2 truncate text-lg font-medium sm:text-xl">
        {{ metric.value }}
      </p>
    </article>
  </section>
</template>
