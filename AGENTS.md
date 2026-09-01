# AGENTS.md — WeatherLens

Este documento define as regras de arquitetura, desenvolvimento, qualidade e GitOps do projeto. Qualquer agente de IA ou colaborador deve seguir estas instruções antes de modificar o código.

## 1. Stack oficial

### Backend
- PHP 8.x compatível com Laravel 13.
- Laravel 13.
- PostgreSQL.
- Redis.
- Pest para testes.
- Laravel HTTP Client para integrações externas.

### Frontend
- Vue 3.
- Composition API.
- `<script setup lang="ts">`.
- TypeScript.
- Inertia.
- Tailwind CSS.
- Vite.

### Desenvolvimento local
- Laravel Sail.
- PostgreSQL via Sail.
- Redis via Sail.

Não substituir a stack sem decisão explícita de arquitetura.

---

## 2. Princípios arquiteturais

A aplicação é um **modular monolith** Laravel + Inertia + Vue.

Fluxo padrão:

```text
Browser
  ↓
Vue / Inertia
  ↓
Controller
  ↓
Form Request
  ↓
Action
  ↓
Contracts / Domain Services
  ↓
Provider / Infrastructure
  ↓
External APIs
```

### Regras obrigatórias

1. Controllers devem ser finos.
2. Controllers não contêm regra de negócio.
3. Actions representam casos de uso.
4. Actions não fazem chamadas HTTP diretamente.
5. Actions dependem de interfaces, nunca de providers concretos.
6. Implementações concretas são resolvidas pelo Laravel Service Container.
7. Bindings devem ser declarados via Service Provider e guiados por configuração.
8. Providers encapsulam integrações externas.
9. JSON externo nunca pode vazar para Actions, Controllers ou Vue.
10. DTOs internos devem normalizar os dados de providers.
11. Vue nunca chama OpenWeather diretamente.
12. API keys nunca podem existir no bundle frontend.
13. Serviços de domínio devem conter regras reutilizáveis e puras sempre que possível.
14. Evitar overengineering e abstrações sem uso real.

---

## 3. Actions Pattern

Actions devem representar casos de uso claros.

Exemplos:
- `SearchLocationsAction`
- `GetWeatherDashboardAction`
- `CompareCitiesAction`

Formato recomendado:

```php
final readonly class ExampleAction
{
    public function __construct(
        private SomeContract $dependency,
    ) {}

    public function execute(...): SomeData
    {
        // orchestration only
    }
}
```

### Regras
- Uma Action deve ter responsabilidade única.
- Preferir método público `execute()`.
- Evitar acesso direto a `Http`, `Cache`, `DB` ou detalhes de framework quando esses detalhes pertencerem à infraestrutura.
- Actions podem compor outras Actions quando isso reduz duplicação e mantém clareza.

---

## 4. Contracts e implementação configurável

Todos os providers externos devem ser acessados por interface.

Exemplos:
- `GeocodingProvider`
- `CurrentWeatherProvider`
- `ForecastProvider`

Configuração esperada:

```php
// config/weather.php
return [
    'provider' => env('WEATHER_PROVIDER', 'openweather'),
    'providers' => [
        'openweather' => [
            'driver' => OpenWeatherProvider::class,
            'api_key' => env('OPENWEATHER_API_KEY'),
            'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org'),
        ],
    ],
];
```

O Service Provider deve resolver a implementação correspondente ao driver configurado.

### Proibido
```php
new OpenWeatherProvider();
```

ou qualquer dependência direta da Action em uma classe concreta de integração.

---

## 5. DTOs

DTOs são contratos internos da aplicação.

Usar DTOs para:
- coordenadas;
- localização;
- clima atual;
- forecast;
- forecast diário;
- dashboard;
- comparação;
- score.

### Regras
- Preferir DTOs `readonly`.
- Evitar arrays associativos não tipados em boundaries importantes.
- O provider converte payload externo para DTO interno.
- Vue deve receber estruturas estáveis e previsíveis.

---

## 6. Integração OpenWeather

Toda comunicação externa deve ficar sob:

