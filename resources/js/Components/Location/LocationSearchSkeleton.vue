<template>
  <div role="status" aria-label="Buscando cidades" class="space-y-1.5 p-1.5">
    <div
      v-for="index in 3"
      :key="index"
      aria-hidden="true"
      class="rounded-xl px-3 py-3 motion-safe:animate-pulse"
    >
      <div class="h-4 rounded-full bg-white/15" :class="index === 2 ? 'w-2/5' : 'w-3/5'" />
      <div class="mt-2 h-3 w-1/3 rounded-full bg-white/10" />
    </div>
  </div>
</template>
