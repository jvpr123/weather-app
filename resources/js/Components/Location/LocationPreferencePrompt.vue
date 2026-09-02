<script setup lang="ts">
import { MapPin } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import type { LocationData } from '@/Types/location';

defineProps<{
  savedLocation: LocationData;
  currentLocation: LocationData;
}>();

defineEmits<{
  keep: [];
  useCurrent: [];
}>();

const prompt = ref<HTMLElement | null>(null);

function locationLabel(location: LocationData): string {
  return [location.name, location.state, location.country].filter(Boolean).join(', ');
}

onMounted(() => prompt.value?.focus());
</script>

<template>
  <section
    ref="prompt"
    role="dialog"
    tabindex="-1"
    aria-labelledby="location-preference-title"
    aria-describedby="location-preference-description"
    class="relative z-10 mt-2 rounded-2xl border border-cyan-100/20 bg-slate-950/75 p-4 shadow-xl shadow-black/20 backdrop-blur-xl focus:outline-none sm:p-5"
  >
    <div class="flex items-start gap-3">
      <span
        class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-cyan-100/10 text-cyan-100"
      >
        <MapPin aria-hidden="true" class="size-5" />
      </span>
      <div class="min-w-0 flex-1">
        <h2 id="location-preference-title" class="font-semibold">Usar sua localização atual?</h2>
        <p id="location-preference-description" class="mt-1 text-sm leading-6 opacity-75">
          Você estava vendo {{ locationLabel(savedLocation) }}, mas parece estar em
          {{ locationLabel(currentLocation) }}.
        </p>
        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
          <button
            type="button"
            class="min-h-11 rounded-xl border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold transition hover:bg-white/15 active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100"
            @click="$emit('keep')"
          >
            Manter {{ savedLocation.name }}
          </button>
          <button
            type="button"
            class="min-h-11 rounded-xl bg-cyan-100 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-white active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100"
            @click="$emit('useCurrent')"
          >
            Usar {{ currentLocation.name }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
