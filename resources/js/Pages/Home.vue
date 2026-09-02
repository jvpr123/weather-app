<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useWeatherDashboard } from '@/Composables/useWeatherDashboard';
import WeatherLayout from '@/Layouts/WeatherLayout.vue';
import type { LocationData } from '@/Types/location';

const { dashboard, loading, refreshing, error, load, refresh, retry } = useWeatherDashboard();

function selectLocation(location: LocationData): void {
  void load(location);
}
</script>

<template>
  <Head title="Previsão do tempo" />
  <WeatherLayout
    :loading="loading"
    :refreshing="refreshing"
    :dashboard="dashboard"
    :error-message="error"
    @select="selectLocation"
    @retry="retry"
    @refresh="refresh"
  />
</template>
