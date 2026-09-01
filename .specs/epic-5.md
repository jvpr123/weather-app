# EPIC 5 — UX e Resiliência

## Objetivo
Garantir uma experiência consistente mesmo em carregamento, erro ou ausência de dados.

## CARD 5.1 — Loading States

### Implementar
- `LocationSearchSkeleton`
- `WeatherHeroSkeleton`
- `ForecastSkeleton`

### Regra
Evitar spinner gigante central.

---

## CARD 5.2 — Error States

### Cenários
```text
location denied
city not found
weather unavailable
forecast unavailable
network timeout
provider rate limited
```

### Regra
Nunca exibir ao usuário:
```text
500
cURL error
stack trace
OpenWeather response
```

### Exemplo de UX
```text
Não foi possível atualizar o clima agora.

[Tentar novamente]
```

---

## CARD 5.3 — Responsividade

### Viewports mínimos
```text
375px
390px
768px
1024px
1440px
```

### Prioridade
375–430px.

### Regra
Desktop deve reorganizar o conteúdo, não reinventar a interface.

---

## CARD 5.4 — Acessibilidade mínima

### Checklist
- contraste adequado;
- botões com `aria-label` quando necessário;
- inputs com label;
- foco visível;
- busca navegável por teclado;
- ícones não são única fonte de significado;
- tabs acessíveis;
- suporte a `prefers-reduced-motion`.

### Referências
- https://www.w3.org/WAI/ARIA/apg/patterns/tabs/
- https://developer.mozilla.org/docs/Web/CSS/@media/prefers-reduced-motion
