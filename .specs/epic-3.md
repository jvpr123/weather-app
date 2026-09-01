# EPIC 3 — Feature 2: Weather Dashboard

## Objetivo
Entregar o principal fluxo visual e técnico da aplicação.

## CARD 3.1 — CurrentWeatherProvider

### Contrato
```php
interface CurrentWeatherProvider
{
    public function current(Coordinates $coordinates): CurrentWeatherData;
}
```

### DTO
```text
CurrentWeatherData
├── temperature
├── feelsLike
├── minTemperature
├── maxTemperature
├── humidity
├── pressure
├── windSpeed
├── weatherCode
├── condition
├── description
├── icon
├── sunrise
├── sunset
└── timestamp
```

### Endpoint externo
`/data/2.5/weather`

### Parâmetros
- `lat`
- `lon`
- `units=metric`
- `lang=pt_br`

### Referências
- https://openweathermap.org/current

---

## CARD 3.2 — ForecastProvider

### Contrato
```php
interface ForecastProvider
{
    public function forecast(Coordinates $coordinates): ForecastData;
}
```

### DTO
```text
ForecastData
└── periods[]
    ├── datetime
    ├── temperature
    ├── min
    ├── max
    ├── condition
    ├── weatherCode
    ├── probabilityOfPrecipitation
    └── windSpeed
```

### Regra
O Provider representa slots de 3h; não transformar em previsão diária dentro dele.

### Referências
- https://openweathermap.org/forecast5

---

## CARD 3.3 — DailyForecastService

### Objetivo
Normalizar slots de 3h para agregação diária.

### Entrada
- `ForecastData`

### Saída
```text
DailyForecastData[]
├── date
├── minTemperature
├── maxTemperature
├── dominantCondition
└── maxRainProbability
```

### Regras
```text
slots → timezone/local date → dia
```
- min = menor temperatura do dia;
- max = maior temperatura do dia;
- rainProbability = maior probabilidade do dia;
- definir condição dominante de forma determinística.

### Testes obrigatórios
- múltiplos slots no mesmo dia;
- mudança de dia;
- chuva dominante;
- min/max corretos.

---

## CARD 3.4 — Cache da OpenWeather

### TTL sugerido
```text
Geocoding          30 min
Current weather    10 min
Forecast           30 min
```

### Chaves
```text
weather:geo:{hash}
weather:current:{lat}:{lon}
weather:forecast:{lat}:{lon}
```

### Critério de aceite
Duas chamadas idênticas dentro do TTL devem gerar somente uma chamada HTTP externa.

### Referências
- https://laravel.com/docs/13.x/cache

---

## CARD 3.5 — GetWeatherDashboardAction

### Fluxo
```text
GetWeatherDashboardAction
          │
          ├── CurrentWeatherProvider
          ├── ForecastProvider
          └── DailyForecastService
                   ↓
          WeatherDashboardData
```

### DTO final
```text
WeatherDashboardData
├── location
├── current
├── hourly
├── daily
└── theme
```

### Regra
A página Vue recebe apenas contrato interno da aplicação.

---

## CARD 3.6 — WeatherThemeResolver

### Entrada
- condição;
- sunrise;
- sunset;
- timestamp atual.

### Saída
```text
clear-day
clear-night
cloudy-day
cloudy-night
rain-day
rain-night
```

### Regras
```text
timestamp < sunrise → night
timestamp > sunset → night
caso contrário → day
```

Mapeamento:
```text
Clear → clear
Clouds → cloudy
Rain/Drizzle/Thunderstorm → rain
```

Fallback:
- `cloudy-day`
- `cloudy-night`

### Testes
- clear + noon → clear-day
- clear + midnight → clear-night
- rain + noon → rain-day
- cloud + night → cloudy-night

---

## CARD 3.7 — WeatherLayout.vue

### Estrutura visual
```text
┌────────────────────────────┐
│ LocationSearch             │
│                            │
│ CurrentWeatherHero         │
│                            │
├────────────────────────────┤
│ Agora | Previsão           │
├────────────────────────────┤
│ Content                    │
└────────────────────────────┘
```

### Tailwind
Mobile-first:
```text
px-4
py-4
gap-4
rounded-3xl
```

Somente depois:
- `md:`
- `lg:`

### Regra visual
Não trocar árvore inteira de componentes por clima. Trocar:
- CSS variables;
- background;
- ilustração;
- contraste.

---

## CARD 3.8 — CurrentWeatherHero

### Referência
```text
São Paulo, SP

28°

Ensolarado

Máx. 31°     Mín. 18°
```

### Hierarquia
- temperatura dominante;
- cidade;
- condição;
- máxima/mínima.

### Tailwind sugerido
- `text-7xl`
- `font-light`
- `tracking-tight`

---

## CARD 3.9 — WeatherMetrics

### Conteúdo
```text
Sensação    Umidade
   30°        55%

Vento       Pressão
12 km/h     1015 hPa
```

### Layout
- mobile: `grid-cols-2`
- desktop: `md:grid-cols-4`

---

## CARD 3.10 — HourlyForecast

### Referência
```text
← scroll →
10h  13h  16h  19h  22h
☀️   ☀️   🌤️   🌧️   🌙
24°  27°  29°  24°  21°
```

### UX
- horizontal scroll;
- `snap-x`;
- touch-friendly;
- scrollbar discreta/oculta.

### Regra de escopo
Sem gráfico no MVP.

---

## CARD 3.11 — DailyForecast

### Referência
```text
Hoje       🌤  18° ━━━ 29°
Terça      🌧  17° ━━━ 24°
Quarta     ☁  16° ━━━ 22°
```

### Regra
Vue somente apresenta dados já agregados no backend.

---

## CARD 3.12 — Tabs + swipe

### Tabs do MVP
- Agora
- Previsão

### Componente
- `WeatherTabs.vue`

### Tipo
```ts
type WeatherTab = 'current' | 'forecast'
```

### Gestos
- `touchstart`
- `touchend`
- threshold mínimo de swipe

### Regra de escopo
Não instalar biblioteca de gesture para esse fluxo.
