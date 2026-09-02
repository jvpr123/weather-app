<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
  href: string;
  label: string;
}>();
</script>

<template>
  <Link
    :href="href"
    :aria-label="label"
    class="inline-flex size-[54px] shrink-0 items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/10 font-semibold backdrop-blur transition hover:bg-white/15 active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100 md:h-[54px] md:w-52 md:px-6"
  >
    <slot />
    <span class="hidden whitespace-nowrap md:inline">{{ label }}</span>
  </Link>
</template>
