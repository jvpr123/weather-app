# WeatherLens

<p align="center">
  <img src="public/images/weatherlens-logo.png" alt="WeatherLens" width="560">
</p>

## Overview

WeatherLens is a responsive weather dashboard for finding a location, viewing its current conditions and forecast, and comparing two cities for outdoor activities. It is built as a Laravel 13 modular monolith: Laravel owns the application and integration boundaries, while Inertia connects the server to a Vue 3 interface without requiring a separate frontend application.

The interface is currently presented in Brazilian Portuguese, and OpenWeather responses are requested in metric units and `pt_br`.

## Features

- Search locations by city name with debouncing, keyboard navigation, and explicit loading, empty, and error states.
- Use browser geolocation with manual search as a permanent fallback.
- Restore the last selected location from browser storage and, when location permission is already granted, offer to switch if the user has moved.
- Resolve coordinates back to a human-readable location.
- Display current temperature, condition, humidity, pressure, wind, and apparent temperature.
- Show scrollable three-hour forecast periods and an aggregated daily forecast.
- Adapt the visual theme for clear, cloudy, and rainy conditions during the day or night.
- Compare two cities and recommend the one with the better application-specific Outdoor Score.
- Cache OpenWeather requests through a configurable cache contract backed by Redis.
- Normalize provider, validation, and network failures before they reach the frontend.

## Tech Stack

### Backend

- PHP 8.5 and Laravel 13
- PostgreSQL 18
- Redis
- Laravel HTTP Client
- Pest 4 and PHPUnit 12

### Frontend

- Vue 3 with Composition API and `<script setup lang="ts">`
- TypeScript
- Inertia 2
- Tailwind CSS 4
- Vite 8
- Lucide Vue icons

### Tooling

- Laravel Sail for local services
- ESLint and Prettier
- Laravel Pint
- GitHub Actions

## Architecture

WeatherLens follows a modular-monolith structure with thin delivery layers and explicit application and infrastructure boundaries.

```text
Browser
  |
  v
Vue / Inertia pages and composables
  |
  v
Controller -> Form Request
  |
  v
Action
  |
  v
Provider Contract
  |
  v
OpenWeather Provider -> OpenWeather Client -> OpenWeather API
          |
          v
 Weather Cache Contract -> Redis implementation
```

The main responsibilities are:

- **Controllers** receive validated input and delegate the use case.
- **Form Requests** own HTTP input validation and normalization.
- **Actions** orchestrate use cases such as location search, dashboard assembly, and city comparison.
- **Contracts** keep Actions independent from OpenWeather and Redis implementations.
- **Providers** translate external payloads into stable internal DTOs.
- **Domain services** contain reusable calculations for daily forecasts, themes, and the Outdoor Score.
- **Service providers** select and bind configured implementations as container singletons.

External OpenWeather payloads are restricted to `app/Integrations/OpenWeather`. Controllers, Actions, and Vue receive normalized DTO-backed structures instead of provider-specific JSON.

## Local Development

### Requirements

