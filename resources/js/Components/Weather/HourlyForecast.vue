<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import type { ForecastPeriodData } from '@/Types/weather';
import { weatherSymbol } from '@/Utils/weather';

defineProps<{
  periods: ForecastPeriodData[];
}>();

const carousel = ref<HTMLElement | null>(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

let resizeObserver: ResizeObserver | undefined;

function updateScrollState(): void {
  const element = carousel.value;

  if (!element) {
    return;
  }

  canScrollLeft.value = element.scrollLeft > 1;
  canScrollRight.value = element.scrollWidth - element.clientWidth - element.scrollLeft > 1;
}

function moveCarousel(direction: -1 | 1): void {
  const element = carousel.value;

  if (!element) {
    return;
  }

  element.scrollBy({
    left: direction * element.clientWidth * 0.75,
    behavior: 'smooth',
  });
}

function formatHour(timestamp: number): string {
  return new Intl.DateTimeFormat('pt-BR', {
    hour: '2-digit',
    hour12: false,
  }).format(new Date(timestamp * 1000));
}

onMounted(async () => {
  await nextTick();
  updateScrollState();

  if (carousel.value) {
    resizeObserver = new ResizeObserver(updateScrollState);
    resizeObserver.observe(carousel.value);
  }
});

onBeforeUnmount(() => resizeObserver?.disconnect());
</script>

<template>
  <section
    class="min-w-0 max-w-full"
    aria-labelledby="hourly-title"
  >
    <div class="flex items-center justify-between gap-4 px-1">
      <h2
        id="hourly-title"
        class="text-sm font-semibold tracking-[0.12em] uppercase opacity-75"
      >
        Próximas horas
      </h2>

      <div
        class="flex gap-2"
        aria-label="Navegação da previsão por hora"
      >
        <button
          type="button"
          :disabled="!canScrollLeft"
          aria-label="Ver horas anteriores"
          class="inline-flex size-9 items-center justify-center rounded-xl border border-white/15 bg-white/10 transition hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100 disabled:cursor-default disabled:opacity-30"
          @click="moveCarousel(-1)"
        >
          <ChevronLeft
            aria-hidden="true"
            class="size-4"
          />
        </button>
        <button
          type="button"
          :disabled="!canScrollRight"
          aria-label="Ver próximas horas"
          class="inline-flex size-9 items-center justify-center rounded-xl border border-white/15 bg-white/10 transition hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100 disabled:cursor-default disabled:opacity-30"
          @click="moveCarousel(1)"
        >
          <ChevronRight
            aria-hidden="true"
            class="size-4"
          />
        </button>
      </div>
    </div>

    <div class="relative mt-3 max-w-full overflow-hidden rounded-3xl">
      <div
        ref="carousel"
        data-horizontal-scroll
        class="flex max-w-full snap-x snap-mandatory gap-3 overflow-x-auto overscroll-x-contain pb-2 pr-6 scroll-smooth touch-pan-x [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        @scroll.passive="updateScrollState"
      >
        <article
          v-for="period in periods"
          :key="period.datetime"
          class="min-w-20 snap-start rounded-3xl border border-white/15 bg-white/10 px-3 py-4 text-center backdrop-blur"
        >
          <time
            :datetime="new Date(period.datetime * 1000).toISOString()"
            class="text-sm opacity-75"
          >
            {{ formatHour(period.datetime) }}h
          </time>
          <p
            aria-hidden="true"
            class="my-3 text-2xl"
          >
            {{ weatherSymbol(period.condition, period.isDaytime) }}
          </p>
          <p class="text-lg font-semibold">
            {{ Math.round(period.temperature) }}°
          </p>
          <p
            v-if="period.probabilityOfPrecipitation > 0.1"
            class="mt-1 text-xs text-cyan-100"
          >
            {{ Math.round(period.probabilityOfPrecipitation * 100) }}%
          </p>
        </article>
      </div>

      <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-y-0 left-0 z-10 w-12 bg-gradient-to-r from-black/30 to-transparent backdrop-blur-[2px] transition-opacity duration-300"
        :class="canScrollLeft ? 'opacity-100' : 'opacity-0'"
      />
      <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-y-0 right-0 z-10 w-12 bg-gradient-to-l from-black/30 to-transparent backdrop-blur-[2px] transition-opacity duration-300"
        :class="canScrollRight ? 'opacity-100' : 'opacity-0'"
      />
    </div>
  </section>
</template>
