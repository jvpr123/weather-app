# EPIC 7 — Qualidade e CI

## Objetivo
Garantir consistência de código e validação automatizada.

## CARD 7.1 — Pint + Frontend Lint

### Backend
- Laravel Pint.

### Frontend
- ESLint.
- Prettier.
- `vue-tsc`.

### Scripts desejados
```text
composer lint
npm run lint
npm run type-check
```

### Se o tempo apertar
Priorizar:
- Pint;
- ESLint;
- vue-tsc.

### Referências
- https://laravel.com/docs/13.x/pint
- https://typescript-eslint.io/

---

## CARD 7.2 — CI

### Pipeline sugerida
```text
checkout
 ↓
composer install
 ↓
npm ci
 ↓
PHP tests
 ↓
Pint --test
 ↓
npm lint
 ↓
type-check
 ↓
build
```

### Banco
PostgreSQL como service container da pipeline.

### Escopo
Não é necessário deploy automático para o teste.
