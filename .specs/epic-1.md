# EPIC 1 — Arquitetura e Integração

## Objetivo
Definir os boundaries da aplicação e encapsular completamente a integração com provedores externos.

## CARD 1.1 — Definir boundaries da aplicação

### Estrutura
```text
app/
├── Actions/
│   ├── Location/
│   └── Weather/
├── Contracts/
│   └── Weather/
├── DTOs/
│   ├── Location/
│   └── Weather/
├── Integrations/
│   └── OpenWeather/
├── Services/
│   └── Weather/
├── Http/
│   ├── Controllers/
│   └── Requests/
└── Providers/
```

### Fluxo arquitetural
```text
HTTP
 ↓
Controller
 ↓
Action
 ↓
Contract / Domain Service
 ↓
Infrastructure
 ↓
OpenWeather
```

### Regras
Nunca:
```text
Controller → OpenWeather
Vue → OpenWeather
Action → Http::get()
Controller → regra de negócio
```

### Responsabilidades
- **Controller:** HTTP input/output.
- **Form Request:** validação e normalização simples.
- **Action:** caso de uso/orquestração.
- **Contract:** capacidade necessária pela aplicação.
- **Provider:** integração externa.
- **DTO:** contrato interno de dados.
- **Service:** regra reutilizável de domínio/aplicação.

### Critérios de aceite
- Nenhuma Action depende de implementação concreta de OpenWeather.
- Nenhum Controller contém regra de negócio.
- Nenhum payload externo vaza diretamente para Vue.

### Referências
- https://laravel.com/docs/13.x/container
- https://laravel.com/docs/13.x/providers

---

## CARD 1.2 — Configuração do Weather Provider

### Objetivo
Permitir troca de provider sem alterar Actions.

### Arquivo
`config/weather.php`

### Exemplo
```php
return [
    'provider' => env('WEATHER_PROVIDER', 'openweather'),

    'providers' => [
        'openweather' => [
            'driver' => App\Integrations\OpenWeather\OpenWeatherProvider::class,
            'api_key' => env('OPENWEATHER_API_KEY'),
            'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org'),
        ],
    ],
];
```

### Interfaces iniciais
- `GeocodingProvider`
- `CurrentWeatherProvider`
- `ForecastProvider`

### Subtarefas
- Criar `WeatherServiceProvider`.
- Registrar bindings por interface.
- Resolver implementação a partir da configuração.
- Adicionar variáveis no `.env.example`.

### Critérios de aceite
```php
public function __construct(
    private CurrentWeatherProvider $weather,
) {}
```
Deve ser resolvido automaticamente pelo container.

---

## CARD 1.3 — OpenWeather HTTP Client

### Objetivo
Centralizar comunicação HTTP, autenticação e tratamento de falhas.

### Arquivo
`app/Integrations/OpenWeather/OpenWeatherClient.php`

### Responsabilidades
- `baseUrl()`
- API key
- `timeout()`
- `connectTimeout()`
- `retry()`
- `acceptJson()`
- tratamento consistente de erros

### Erros a mapear
```text
401 → configuração/API key
404 → recurso não encontrado
429 → rate limit externo
5xx → indisponibilidade OpenWeather
ConnectionException → erro de rede
```

Criar exception própria:
- `WeatherProviderException`

### Segurança
Nunca logar:
- `appid`
- `OPENWEATHER_API_KEY`

### Testes
Usar:
```php
Http::fake();
Http::assertSent();
Http::preventStrayRequests();
```

### Referências
- https://laravel.com/docs/13.x/http-client
- https://openweathermap.org/api
