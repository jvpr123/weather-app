<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Previsão do tempo" />

  <main class="relative isolate flex min-h-screen items-center justify-center overflow-hidden px-6 py-16">
    <div
      aria-hidden="true"
      class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,#1d6682_0%,#0b3045_36%,#061826_72%)]"
    />

    <section class="w-full max-w-2xl rounded-3xl border border-white/15 bg-white/10 p-8 shadow-2xl shadow-black/20 backdrop-blur md:p-12">
      <p class="text-sm font-semibold tracking-[0.22em] text-cyan-200 uppercase">
        WeatherLens
      </p>
      <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white md:text-6xl">
        O clima, com clareza.
      </h1>
      <p class="mt-6 max-w-xl text-base leading-7 text-slate-200 md:text-lg">
        A fundação está pronta. Busca de localidades, previsão e comparação de cidades chegam nos próximos epics.
      </p>
      <div class="mt-8 flex flex-wrap gap-3 text-sm text-slate-100">
        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2">Laravel 13</span>
        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2">Inertia + Vue 3</span>
        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2">TypeScript</span>
      </div>
    </section>
  </main>
</template>
