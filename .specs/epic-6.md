# EPIC 6 — Testes

## Objetivo
Cobrir as regras e integrações de maior valor técnico.

## CARD 6.1 — Unit Tests

### Prioridade
- `Coordinates`
- `DailyForecastService`
- `WeatherThemeResolver`
- `OutdoorScoreCalculator`

### Regra
Priorizar regras puras e casos de borda.

---

## CARD 6.2 — Provider Tests

### Ferramentas
```php
Http::fake();
Http::preventStrayRequests();
Http::assertSent();
```

### Casos
```text
current weather success
forecast success
geocoding success
401
429
500
timeout
invalid payload
```

### Critério de aceite
Nenhum teste do provider deve realizar request externo real.

### Referências
- https://laravel.com/docs/13.x/http-client#testing

---

## CARD 6.3 — Feature Tests

### Rotas principais
```text
GET /
GET /locations/search
GET /weather
GET /weather/compare
```

### Validar
- status;
- validation;
- Inertia component;
- props;
- error handling.

### Referências
- https://laravel.com/docs/13.x/http-tests
- https://inertiajs.com/testing
