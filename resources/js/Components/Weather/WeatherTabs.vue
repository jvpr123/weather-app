<script setup lang="ts">
import { nextTick, ref } from 'vue';

export type WeatherTab = 'current' | 'forecast';

const tabs = [
  ['current', 'Agora'],
  ['forecast', 'Previsão'],
] as const;

const props = defineProps<{
  modelValue: WeatherTab;
}>();

const emit = defineEmits<{
  'update:modelValue': [tab: WeatherTab];
}>();

const SWIPE_THRESHOLD = 50;
const tabButtons = ref<HTMLButtonElement[]>([]);
let touchStartX: number | null = null;

function select(tab: WeatherTab): void {
  emit('update:modelValue', tab);
}

function selectAndFocus(index: number): void {
  const tab = tabs[index];

  if (!tab) {
    return;
  }

  select(tab[0]);
  void nextTick(() => tabButtons.value[index]?.focus());
}

function handleKeydown(event: KeyboardEvent, index: number): void {
  let nextIndex: number | null = null;

  if (event.key === 'ArrowRight') {
    nextIndex = (index + 1) % tabs.length;
  } else if (event.key === 'ArrowLeft') {
    nextIndex = (index - 1 + tabs.length) % tabs.length;
  } else if (event.key === 'Home') {
    nextIndex = 0;
  } else if (event.key === 'End') {
    nextIndex = tabs.length - 1;
  }

  if (nextIndex !== null) {
    event.preventDefault();
    selectAndFocus(nextIndex);
  }
}

function handleTouchStart(event: TouchEvent): void {
  if (event.target instanceof Element && event.target.closest('[data-horizontal-scroll]')) {
    touchStartX = null;
    return;
  }

  touchStartX = event.changedTouches[0]?.clientX ?? null;
}

function handleTouchEnd(event: TouchEvent): void {
  const touchEndX = event.changedTouches[0]?.clientX;

  if (touchStartX === null || touchEndX === undefined) {
    touchStartX = null;
    return;
  }

  const distance = touchEndX - touchStartX;
  touchStartX = null;

  if (Math.abs(distance) < SWIPE_THRESHOLD) {
    return;
  }

  if (distance < 0 && props.modelValue === 'current') {
    select('forecast');
  } else if (distance > 0 && props.modelValue === 'forecast') {
    select('current');
  }
}
</script>

<template>
  <div
    @touchstart.passive="handleTouchStart"
    @touchend.passive="handleTouchEnd"
  >
    <div
      role="tablist"
      aria-label="Seções da previsão"
      class="grid grid-cols-2 rounded-2xl bg-black/10 p-1"
    >
      <button
        v-for="(tab, index) in tabs"
        :id="`weather-tab-${tab[0]}`"
        ref="tabButtons"
        :key="tab[0]"
        type="button"
        role="tab"
        :aria-controls="`weather-panel-${tab[0]}`"
        :aria-selected="modelValue === tab[0]"
        :tabindex="modelValue === tab[0] ? 0 : -1"
        class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100"
        :class="modelValue === tab[0] ? 'bg-white/20 shadow-sm' : 'opacity-75 hover:opacity-100'"
        @click="select(tab[0])"
        @keydown="handleKeydown($event, index)"
      >
        {{ tab[1] }}
      </button>
    </div>

    <slot />
  </div>
</template>