```text
app/Integrations/OpenWeather/
```

Separação recomendada:
- `OpenWeatherClient.php`
- `OpenWeatherProvider.php`
- mappers/DTO adapters quando necessário.

### HTTP Client
Usar Laravel HTTP Client.

Configurar:
- base URL;
- API key;
- timeout;
- connect timeout;
- retry quando adequado;
- headers comuns;
- exceptions normalizadas.

### Segurança
Nunca:
- expor API key;
- logar `appid`;
- retornar payload bruto em exceptions para frontend.

### Erros externos
Mapear para exceptions internas previsíveis.

---

## 7. Cache

Redis é o cache store padrão.

TTL inicial:
- Geocoding: 30 min.
- Current weather: 10 min.
- Forecast: 30 min.

Chaves devem ser determinísticas e namespaced:

```text
weather:geo:{hash}
weather:current:{lat}:{lon}
weather:forecast:{lat}:{lon}
```

Evitar cache espalhado por Controllers.

Preferência:
- cache na camada de provider/infrastructure;
- ou decorator explícito se a complexidade justificar.

---

## 8. Frontend Vue

### Estrutura sugerida

```text
resources/js/
├── Components/
│   ├── Location/
│   ├── Weather/
│   └── Comparison/
├── Composables/
├── Layouts/
├── Pages/
├── Types/
└── Utils/
```

### Regras
- Sempre usar TypeScript.
- Preferir Composition API.
- Preferir `<script setup lang="ts">`.
- Props devem ser tipadas.
- Estado assíncrono reutilizável deve ficar em composables.
- Evitar Pinia enquanto não houver estado global real.
- Não duplicar lógica de domínio no frontend.
- Tailwind deve seguir mobile-first.

---

## 9. Design system e tema climático

A estrutura visual é estável; o tema é dinâmico.

Temas previstos:

```text
clear-day
clear-night
cloudy-day
cloudy-night
rain-day
rain-night
```

A interface deve mudar por:
- CSS variables;
- background;
- ilustração;
- contraste;
- detalhes atmosféricos.

Não criar layouts separados para cada condição climática.

### Mobile-first
Começar pelas classes sem prefixo.
Adicionar `md:` e `lg:` somente quando necessário.

### Cards
- bordas arredondadas grandes;
- spacing consistente;
- superfícies translúcidas quando fizer sentido;
- boa legibilidade em todos os temas.

---

## 10. Localização

A aplicação deve suportar:
1. browser geolocation;
2. permission denied;
3. browser sem suporte;
4. timeout;
5. busca manual por cidade.

Busca manual deve estar sempre disponível como fallback.

Autocomplete deve:
- usar debounce;
- ser navegável por teclado;
- tratar loading/error/empty;
- não disparar request com menos de 2 caracteres.

---

## 11. Outdoor Score

O Outdoor Score é uma heurística interna e não deve ser apresentado como índice científico.

Score: `0.0` a `10.0`.

Pesos iniciais:
- temperatura: 30%;
- chuva: 30%;
- umidade: 15%;
- vento: 15%;
- condição: 10%.

Toda regra deve ser coberta por unit tests.

---

## 12. Testes

### Unit
Priorizar:
- `Coordinates`;
- `DailyForecastService`;
- `WeatherThemeResolver`;
- `OutdoorScoreCalculator`.

### Providers
Usar:
```php
Http::fake();
Http::preventStrayRequests();
```

Cobrir:
- sucesso;
- 401;
- 429;
- 500;
- timeout;
- payload inválido.

### Feature
Cobrir rotas críticas, validação, componentes Inertia e props.

Nenhum teste automatizado deve depender de OpenWeather real.

---

## 13. Qualidade

