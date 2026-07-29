# Plano de Refatoração Frontend

> Refatoração gradual do frontend seguindo os padrões de `docs/frontend-patterns.md`.
> Cada fase é independente e pode ser commitada separadamente.

---

## Fase 1: Funções utilitárias compartilhadas

**Objetivo:** Eliminar duplicação de funções básicas.

### 1.1 Criar `lib/format.ts`
- Extrair `formatCurrency` (duplicado 12x)
- Extrair `formatShortCurrency`
- Extrair `formatDate` (se existir duplicação)
- Extrair `formatDateRange`

### 1.2 Criar `lib/constants.ts`
- Extrair `monthNames` (duplicado 3x)
- Extrair `monthAbbrs` (duplicado 2x)
- Extrair `typeIcons` mapping (duplicado 3x)
- Extrair `typeLabels` (duplicado 3x)
- Extrair `typeColors` (já existe em `lib/colors.ts`, consolidar)

### 1.3 Atualizar imports em todos os arquivos
- Substituir funções locais por imports de `lib/format.ts` e `lib/constants.ts`

**Arquivos afetados:** 12+ arquivos
**Commit:** `refactor: extract shared utilities to lib/format.ts and lib/constants.ts`

---

## Fase 2: Composables

**Objetivo:** Extrair lógica reutilizável dos componentes.

### 2.1 Criar `composables/useMonthNavigation.ts`
- Lógica de `previousMonth()` / `nextMonth()` (duplicado 3x)
- Props: `month`, `year`, `routeName`
- Returns: `previousMonth()`, `nextMonth()`, `currentMonthName`

### 2.2 Criar `composables/useTableSort.ts`
- Lógica de `toggleSort()` + `sortIcon()` (duplicado 3x)
- Props: `initialField`, `initialDirection`
- Returns: `sorting`, `toggleSort()`, `sortIcon()`

### 2.3 Criar `composables/usePreferences.ts`
- Lógica de `fetch(route('preferences.update'))` (duplicado 3x)
- Props: `key`
- Returns: `savePreference(key, value)`, `loadPreference(key)`

### 2.4 Criar `composables/useTitleCase.ts`
- Lógica de `toTitleCase()` (duplicado 2x)

**Arquivos afetados:** ~6 arquivos
**Commit:** `refactor: extract shared logic into composables`

---

## Fase 3: Componentes base

**Objetivo:** Extrair HTML duplicado em componentes reutilizáveis.

### 3.1 Criar `Components/NavLink.vue`
- Props: `href`, `icon`, `active`
- Extrair de `AppLayout.vue` (desktop + mobile)
- Substituir os 8 nav links duplicados

### 3.2 Criar `Components/MonthNavigator.vue`
- Props: `month`, `year`, `label?`
- Events: `update:month`, `update:year`
- Extrair de `Purchases/Index.vue`, `Incomes/Index.vue`, `Cards/Purchases.vue`

### 3.3 Criar `Components/SummaryCard.vue`
- Props: `title`, `value`, `icon`, `color`, `description`
- Extrair dos cards de resumo do Dashboard e Purchases

### 3.4 Criar `Components/NotificationBadges.vue`
- Props: `card`, `type`
- Extrair de `CardsSectionGridMode.vue` e `CardsSectionTableMode.vue`

**Arquivos afetados:** ~8 arquivos
**Commit:** `refactor: extract reusable base components`

---

## Fase 4: Refatoração de páginas

**Objetivo:** Simplificar páginas usando componentes e composables criados.

### 4.1 Refatorar `AppLayout.vue`
- Usar `NavLink` component
- Remover HTML duplicado de nav links

### 4.2 Refatorar `Purchases/Index.vue`
- Usar `MonthNavigator`
- Usar `SummaryCard`
- Usar `useFormatCurrency`
- Usar `useMonthNavigation`

### 4.3 Refatorar `Incomes/Index.vue`
- Usar `MonthNavigator`
- Usar `useFormatCurrency`
- Usar `useMonthNavigation`

### 4.4 Refatorar `Cards/Index.vue`
- Usar `usePreferences`
- Usar `useFormatCurrency`

### 4.5 Refatorar `Cards/Purchases.vue`
- Usar `MonthNavigator`
- Usar `useFormatCurrency`
- Usar `useMonthNavigation`

### 4.6 Refatorar `Dashboard.vue`
- Usar `SummaryCard`
- Usar `useFormatCurrency`

**Arquivos afetados:** ~6 páginas
**Commit:** `refactor: simplify pages using new components and composables`

---

## Fase 5: Refatoração de componentes filhos

**Objetivo:** Simplificar componentes filhos seguindo SOLID.

### 5.1 Refatorar `PurchaseSummary.vue`
- Usar `useFormatCurrency`
- Usar `useTitleCase`
- Usar `typeIcons` de `lib/constants.ts`

### 5.2 Refatorar `PurchasesTableMode.vue`
- Usar `useTableSort`
- Usar `usePreferences`
- Usar `useFormatCurrency`

### 5.3 Refatorar `CardsSectionTableMode.vue`
- Usar `useTableSort`
- Usar `usePreferences`
- Usar `NotificationBadges`

### 5.4 Refatorar `CardsSectionGridMode.vue`
- Usar `NotificationBadges`

### 5.5 Refatorar `PaymentHistory.vue`
- Usar `useFormatCurrency`
- Usar `useTitleCase`
- Usar `typeIcons`

**Arquivos afetados:** ~5 componentes
**Commit:** `refactor: simplify child components following SOLID`

---

## Fase 6: Limpeza final

**Objetivo:** Verificar consistência e remover código morto.

### 6.1 Verificar imports
- Todos ordenados por grupo
- Sem imports não utilizados

### 6.2 Verificar script setup
- Todos seguem a ordem: imports → options → props → composables → state → computed → watches → functions → lifecycle

### 6.3 Verificar templates
- Diretivas na ordem correta
- Sem `else if` desnecessários

### 6.4 Rodar testes
- `php artisan test --compact`
- `npm run build`

**Commit:** `refactor: final cleanup and consistency check`

---

## Ordem de Execução

1. Fase 1 → commit
2. Fase 2 → commit
3. Fase 3 → commit
4. Fase 4 → commit
5. Fase 5 → commit
6. Fase 6 → commit

Cada fase pode ser executada independentemente. Não pular fases.
