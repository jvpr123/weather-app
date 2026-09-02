<script setup lang="ts">
import { ArrowLeftRight, LoaderCircle } from '@lucide/vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ComparisonResult from '@/Components/Comparison/ComparisonResult.vue';
import LocationSearch from '@/Components/Location/LocationSearch.vue';
import { useCityComparison } from '@/Composables/useCityComparison';
import type { LocationData } from '@/Types/location';

const leftQuery = ref('');
const rightQuery = ref('');
const leftLocation = ref<LocationData | null>(null);
const rightLocation = ref<LocationData | null>(null);
const {
  comparison,
  loading,
  error,
  compare,
  clear,
} = useCityComparison();

const sameLocation = computed(() => leftLocation.value !== null
  && rightLocation.value !== null
  && leftLocation.value.latitude === rightLocation.value.latitude
  && leftLocation.value.longitude === rightLocation.value.longitude);
const canCompare = computed(() => leftLocation.value !== null
  && rightLocation.value !== null
  && !sameLocation.value
  && !loading.value);

function selectLeft(location: LocationData): void {
  leftLocation.value = location;
  clear();
}

function selectRight(location: LocationData): void {
  rightLocation.value = location;
  clear();
}

function submit(): void {
  if (!leftLocation.value || !rightLocation.value || sameLocation.value) {
    return;
  }

  void compare(leftLocation.value, rightLocation.value);
}
</script>

<template>
  <Head title="Comparar cidades" />

  <main class="relative isolate min-h-screen overflow-x-hidden bg-slate-950 px-4 py-6 text-white">
    <div
      aria-hidden="true"
      class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_50%_-10%,#155e75_0%,#0f3043_38%,#071923_72%,#020617_100%)]"
    />
    <div
      aria-hidden="true"
      class="absolute top-0 left-1/2 -z-10 h-80 w-[36rem] -translate-x-1/2 rounded-full bg-cyan-200/10 blur-3xl"
    />

    <div class="mx-auto w-full max-w-4xl">
      <header class="flex items-center justify-between gap-4">
        <p class="text-sm font-semibold tracking-[0.22em] uppercase opacity-80">
          WeatherLens
        </p>
        <Link
          href="/"
          class="rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100"
        >
          Voltar ao clima
        </Link>
      </header>

      <section class="mx-auto mt-12 max-w-2xl text-center">
        <p class="text-xs font-semibold tracking-[0.2em] text-cyan-200 uppercase">
          Condições lado a lado
        </p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
          Comparar cidades
        </h1>
        <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-slate-300 sm:text-base">
          Escolha dois lugares para comparar clima atual e condições para atividades ao ar livre.
        </p>
      </section>

      <form
        class="mt-10"
        @submit.prevent="submit"
      >
        <div class="grid items-start gap-4 md:grid-cols-[1fr_auto_1fr]">
          <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
            <label class="mb-3 block text-left text-xs font-semibold tracking-wider uppercase opacity-65">
              Primeira cidade
            </label>
            <LocationSearch
              v-model="leftQuery"
              @select="selectLeft"
            />
          </div>

          <div class="flex items-center justify-center md:h-full">
            <span class="inline-flex size-11 items-center justify-center rounded-full border border-white/15 bg-white/10 text-xs font-bold">
              VS
            </span>
          </div>

          <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
            <label class="mb-3 block text-left text-xs font-semibold tracking-wider uppercase opacity-65">
              Segunda cidade
            </label>
            <LocationSearch
              v-model="rightQuery"
              @select="selectRight"
            />
          </div>
        </div>

        <p
          v-if="sameLocation"
          role="alert"
          class="mt-4 text-center text-sm text-amber-200"
        >
          Escolha duas cidades diferentes para comparar.
        </p>
        <p
          v-if="error"
          role="alert"
          class="mt-4 rounded-2xl border border-rose-200/20 bg-rose-200/10 px-4 py-3 text-center text-sm text-rose-50"
        >
          {{ error }}
        </p>

        <button
          type="submit"
          :disabled="!canCompare"
          class="mx-auto mt-6 inline-flex min-h-12 min-w-48 items-center justify-center gap-3 rounded-2xl bg-cyan-100 px-6 py-3 font-semibold text-slate-950 transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100 disabled:cursor-not-allowed disabled:opacity-40"
        >
          <LoaderCircle
            v-if="loading"
            aria-hidden="true"
            class="size-5 animate-spin"
          />
          <ArrowLeftRight
            v-else
            aria-hidden="true"
            class="size-5"
          />
          {{ loading ? 'Comparando...' : 'Comparar' }}
        </button>
      </form>

      <ComparisonResult
        v-if="comparison"
        :comparison="comparison"
      />
    </div>
  </main>
</template>
