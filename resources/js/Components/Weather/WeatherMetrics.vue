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
    class="grid grid-cols-2 gap-3 md:grid-cols-4"
  >
    <article
      v-for="metric in metrics"
      :key="metric.label"
      class="rounded-3xl border border-white/15 bg-white/10 px-4 py-5 backdrop-blur"
    >
      <div class="flex items-center gap-2 opacity-65">
        <component
          :is="metric.icon"
          aria-hidden="true"
          :stroke-width="1.75"
        />
        <p class="text-xs font-semibold tracking-wide uppercase">
          {{ metric.label }}
        </p>
      </div>
      <p class="mt-2 text-xl font-medium">
        {{ metric.value }}
      </p>
    </article>
  </section>
</template>