- OrbStack, Docker Desktop, or another Docker engine with Compose support
- PHP 8.3+ and Composer for the initial dependency installation
- Node.js 22+
- npm 11+
- An [OpenWeather API key](https://openweathermap.org/api)

### First-time setup

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
npm install
npm run dev
```

Set `OPENWEATHER_API_KEY` in `.env` before requesting weather data. By default, the application is available at `http://localhost`, PostgreSQL is available to the application at `pgsql:5432`, and Redis at `redis:6379`.

If port 80 is already in use, set a different application port and keep its URL consistent:

```dotenv
APP_PORT=8000
APP_URL=http://localhost:8000
```

### Daily development

```bash
./vendor/bin/sail up -d
npm run dev
```

Useful commands:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan tinker
./vendor/bin/sail artisan test
./vendor/bin/sail down
```

## Environment Variables

The application uses Laravel's standard environment variables plus the following integration settings:

| Variable | Default | Purpose |
| --- | --- | --- |
| `WEATHER_PROVIDER` | `openweather` | Selects the provider configured in `config/weather.php`. |
| `OPENWEATHER_API_KEY` | none | Authenticates server-side OpenWeather requests. Never expose it through a `VITE_` variable. |
| `OPENWEATHER_BASE_URL` | `https://api.openweathermap.org` | Base URL used by the OpenWeather client. |
| `OPENWEATHER_TIMEOUT` | `10` | Total request timeout in seconds. |
| `OPENWEATHER_CONNECT_TIMEOUT` | `5` | Connection timeout in seconds. |
| `OPENWEATHER_RETRY_TIMES` | `3` | Maximum attempts for transient failures. |
| `OPENWEATHER_RETRY_DELAY_MS` | `200` | Delay between retry attempts in milliseconds. |
| `WEATHER_CACHE_STORE` | `redis` | Laravel cache store used by the weather cache implementation. |
| `WEATHER_GEOCODING_CACHE_TTL` | `1800` | Search and reverse-geocoding TTL in seconds. |
| `WEATHER_CURRENT_CACHE_TTL` | `600` | Current-weather TTL in seconds. |
| `WEATHER_FORECAST_CACHE_TTL` | `1800` | Forecast TTL in seconds. |

The OpenWeather key remains exclusively on the server. Vue calls same-origin Laravel endpoints and never communicates with OpenWeather directly.

## OpenWeather Integration

`OpenWeatherClient` centralizes authentication, the base URL, timeouts, retries, and HTTP error handling. It retries connection failures, rate limits, and server errors, then maps failures to sanitized `WeatherProviderException` instances.

`OpenWeatherProvider` implements the geocoding, current-weather, and forecast contracts. It uses:

- Direct Geocoding API for manual location search.
- Reverse Geocoding API for browser coordinates.
- Current Weather Data for present conditions.
- 5 Day / 3 Hour Forecast for hourly periods and daily aggregation.

The provider validates every important field before constructing internal DTOs. Invalid payloads are rejected rather than partially propagated through the application.

## Caching Strategy

Redis is accessed through `WeatherCache`, with `RedisWeatherCache` injected as its configured implementation. Caching at the provider boundary avoids duplicate OpenWeather calls while keeping cache details out of Controllers and Actions.

Default TTLs balance freshness with external API usage:

| Data | TTL | Key pattern |
| --- | ---: | --- |
| Geocoding | 30 minutes | `weather:geo:{sha256}` |
| Current weather | 10 minutes | `weather:current:{lat}:{lon}` |
| Forecast | 30 minutes | `weather:forecast:{lat}:{lon}` |

Coordinates are normalized to six decimal places before forming a cache key. Search and reverse-geocoding inputs are hashed so free-form queries do not become raw cache-key fragments. Cached provider payloads are validated and mapped again when read.

## Outdoor Score

The Outdoor Score is an internal comparison heuristic from **0.0 to 10.0**. It is intended to make the city comparison easier to understand; it is not a scientific, health, or safety index.

The final value is a weighted sum of five component scores:

```text
score = temperature_score x 0.30
      + rain_score        x 0.30
      + humidity_score    x 0.15
      + wind_score        x 0.15
      + condition_score   x 0.10
```

Each component is constrained to the `0–10` range:

- **Temperature (30%)**: temperatures from 18°C through 26°C score 10. Outside that range, 1.25 points are removed for every degree of distance from the closest boundary.
- **Rain probability (30%)**: `rain_score = (1 - probability) x 10`, where probability is constrained from `0` to `1`. No expected rain scores 10; 100% probability scores 0.
- **Humidity (15%)**: values from 40% through 70% score 10. Outside that range, one point is removed for every three percentage points of distance from the closest boundary.
- **Wind (15%)**: speeds up to 5 m/s score 10. Above 5 m/s, 1.5 points are removed for every additional m/s.
- **Condition (10%)**: Clear scores 10, Clouds 8, Drizzle 5, Rain 3, and Thunderstorm 1. An unknown condition scores 4.

The result is clamped once more to `0–10` and rounded to one decimal place.

### Example

For 22°C, 20% rain probability, 55% humidity, 7 m/s wind, and Clear conditions:

```text
temperature = 10.0 x 0.30 = 3.00
rain        =  8.0 x 0.30 = 2.40
humidity    = 10.0 x 0.15 = 1.50
wind        =  7.0 x 0.15 = 1.05
condition   = 10.0 x 0.10 = 1.00
                              ----
Outdoor Score                 9.0 (8.95 rounded)
```

The rain component uses the next available three-hour forecast period. If no forecast period exists, the comparison falls back to zero rain probability.

## Testing

The test suite contains architecture, unit, provider-integration, and feature tests:

- Architecture tests protect dependency boundaries.
- Unit tests cover coordinates, forecast aggregation, theme resolution, actions, and every Outdoor Score rule.
- Provider tests use `Http::fake()` and prevent stray requests, covering successful responses, invalid payloads, authentication failures, rate limits, server failures, network errors, and timeouts.
- Feature tests cover validation, routes, Inertia components, normalized responses, and service-container bindings.

No automated test calls the real OpenWeather API. Local tests use in-memory SQLite and array-backed framework services as configured in `phpunit.xml`; CI runs the suite against its PostgreSQL service.

Run the complete local quality gate with:

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
npm run lint
npm run format:check
npm run type-check
npm run build
```

The GitHub Actions workflow executes the same checks for pull requests targeting `main`.

## Technical Decisions

### Laravel and Inertia instead of a REST SPA

The product is a cohesive monolith with one deployment boundary and no requirement for an independently deployed frontend or public API. Inertia keeps routing, validation, configuration, and authentication concerns in Laravel while allowing the interface to use Vue components. It also avoids maintaining a second API contract solely to connect two parts of the same application.

This choice trades independent frontend deployment and first-class public API reuse for a smaller operational surface and faster end-to-end development. See the [Inertia documentation](https://inertiajs.com/) and its [protocol description](https://inertiajs.com/the-protocol).

### Actions for use cases

Actions give application operations explicit names and keep Controllers focused on HTTP concerns. They make orchestration independently testable and prevent provider, cache, or framework details from becoming controller logic. The cost is an additional class for each meaningful use case.

### Interfaces around OpenWeather

Actions depend on `GeocodingProvider`, `CurrentWeatherProvider`, and `ForecastProvider`, not `OpenWeatherProvider`. Laravel's service container selects the configured driver. This makes tests deterministic and allows another provider to be introduced without rewriting use cases, at the cost of maintaining internal contracts and bindings.

### DTOs instead of provider responses

DTOs define stable application-owned structures and prevent OpenWeather field names or payload changes from leaking into business logic and Vue. This requires explicit mapping code, but failures remain localized at the integration boundary.

### Redis cache behind a contract

Weather data is read frequently but changes less often than a user can request it. Redis lowers latency and external API usage. Keeping it behind `WeatherCache` preserves testability and keeps Actions independent from storage mechanics. Cached data can be stale for up to its configured TTL, which is an intentional freshness-versus-cost trade-off.

## Trade-offs

- Weather and comparison data are requested on demand and are not persisted as historical records.
- The last selected location is stored only in the browser's `localStorage`, not by IP or in the backend. Clearing site data resets this preference.
- Forecasts use OpenWeather's five-day, three-hour product; the application does not provide long-range forecasting.
- The Outdoor Score uses fixed, transparent rules and is not personalized for activity, health, age, or user preference.
- City comparison performs the dashboard use case for both cities. Cache hits reduce duplicate external work, but uncached comparisons require multiple provider calls.
- Authentication, favorites, search history, saved preferences, and background refresh are outside the current scope.
- Air quality, maps, charts, severe-weather alerts, offline support, and PWA installation were deferred until the core P0 and P1 experience is stable.
- Inertia intentionally couples the Vue interface to the Laravel deployment; a future native client or public API would require a dedicated API boundary.

## Future Improvements

- Add activity-specific and user-configurable Outdoor Score profiles.
- Persist favorite locations and recent searches for authenticated users.
- Add air-quality data, severe-weather alerts, charts, and map layers.
- Introduce background refresh and stale-while-revalidate behavior for popular locations.
- Add end-to-end browser tests for geolocation permissions, keyboard navigation, carousel controls, and responsive layouts.
- Evaluate a dedicated API only if an independent frontend, mobile client, or third-party consumer becomes a concrete requirement.
