# EPIC 2 — Feature 1: Localização

## Objetivo
Permitir descoberta automática de localização com fallback manual e autocomplete.

## CARD 2.1 — DTO Coordinates

### Objetivo
Encapsular latitude e longitude.

### Estrutura
```text
Coordinates
├── latitude
└── longitude
```

### Validações
- latitude: `-90` a `90`
- longitude: `-180` a `180`

### Regras
- Immutable / readonly.
- Evitar pares de floats soltos em Actions e Providers.

---

## CARD 2.2 — Geocoding Provider

### Contrato
```php
interface GeocodingProvider
{
    public function search(string $query, int $limit = 5): array;
}
```

### Retorno
```text
LocationData
├── name
├── state?
├── country
├── latitude
└── longitude
```

### Endpoint externo
```text
/direct?q={query}&limit={limit}
```

### Regras
- Nunca retornar JSON cru da OpenWeather.
- Normalizar resultados no provider.

### Referências
- https://openweathermap.org/api/geocoding-api

---

## CARD 2.3 — SearchLocationsAction

### Fluxo
```text
SearchLocationRequest
        ↓
SearchLocationsAction
        ↓
GeocodingProvider
        ↓
OpenWeather
```

### Entrada
- `query`

### Regras
- mínimo 2 caracteres;
- `trim`;
- limite de tamanho;
- `limit = 5`.

### Endpoint
```http
GET /locations/search?q=São Paulo
```

### Critérios de aceite
- Resposta JSON tipada e normalizada.
- Validação devolve erro adequado.
- Nenhum detalhe do provider aparece no payload público.

---

## CARD 2.4 — LocationSearch.vue

### Referência de design
```text
┌─────────────────────────────┐
│ 🔍  Buscar cidade...        │
└─────────────────────────────┘
```

Estado ativo:
```text
┌─────────────────────────────┐
│ São Caetano...              │
├─────────────────────────────┤
│ São Caetano do Sul, SP, BR  │
│ São Caetano, PE, BR         │
└─────────────────────────────┘
```

### Comportamento
- `v-model`;
- debounce ~300 ms;
- mínimo 2 caracteres;
- loading;
- zero-results;
- erro;
- navegação por teclado;
- Escape fecha;
- click outside fecha.

### Composable
- `useLocationSearch.ts`

### Referências
- https://vuejs.org/guide/reusability/composables.html
- https://vuejs.org/guide/essentials/forms.html

---

## CARD 2.5 — Browser Geolocation

### Composable
- `useGeolocation.ts`

### Estados
```text
idle
requesting
available
denied
unavailable
timeout
```

### Fluxo
```text
navigator.geolocation
       ↓
Coordinates
       ↓
Weather page
```

### Regras
- Tratar permissão negada.
- Tratar browser sem suporte.
- Tratar timeout.
- Não bloquear busca manual.

### Referências
- https://developer.mozilla.org/docs/Web/API/Geolocation_API
- https://developer.mozilla.org/docs/Web/API/Permissions_API

---

## CARD 2.6 — Tela universal sem localização

### Referência de design
```text
       📍

Não conseguimos acessar
sua localização

Para mostrar o clima da sua região,
precisamos saber onde você está.

[ Usar minha localização ]

[ Adicionar local manualmente ]
```

### Design
- Mobile-first.
- `min-h-screen`.
- Área atmosférica superior.
- Card de ação inferior.

### Estados
- permissão ainda não solicitada;
- permissão negada;
- browser sem suporte;
- erro temporário.

### Critérios de aceite
- Usuário nunca fica preso sem ação possível.
- Busca manual sempre disponível.
