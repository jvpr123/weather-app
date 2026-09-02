import { computed, onMounted, ref } from 'vue';
import { useGeolocation } from '@/Composables/useGeolocation';
import { useReverseGeocoding } from '@/Composables/useReverseGeocoding';
import type { Coordinates, LocationData } from '@/Types/location';
import {
  distanceBetweenLocations,
  hasGrantedGeolocationPermission,
  readPreferredLocation,
  rememberPreferredLocation,
} from '@/Utils/locationPreference';

const LOCATION_CHANGE_THRESHOLD_KILOMETERS = 5;

export function useLocationPreference(onSelect: (location: LocationData) => void) {
  const selectedLocation = ref<LocationData | null>(null);
  const suggestedLocation = ref<LocationData | null>(null);
  const { status, coordinates, request, reset: resetGeolocation } = useGeolocation();
  const {
    loading: resolvingLocation,
    resolve: resolveLocation,
    clear: clearLocationResolution,
  } = useReverseGeocoding();

  const locationRequestPending = computed(
    () => status.value === 'requesting' || resolvingLocation.value,
  );
  const locationButtonLabel = computed(() =>
    resolvingLocation.value
      ? 'Identificando cidade...'
      : status.value === 'requesting'
        ? 'Buscando localização...'
        : 'Usar localização atual',
  );

  function useLocation(location: LocationData): void {
    selectedLocation.value = location;
    suggestedLocation.value = null;
    rememberPreferredLocation(location);
    onSelect(location);
  }

  function selectLocation(location: LocationData): void {
    clearLocationResolution();
    resetGeolocation();
    useLocation(location);
  }

  async function locationFromCoordinates(foundCoordinates: Coordinates): Promise<LocationData> {
    return (
      (await resolveLocation(foundCoordinates)) ?? {
        name: 'Localização atual',
        state: null,
        country: '--',
        ...foundCoordinates,
      }
    );
  }

  async function useCurrentLocation(): Promise<void> {
    clearLocationResolution();

    const foundCoordinates = await request();

    if (!foundCoordinates) {
      return;
    }

    useLocation(await locationFromCoordinates(foundCoordinates));
  }

  function keepSelectedLocation(): void {
    suggestedLocation.value = null;
  }

  function useSuggestedLocation(): void {
    if (suggestedLocation.value) {
      useLocation(suggestedLocation.value);
    }
  }

  async function restoreLocation(): Promise<void> {
    const preferredLocation = readPreferredLocation();

    if (preferredLocation) {
      useLocation(preferredLocation);
    }

    if (!(await hasGrantedGeolocationPermission())) {
      return;
    }

    const foundCoordinates = await request();

    if (!foundCoordinates) {
      return;
    }

    const currentLocation = await locationFromCoordinates(foundCoordinates);
    const activeLocation = selectedLocation.value;

    if (!activeLocation) {
      useLocation(currentLocation);
      return;
    }

    if (
      distanceBetweenLocations(activeLocation, currentLocation) <=
      LOCATION_CHANGE_THRESHOLD_KILOMETERS
    ) {
      selectedLocation.value = currentLocation;
      rememberPreferredLocation(currentLocation);
      return;
    }

    suggestedLocation.value = currentLocation;
  }

  onMounted(() => void restoreLocation());

  return {
    status,
    coordinates,
    resolvingLocation,
    selectedLocation,
    suggestedLocation,
    locationRequestPending,
    locationButtonLabel,
    selectLocation,
    useCurrentLocation,
    keepSelectedLocation,
    useSuggestedLocation,
  };
}
