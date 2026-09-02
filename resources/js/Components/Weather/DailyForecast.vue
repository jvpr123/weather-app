<script setup lang="ts">
import type { DailyForecastData } from '@/Types/weather';
import { weatherConditionLabel, weatherSymbol } from '@/Utils/weather';

defineProps<{
  days: DailyForecastData[];
}>();

function formatDay(date: string): string {
  const today = new Date().toISOString().slice(0, 10);

  if (date === today) {
    return 'Hoje';
  }

  return new Intl.DateTimeFormat('pt-BR', {
    weekday: 'short',
    timeZone: 'UTC',
  }).format(new Date(`${date}T12:00:00Z`)).replace('.', '');
}
</script>

<template>
  <section
    class="min-w-0 max-w-full"
    aria-labelledby="daily-title"
  >
    <h2
      id="daily-title"
      class="px-1 text-sm font-semibold tracking-[0.12em] uppercase opacity-75"
    >
      Próximos dias
    </h2>
    <div class="mt-3 w-full max-w-full overflow-hidden rounded-2xl border border-white/15 bg-white/10 backdrop-blur sm:rounded-3xl">
      <article
        v-for="(day, index) in days"
        :key="day.date"
        class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto_auto] items-center gap-2 px-3 py-4 sm:gap-4 sm:px-4"
        :class="{ 'border-t border-white/10': index > 0 }"
      >
        <div class="min-w-0">
          <p class="truncate text-sm font-medium capitalize sm:text-base">
            {{ formatDay(day.date) }}
          </p>
          <p
            v-if="day.maxRainProbability > 0.1"
            class="mt-1 text-xs opacity-75"
          >
            Chuva {{ Math.round(day.maxRainProbability * 100) }}%
          </p>
        </div>
        <span
          aria-hidden="true"
          class="text-2xl"
        >
          {{ weatherSymbol(day.dominantCondition) }}
        </span>
        <span class="sr-only">{{ weatherConditionLabel(day.dominantCondition) }}</span>
        <p class="whitespace-nowrap text-right text-sm tabular-nums sm:text-base">
          <span class="opacity-60">{{ Math.round(day.minTemperature) }}°</span>
          <span class="mx-1 opacity-35 sm:mx-2">━</span>
          <span class="font-semibold">{{ Math.round(day.maxTemperature) }}°</span>
        </p>
      </article>
    </div>
  </section>
</template>
