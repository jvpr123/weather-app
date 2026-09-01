# EPIC 4 — Feature 3: Comparação de Cidades

## Objetivo
Adicionar regra de negócio própria e reutilizar toda a infraestrutura já construída.

## CARD 4.1 — Compare Page

### Rota
`/weather/compare`

### Layout mobile
```text
Comparar cidades

[ São Paulo ]

      VS

[ Curitiba ]

[ Comparar ]
```

### Resultado inicial
```text
São Paulo       Curitiba
   28°             19°
```

---

## CARD 4.2 — CompareCitiesAction

### Entrada
- `Coordinates A`
- `Coordinates B`

### Fluxo
```text
CompareCitiesAction
       │
       ├── GetWeatherDashboardAction(A)
       └── GetWeatherDashboardAction(B)
```

### Regra
Não duplicar integração externa.

### Saída
```text
CityComparisonData
├── left
├── right
└── recommendation
```

---

## CARD 4.3 — OutdoorScoreCalculator

### Objetivo
Calcular score comparativo de 0.0 a 10.0.

### Pesos
| Critério | Peso |
|---|---:|
| Temperatura confortável | 30% |
| Probabilidade de chuva | 30% |
| Umidade | 15% |
| Vento | 15% |
| Condição | 10% |

### Regras sugeridas

#### Temperatura
- 18–26°C → score máximo.
- Penalização progressiva fora da faixa.

#### Chuva
- 0% → 10.
- 100% → 0.

#### Umidade
- 40–70% → faixa confortável.

#### Vento
- Velocidades muito altas reduzem score.

#### Condição
```text
Clear        10
Clouds        8
Drizzle       5
Rain          3
Thunderstorm  1
```

### Observação obrigatória no README
> Outdoor Score is an application-specific heuristic created solely to provide a comparative user experience.

---

## CARD 4.4 — Testes do Outdoor Score

### Casos
- 22°C + seco + vento baixo → score alto.
- 35°C + umidade alta → penalização.
- chuva 90% → score baixo.
- tempestade → score muito baixo.

---

## CARD 4.5 — Comparison UI

### Referência
```text
┌─────────────┬─────────────┐
│ São Paulo   │ Curitiba    │
│    28°      │    19°      │
│ 🌤          │ 🌧          │
├─────────────┼─────────────┤
│ Umidade 55% │ 82%         │
│ Chuva    15%│ 70%         │
│ Vento 10km/h│ 18km/h      │
└─────────────┴─────────────┘

Outdoor Score
São Paulo  ████████░░ 8.1
Curitiba   ██████░░░░ 6.3
```

### Regra de escopo
Evitar gráficos complexos.
