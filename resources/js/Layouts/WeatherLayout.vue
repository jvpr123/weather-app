<script setup lang="ts">
import { ArrowLeftRight, LoaderCircle, MapPin } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import LocationSearch from '@/Components/Location/LocationSearch.vue';
import NoLocationState from '@/Components/Location/NoLocationState.vue';
import RequestErrorState from '@/Components/RequestErrorState.vue';
import CurrentWeatherHero from '@/Components/Weather/CurrentWeatherHero.vue';
import DailyForecast from '@/Components/Weather/DailyForecast.vue';
import ForecastSkeleton from '@/Components/Weather/ForecastSkeleton.vue';
import HourlyForecast from '@/Components/Weather/HourlyForecast.vue';
import WeatherMetrics from '@/Components/Weather/WeatherMetrics.vue';
import WeatherHeroSkeleton from '@/Components/Weather/WeatherHeroSkeleton.vue';
import WeatherTabs from '@/Components/Weather/WeatherTabs.vue';
import type { WeatherTab } from '@/Components/Weather/WeatherTabs.vue';
import { useGeolocation } from '@/Composables/useGeolocation';
import { useReverseGeocoding } from '@/Composables/useReverseGeocoding';
import type { LocationData } from '@/Types/location';
import type { WeatherDashboardData } from '@/Types/weather';

const props = defineProps<{
  dashboard: WeatherDashboardData | null;
  loading: boolean;
  errorMessage?: string | null;
}>();

const emit = defineEmits<{
  select: [location: LocationData];
  retry: [];
}>();

const query = ref('');
const activeTab = ref<WeatherTab>('current');
const {
  status,
  coordinates,
  request,
  reset: resetGeolocation,
} = useGeolocation();
const {
  loading: resolvingLocation,
  resolve: resolveLocation,
  clear: clearLocationResolution,
} = useReverseGeocoding();

const theme = computed(() => props.dashboard?.theme ?? 'cloudy-night');
const locationRequestPending = computed(() => status.value === 'requesting' || resolvingLocation.value);
const locationButtonLabel = computed(() => resolvingLocation.value
  ? 'Identificando cidade...'
  : status.value === 'requesting'
    ? 'Buscando localização...'
    : 'Usar localização atual');

function selectLocation(location: LocationData): void {
  clearLocationResolution();
  resetGeolocation();
  emit('select', location);
}

async function useCurrentLocation(): Promise<void> {
  clearLocationResolution();

  const foundCoordinates = await request();

  if (!foundCoordinates) {
    return;
  }

  const location = await resolveLocation(foundCoordinates) ?? {
    name: 'Localização atual',
    state: null,
    country: '--',
    ...foundCoordinates,
  };

  emit('select', location);
}
</script>

