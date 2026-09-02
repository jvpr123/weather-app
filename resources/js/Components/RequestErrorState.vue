<script setup lang="ts">
import { RefreshCw } from '@lucide/vue';

withDefaults(defineProps<{
  message: string;
  compact?: boolean;
}>(), {
  compact: false,
});

defineEmits<{
  retry: [];
}>();
</script>

<template>
  <section
    role="alert"
    class="text-center"
    :class="compact ? 'rounded-2xl border border-rose-200/20 bg-rose-200/10 px-3 py-4 sm:px-4' : 'flex flex-1 items-center justify-center py-8'"
  >
    <div :class="compact ? '' : 'w-full max-w-lg rounded-2xl border border-white/10 bg-white/5 px-4 py-8 backdrop-blur sm:rounded-3xl sm:px-6 sm:py-10'">
      <p class="font-semibold text-rose-50">
        {{ message }}
      </p>
      <button
        type="button"
        class="mt-5 inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100"
        @click="$emit('retry')"
      >
        <RefreshCw
          aria-hidden="true"
          class="size-4"
        />
        Tentar novamente
      </button>
    </div>
  </section>
</template>
