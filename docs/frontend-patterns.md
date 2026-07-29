# Frontend Patterns & Conventions

> Documento de referência para padrões de código frontend no projeto My Wallet.
> Toda nova feature ou refatoração deve seguir essas regras.

---

## 1. Imports

**Ordem por grupo (asc dentro de cada grupo):**

1. Vue (`vue`, `@vueuse/*`)
2. Inertia (`@inertiajs/*`)
3. Componentes UI (`@/components/ui/*`)
4. Ícones (`lucide-vue`, `@lucide/vue`)
5. Componentes locais (`@/Components/*`, `./Partials/*`)
6. Utilitários (`@/lib/*`)
7. Tipos (`@/types/*`)

---

## 2. Estrutura do `<script setup>`

**Ordem fixa:**
1. Imports
2. defineOptions
3. Props e Emits
4. Composables
5. Variáveis reativas / Estado local
6. Computed
7. Watches
8. Funções / Métodos
9. Ciclos de vida
10. expose (opcional)

---

## 3. Diretivas no Template

**Ordem:** `is` → `v-for` → `v-if` → `v-else-if` → `v-else` → `v-show` → `id` → `ref` → `key` → `v-model` → `class` → `:class` → `style` → `:style` → props comuns → `:props` → `@action`

---

## 4. Evitar Else

- Usar **early returns** em vez de `if/else`
- Usar **ternários** para atribuições simples

---

## 5. Componentização e SOLID

- **Páginas**: orquestração, fetch, estado global
- **Componentes filhos**: recebem props, emitem eventos, sem fetch
- **Composables**: lógica reutilizável extraída
- **HTML duplicado**: extrair para componente
- **Lógica duplicada**: extrair para composable

---

## 6. Componentes Base a Criar

| Componente | Extraído de |
|---|---|
| `NavLink` | AppLayout.vue (nav links) |
| `MonthNavigator` | Purchases, Incomes, Cards/Purchases |
| `SummaryCard` | Dashboard + Purchases (cards de resumo) |
| `NotificationBadges` | CardsSectionGridMode + TableMode |
| `DataTable` | CardsSectionTableMode + PurchasesTableMode |

---

## 7. Composables a Criar

| Composable | Duplicado em |
|---|---|
| `useFormatCurrency` | 12 arquivos |
| `useMonthNavigation` | 3 arquivos (6 funções) |
| `useTableSort` | 3 arquivos |
| `usePreferences` | 3 arquivos |
| `useMonthNames` | 3 arquivos |
| `useTypeIcons` | 3 arquivos |
| `useTitleCase` | 2 arquivos |

---

## 8. Checklist de Code Review

- [ ] Imports ordenados por grupo
- [ ] Script setup na ordem correta
- [ ] Diretivas na ordem correta
- [ ] Sem `else if` desnecessários
- [ ] HTML duplicado extraído
- [ ] Lógica compartilhada em composable
- [ ] Páginas usam filhos via props (sem fetch em filhos)
- [ ] Formatação usa `lib/format.ts`
