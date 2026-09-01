# EPIC 0 — Fundação do Projeto

## Objetivo
Criar a base técnica do WeatherLens com Laravel, Inertia, Vue, TypeScript, Tailwind, Sail, PostgreSQL, Redis e Pest.

## CARD 0.1 — Bootstrap da aplicação

### Escopo
- Laravel 13.
- Vue 3.
- TypeScript.
- Inertia.
- Tailwind.
- Vite.
- Pest.
- Sem autenticação no MVP.

### Subtarefas
- Criar o projeto Laravel.
- Instalar/configurar Inertia.
- Configurar Vue 3.
- Habilitar TypeScript.
- Configurar alias `@/`.
- Configurar Tailwind.
- Confirmar build com Vite.
- Criar `Home.vue`.
- Remover componentes/demo desnecessários.
- Configurar título/meta base.
- Criar `.env.example`.

### Estrutura inicial
```text
app/
resources/
    js/
        Components/
        Composables/
        Layouts/
        Pages/
        Types/
routes/
tests/
```

### Critérios de aceite
```bash
./vendor/bin/sail up -d
npm run dev
```
- A rota `/` deve renderizar uma página Vue via Inertia.
- O projeto deve compilar sem erros TypeScript.

### Referências
- https://laravel.com/docs/13.x/structure
- https://laravel.com/starter-kits
- https://vuejs.org/guide/typescript/overview
- https://vuejs.org/api/sfc-script-setup.html

---

## CARD 0.2 — Ambiente Sail

### Objetivo
Garantir um ambiente local reproduzível usando Docker/Sail.

### Serviços
```text
Laravel
PostgreSQL
Redis
```

### Subtarefas
- Habilitar Sail.
- PostgreSQL como banco padrão.
- Redis como cache.
- Ajustar `.env`.
- Testar conexão PostgreSQL.
- Testar conexão Redis.
- Rodar migrations.
- Documentar comandos essenciais.

### Configuração esperada
```env
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=weatherlens
DB_USERNAME=sail
DB_PASSWORD=password

CACHE_STORE=redis
REDIS_HOST=redis
```

### Critérios de aceite
```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan tinker
```
- PostgreSQL acessível pelo container da aplicação.
- Redis acessível e funcional como cache store.

### Referências
- https://laravel.com/docs/13.x/sail
- https://laravel.com/docs/13.x/database
- https://laravel.com/docs/13.x/redis