Antes de concluir qualquer epic relevante:

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
npm run lint
npm run type-check
npm run build
```

Se algum comando ainda não existir no package/composer scripts, adicionar de forma consistente.

---

## 14. GitOps

### Estratégia de branches

Cada epic deve ser desenvolvido em branch própria.

Formato obrigatório:

```text
feature/epic-0-foundation
feature/epic-1-architecture
feature/epic-2-location
feature/epic-3-weather-dashboard
feature/epic-4-city-comparison
feature/epic-5-ux-resilience
feature/epic-6-testing
feature/epic-7-quality-ci
feature/epic-8-documentation
feature/epic-9-polish
```

Para correções pontuais:

```text
fix/<scope>-<short-description>
```

Para refactors:

```text
refactor/<scope>-<short-description>
```

Não trabalhar diretamente na branch principal.

---

## 15. Conventional Commits com scope

Todo commit deve seguir Conventional Commits com scope explícito.

Formato:

```text
<type>(<scope>): <description>
```

### Tipos permitidos
- `feat`
- `fix`
- `refactor`
- `test`
- `docs`
- `chore`
- `ci`
- `style`
- `perf`

### Scopes recomendados
- `foundation`
- `sail`
- `architecture`
- `weather`
- `geocoding`
- `location`
- `forecast`
- `cache`
- `dashboard`
- `theme`
- `comparison`
- `score`
- `ui`
- `a11y`
- `tests`
- `ci`
- `docs`

### Exemplos

```text
feat(location): add browser geolocation fallback
feat(geocoding): implement location search action
feat(weather): add current weather provider contract
feat(forecast): aggregate three-hour slots by day
feat(theme): resolve day and night weather themes
feat(comparison): add city comparison action
feat(score): calculate outdoor conditions score
fix(cache): normalize coordinate cache keys
test(weather): cover provider rate limit response
refactor(architecture): extract weather provider contracts
docs(readme): document inertia trade-offs
ci(pipeline): add lint test and build checks
```

### Regras de commit
- Commits devem ser pequenos e coerentes.
- Um commit deve representar uma mudança lógica.
- Evitar commits como `update`, `changes`, `wip`, `fix stuff`.
- Não misturar refactor não relacionado com feature.
- Antes do commit, rodar os checks relevantes para a mudança.

---

## 16. Fluxo por Epic

Para iniciar um epic:

```bash
git checkout main
git pull
git checkout -b feature/epic-N-short-name
```

Durante o desenvolvimento:
- implementar em pequenos commits;
- manter testes atualizados;
- evitar mudanças fora do escopo do epic.

Antes de abrir PR:

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
npm run lint
npm run type-check
npm run build
```

Depois:

```bash
git push -u origin feature/epic-N-short-name
```

### Pull Request
Título sugerido:

```text
feat(epic-N): <short epic description>
```

Descrição deve conter:
- objetivo;
- principais mudanças;
- decisões técnicas;
- como testar;
- screenshots quando houver UI;
- riscos/limitações;
- checklist de qualidade.

---

## 17. Regras para uso de IA

O uso de IA deve ser moderado e auditável.

### IA pode ajudar com
- boilerplate;
- Tailwind;
- revisão de TypeScript;
- sugestões de edge cases;
- revisão de testes;
- revisão de documentação.

### Implementação deve ser compreendida e validada manualmente em
- arquitetura;
- Contracts;
- Service Container bindings;
- Actions;
- DTO mapping;
- cache strategy;
- forecast aggregation;
- theme resolver;
- Outdoor Score;
- testes das regras.

Nunca aceitar código gerado sem:
1. entender a mudança;
2. revisar padrões do projeto;
3. executar testes;
4. validar edge cases.

---

## 18. Escopo e prioridade

### P0
- foundation;
- Sail/PostgreSQL;
- arquitetura/contracts;
- OpenWeather client;
- geocoding;
- location search;
- geolocation fallback;
- current weather;
- forecast;
- dashboard;
- dynamic theme;
- responsive layout;
- Outdoor Score;
- comparison;
- core tests;
- README.

### P1
- Redis cache;
- swipe;
- skeletons;
- provider error mapping;
- CI;
- accessibility.

### P2
Somente se P0 e P1 estiverem estáveis:
- Air Quality;
- charts;
- weather maps;
- favorites;
- search history;
- elaborate animations;
- PWA.

Não expandir escopo antes de estabilizar P0.
