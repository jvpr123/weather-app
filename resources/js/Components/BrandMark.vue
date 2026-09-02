<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const iconPath = '/images/weatherlens-icon.png';

withDefaults(
  defineProps<{
    href?: string;
  }>(),
  {
    href: '/',
  },
);
</script>

<template>
  <Link
    :href="href"
    aria-label="WeatherLens — página inicial"
    class="group/brand inline-flex shrink-0 items-center gap-2 rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100"
  >
    <img
      :src="iconPath"
      alt=""
      width="36"
      height="36"
      class="size-9 shrink-0 object-contain drop-shadow-sm transition duration-300 ease-out motion-safe:group-hover/brand:-translate-y-0.5 motion-safe:group-hover/brand:scale-105 motion-safe:group-active/brand:translate-y-0 motion-safe:group-active/brand:scale-95"
    />
    <span class="text-sm font-semibold tracking-[0.22em] uppercase opacity-80">WeatherLens</span>
  </Link>
</template>
