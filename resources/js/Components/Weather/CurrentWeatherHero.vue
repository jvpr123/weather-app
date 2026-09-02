<script setup lang="ts">
import { computed } from 'vue';
import type { LocationData } from '@/Types/location';
import type { CurrentWeatherData } from '@/Types/weather';
import { weatherSymbol } from '@/Utils/weather';
import { ThermometerSnowflake, ThermometerSun } from '@lucide/vue';

const props = defineProps<{
  location: LocationData;
  current: CurrentWeatherData;
}>();

const locationDetail = computed(() => [props.location.state, props.location.country]
  .filter(Boolean)
  .join(', '));
</script>

<template>
  <section class="px-1 py-7 text-center sm:px-2 sm:py-9 lg:py-12">
    <p class="mx-auto max-w-2xl text-sm font-semibold tracking-[0.12em] break-words uppercase opacity-75 sm:tracking-[0.16em]">
      {{ location.name }}<span v-if="locationDetail">, {{ locationDetail }}</span>
    </p>
    <div class="mt-4 flex items-start justify-center">
      <span class="text-7xl font-light tracking-tighter sm:text-8xl">
        {{ Math.round(current.temperature) }}
      </span>
      <span class="mt-2 text-3xl font-light">°</span>
    </div>
    <p class="mt-3 text-lg font-medium capitalize">
      <span aria-hidden="true">{{ weatherSymbol(current.condition, !current.icon.endsWith('n')) }}</span>
      {{ current.description }}
    </p>
    <div class="mt-4 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm opacity-80">
      <span class="flex items-center gap-2"><ThermometerSnowflake class="size-4" /> Mín. {{ Math.round(current.minTemperature) }}°</span>
      <span class="flex items-center gap-2"><ThermometerSun class="size-4" /> Máx. {{ Math.round(current.maxTemperature) }}°</span>
    </div>
  </section>
</template>
