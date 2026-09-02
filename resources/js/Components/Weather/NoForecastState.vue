<script setup lang="ts">
import { CloudOff } from '@lucide/vue';
</script>

<template>
  <section
    role="status"
    class="flex min-h-52 items-center justify-center rounded-2xl border border-white/10 bg-white/5 px-4 py-8 text-center backdrop-blur sm:rounded-3xl sm:px-6"
  >
    <div class="max-w-sm">
      <CloudOff aria-hidden="true" class="mx-auto size-9 opacity-70" :stroke-width="1.5" />
      <h2 class="mt-4 text-lg font-semibold">Previsão ainda indisponível</h2>
      <p class="mt-2 text-sm leading-6 opacity-70">
        Não recebemos dados para as próximas horas. As condições atuais continuam disponíveis.
      </p>
    </div>
  </section>
</template>
