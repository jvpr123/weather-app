# EPIC 8 — README e Documentação Técnica

## Objetivo
Documentar decisões para que o avaliador consiga entender e reproduzir o projeto rapidamente.

## CARD 8.1 — README técnico

### Estrutura
```text
# WeatherLens

## Overview
## Features
## Tech Stack
## Architecture
## Local Development
## Environment Variables
## OpenWeather Integration
## Caching Strategy
## Outdoor Score
## Testing
## Technical Decisions
## Trade-offs
## Future Improvements
```

### Diagrama de arquitetura
```text
Vue / Inertia
      ↓
Controller
      ↓
Action
      ↓
Contract
      ↓
Provider
      ↓
OpenWeather
```

### Decisão Inertia
Documentar que Inertia foi escolhido porque a aplicação é um monólito coeso Laravel + Vue e não requer frontend independentemente implantado.

### Referências
- https://inertiajs.com/
- https://inertiajs.com/the-protocol

---

## CARD 8.2 — Decisões arquiteturais

### Registrar
1. Why Laravel + Inertia instead of REST SPA.
2. Why Actions.
3. Why interfaces around OpenWeather.
4. Why DTOs instead of provider responses.
5. Why Redis cache.

### Trade-offs
Documentar explicitamente o que foi deixado de fora por escopo e prazo.
