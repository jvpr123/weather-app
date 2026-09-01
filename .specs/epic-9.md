# EPIC 9 — Polish Final

## Objetivo
Fazer revisão final sem expandir o escopo funcional.

## CARD 9.1 — Empty States

### Garantir estados para
- sem localização;
- sem resultado de busca;
- sem forecast;
- erro de provider.

---

## CARD 9.2 — Microinterações

### Somente depois de tudo funcionar
- fade entre temas;
- transição de tabs;
- hover desktop;
- pressed mobile;
- skeleton pulse.

### Regra
Nada de animações complexas.

---

## CARD 9.3 — Code Review Final

### Checklist
```text
Controllers finos?
Actions pequenas?
Nenhum Http:: dentro de Action?
Nenhuma API key frontend?
Nenhum JSON OpenWeather vazando para Vue?
DTOs tipados?
Vue props tipadas?
Componentes grandes demais?
Duplicação?
Testes cobrindo regras?
README reproduzível?
```

---

## Prioridades finais

### P0 — obrigatório
- Bootstrap.
- Sail/Postgres.
- Architecture/contracts.
- OpenWeather client.
- Geocoding.
- Location search.
- Geolocation fallback.
- Current Weather.
- Forecast.
- Dashboard.
- Theme.
- Responsive layout.
- Outdoor Score.
- Comparison.
- Core tests.
- README.

### P1 — importante
- Redis.
- Swipe.
- Skeletons.
- Provider error mapping.
- CI.
- Accessibility.

### P2 — se sobrar tempo
- Air Quality.
- Charts.
- Map.
- Favorites.
- History.
- Elaborate animations.
- PWA.