<template>
  <main
    class="weather-layout relative isolate min-h-screen overflow-x-hidden px-4 py-4 text-[var(--weather-text)] transition-colors duration-500"
    :class="theme"
  >
    <div
      aria-hidden="true"
      class="absolute inset-0 -z-20 bg-[var(--weather-background)]"
    />
    <div
      aria-hidden="true"
      class="absolute top-0 left-1/2 -z-10 h-80 w-[36rem] -translate-x-1/2 rounded-full bg-[var(--weather-glow)] blur-3xl"
    />

    <div class="mx-auto flex min-h-[calc(100vh-2rem)] w-full max-w-4xl flex-col">
      <header class="relative z-20 flex flex-col gap-4 py-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-semibold tracking-[0.22em] uppercase opacity-80">
          WeatherLens
        </p>
        <div class="flex w-full min-w-0 items-center gap-2 sm:w-auto">
          <div class="min-w-0 flex-1 sm:w-80 sm:flex-none lg:w-96">
            <LocationSearch
              v-model="query"
              @select="selectLocation"
            >
              <template #trailing>
                <div class="group/location relative flex h-full items-stretch">
                  <button
                    type="button"
                    :disabled="locationRequestPending"
                    class="inline-flex w-14 items-center justify-center rounded-r-[15px] border-l border-white/15 text-slate-300 transition hover:bg-white/10 hover:text-white focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-cyan-100 disabled:cursor-wait disabled:opacity-60"
                    :aria-label="locationButtonLabel"
                    aria-describedby="current-location-tooltip"
                    @click="useCurrentLocation"
                  >
                    <LoaderCircle
                      v-if="locationRequestPending"
                      aria-hidden="true"
                      class="size-5 animate-spin motion-reduce:animate-none"
                      :stroke-width="2"
                    />
                    <MapPin
                      v-else
                      aria-hidden="true"
                      class="size-5"
                    />
                  </button>
                  <span
                    id="current-location-tooltip"
                    role="tooltip"
                    class="pointer-events-none absolute top-full right-0 z-30 mt-2 w-max max-w-56 translate-y-1 rounded-lg border border-white/10 bg-slate-950/95 px-3 py-2 text-xs font-medium text-slate-100 opacity-0 shadow-xl backdrop-blur transition group-hover/location:translate-y-0 group-hover/location:opacity-100 group-focus-within/location:translate-y-0 group-focus-within/location:opacity-100"
                  >
                    {{ locationButtonLabel }}
                  </span>
                </div>
              </template>
            </LocationSearch>
          </div>
          <Link
            href="/weather/compare"
            class="inline-flex h-[54px] shrink-0 items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 font-semibold backdrop-blur transition hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-100 sm:px-5"
          >
            <ArrowLeftRight
              aria-hidden="true"
              class="size-5 shrink-0"
            />
            <span class="hidden whitespace-nowrap md:inline">Comparar</span>
          </Link>
        </div>
      </header>

      <section
        v-if="loading"
        class="flex-1"
        aria-live="polite"
        aria-busy="true"
      >
        <p class="sr-only">
          Carregando previsão do tempo
        </p>
        <WeatherHeroSkeleton />
        <ForecastSkeleton />
      </section>

      <template v-else-if="dashboard">
        <CurrentWeatherHero
          :location="dashboard.location"
          :current="dashboard.current"
        />

        <WeatherTabs v-model="activeTab">
          <section
            v-if="activeTab === 'current'"
            id="weather-panel-current"
            role="tabpanel"
            aria-labelledby="weather-tab-current"
            class="py-4"
          >
            <WeatherMetrics :current="dashboard.current" />
          </section>

          <section
            v-else
            id="weather-panel-forecast"
            role="tabpanel"
            aria-labelledby="weather-tab-forecast"
            class="grid gap-7 py-4"
          >
            <HourlyForecast
              :periods="dashboard.hourly"
              :timezone-offset="dashboard.timezoneOffset"
            />
            <DailyForecast :days="dashboard.daily" />
          </section>
        </WeatherTabs>
      </template>

      <RequestErrorState
        v-else-if="errorMessage"
        :message="errorMessage"
        @retry="emit('retry')"
      />

      <NoLocationState
        v-else
        :status="status"
        :coordinates="coordinates"
        :resolving-location="resolvingLocation"
      />
    </div>
  </main>
</template>

<style scoped>
.clear-day {
  --weather-background: linear-gradient(155deg, #0e7490 0%, #0369a1 42%, #164e63 100%);
  --weather-glow: rgb(254 240 138 / 28%);
  --weather-text: #f8fafc;
}

.clear-night {
  --weather-background: linear-gradient(155deg, #020617 0%, #172554 52%, #0f172a 100%);
  --weather-glow: rgb(129 140 248 / 20%);
  --weather-text: #f8fafc;
}

.cloudy-day {
  --weather-background: linear-gradient(155deg, #475569 0%, #334155 50%, #1e3a4a 100%);
  --weather-glow: rgb(226 232 240 / 18%);
  --weather-text: #f8fafc;
}

.cloudy-night {
  --weather-background: linear-gradient(155deg, #0f172a 0%, #1e293b 52%, #111827 100%);
  --weather-glow: rgb(148 163 184 / 16%);
  --weather-text: #f8fafc;
}

.rain-day {
  --weather-background: linear-gradient(155deg, #155e75 0%, #334155 48%, #1e293b 100%);
  --weather-glow: rgb(103 232 249 / 18%);
  --weather-text: #f8fafc;
}

.rain-night {
  --weather-background: linear-gradient(155deg, #082f49 0%, #0f172a 52%, #020617 100%);
  --weather-glow: rgb(56 189 248 / 14%);
  --weather-text: #f8fafc;
}
</style>
