<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';
import { useLocationSearch } from '@/Composables/useLocationSearch';
import type { LocationData } from '@/Types/location';
import { Search } from '@lucide/vue';

const props = withDefaults(defineProps<{
  modelValue: string;
  placement?: 'top' | 'bottom';
}>(), {
  placement: 'bottom',
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
  select: [location: LocationData];
}>();

const root = ref<HTMLElement | null>(null);
const focused = ref(false);
const open = ref(false);
const activeIndex = ref(-1);
const ignoreNextSearch = ref(false);
const listboxId = `location-results-${useId()}`;

const {
  results,
  loading,
  error,
  isEmpty,
  search,
  clear,
} = useLocationSearch();

const activeOptionId = computed(() => activeIndex.value >= 0
  ? `${listboxId}-option-${activeIndex.value}`
  : undefined);

const showPanel = computed(() => open.value && props.modelValue.trim().length >= 2);
const showResultsPanel = computed(() => showPanel.value
  && !loading.value
  && (error.value !== null || isEmpty.value || results.value.length > 0));

watch(
  () => props.modelValue,
  (value) => {
    activeIndex.value = -1;

    if (ignoreNextSearch.value) {
      ignoreNextSearch.value = false;
      clear();
      return;
    }

    search(value);

    if (focused.value && value.trim().length >= 2) {
      open.value = true;
    }
  },
  { immediate: true },
);

function updateValue(event: Event): void {
  emit('update:modelValue', (event.target as HTMLInputElement).value);
  open.value = true;
}

function formatLocation(location: LocationData): string {
  return [location.name, location.state, location.country].filter(Boolean).join(', ');
}

function selectLocation(location: LocationData): void {
  ignoreNextSearch.value = true;
  emit('update:modelValue', formatLocation(location));
  emit('select', location);
  open.value = false;
  activeIndex.value = -1;
  clear();
}

function moveActive(direction: 1 | -1): void {
  if (!showPanel.value) {
    open.value = true;
  }

  if (results.value.length === 0) {
    return;
  }

  activeIndex.value = (activeIndex.value + direction + results.value.length) % results.value.length;
}

function chooseActive(): void {
  const location = results.value[activeIndex.value];

  if (location) {
    selectLocation(location);
  }
}

function close(): void {
  open.value = false;
  activeIndex.value = -1;
}

function handleOutsidePointer(event: PointerEvent): void {
  if (root.value && !root.value.contains(event.target as Node)) {
    close();
  }
}

onMounted(() => document.addEventListener('pointerdown', handleOutsidePointer));
onBeforeUnmount(() => document.removeEventListener('pointerdown', handleOutsidePointer));
</script>

<template>
  <div
    ref="root"
    class="relative w-full"
  >
    <label
      class="sr-only"
      :for="`${listboxId}-input`"
    >
      Buscar cidade
    </label>

    <div class="relative">
      <Search class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-slate-400" />

      <input
        :id="`${listboxId}-input`"
        :value="modelValue"
        type="search"
        autocomplete="off"
        placeholder="Buscar cidade..."
        role="combobox"
        aria-autocomplete="list"
        :aria-controls="listboxId"
        :aria-expanded="showResultsPanel"
        :aria-activedescendant="activeOptionId"
        class="w-full rounded-2xl border border-white/15 bg-white/10 py-3.5 pl-12 text-base text-white outline-none placeholder:text-slate-400 focus:border-cyan-300/70 focus:ring-4 focus:ring-cyan-300/10"
        :class="$slots.trailing ? 'pr-28' : 'pr-12'"
        @input="updateValue"
        @focus="focused = true; open = modelValue.trim().length >= 2"
        @blur="focused = false"
        @keydown.down.prevent="moveActive(1)"
        @keydown.up.prevent="moveActive(-1)"
        @keydown.enter.prevent="chooseActive"
        @keydown.esc="close"
      >

      <span
        v-if="loading"
        aria-label="Buscando cidades"
        class="absolute top-1/2 size-5 -translate-y-1/2 animate-spin rounded-full border-2 border-white/25 border-t-cyan-200"
        :class="$slots.trailing ? 'right-16' : 'right-4'"
      />

      <div
        v-if="$slots.trailing"
        class="absolute inset-y-px right-px flex items-stretch"
      >
        <slot name="trailing" />
      </div>
    </div>

    <div
      v-if="showResultsPanel"
      class="absolute z-20 w-full overflow-hidden rounded-2xl border border-white/15 bg-slate-950/95 shadow-2xl shadow-black/30 backdrop-blur-xl"
      :class="placement === 'top' ? 'bottom-full mb-2' : 'mt-2'"
    >
      <p
        v-if="error"
        role="alert"
        class="px-4 py-4 text-sm text-rose-200"
      >
        {{ error }}
      </p>

      <p
        v-else-if="isEmpty"
        role="status"
        class="px-4 py-4 text-sm text-slate-300"
      >
        Nenhuma cidade encontrada.
      </p>

      <ul
        v-else
        :id="listboxId"
        role="listbox"
        aria-label="Resultados da busca"
        class="max-h-72 overflow-y-auto p-1.5"
      >
        <li
          v-for="(location, index) in results"
          :id="`${listboxId}-option-${index}`"
          :key="`${location.latitude}:${location.longitude}`"
          role="option"
          :aria-selected="activeIndex === index"
          class="cursor-pointer rounded-xl px-3 py-3 text-left transition"
          :class="activeIndex === index ? 'bg-cyan-300/15 text-white' : 'text-slate-200 hover:bg-white/10'"
          @mouseenter="activeIndex = index"
          @mousedown.prevent
          @click="selectLocation(location)"
        >
          <span class="block font-medium">{{ location.name }}</span>
          <span class="mt-0.5 block text-sm text-slate-400">
            {{ [location.state, location.country].filter(Boolean).join(', ') }}
          </span>
        </li>
      </ul>
    </div>
  </div>
</template>
