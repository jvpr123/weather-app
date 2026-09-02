<template>
  <section
    aria-hidden="true"
    class="motion-safe:animate-pulse"
  >
    <div class="grid grid-cols-2 gap-1 rounded-2xl bg-black/10 p-1">
      <div class="h-14 rounded-xl bg-white/15" />
      <div class="h-14 rounded-xl bg-white/5" />
    </div>

    <div class="grid grid-cols-2 gap-3 py-4 sm:grid-cols-4">
      <article
        v-for="index in 4"
        :key="index"
        class="h-28 rounded-3xl border border-white/10 bg-white/10 p-4"
      >
        <div class="h-3 w-16 rounded-full bg-white/15" />
        <div class="mt-5 h-6 w-20 rounded-full bg-white/15" />
      </article>
    </div>
  </section>
</template>
