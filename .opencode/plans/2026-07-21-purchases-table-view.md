# Compras — Visualização em Tabela

## Visão Geral
Adicionar um segundo modo de visualização (tabela compacta) na página de listagem de compras, seguindo o mesmo padrão da página de cartões.

## Alternador de Visualização
- Localizado no header, ao lado do botão "Nova compra"
- Dois botões `variant="outline" size="icon"` com tooltips:
  - `LayoutList` → cards (padrão)
  - `Table` → tabela
- Botão ativo: `bg-primary text-primary-foreground`
- Estado: `ref<'card' | 'table'>('card')` no `Index.vue`
- Sem persistência
- Cada botão com `Tooltip` do shadcn-vue: "Visualização em cards" / "Visualização em tabela"

## Estrutura da Tabela
Novo componente: `resources/js/Pages/Purchases/Partials/PurchasesTableMode.vue`

### Colunas
| Coluna | Conteúdo | Ordenável |
|---|---|---|
| Ícone | Ícone lucide com cor do tipo | Não |
| Nome | Nome da compra ou nome do cartão | Sim |
| Datas | "Dia 15" ou "Fechamento 10 / Vencimento 15" | Não |
| Status | StatusBadge | Sim |
| Valor | formatCurrency alinhado à direita | Sim |

- `hover:bg-muted/50`, `cursor-pointer`
- Clique → modal de detalhes
- Grupos de cartão como linha única

### Ordenação
- `sortKey`: `ref<'name' | 'status' | 'amount' | null>(null)`
- `sortDirection`: `ref<'asc' | 'desc'>('asc')`
- Clique no header alterna: `none → asc → desc → none`
- Setas `▲▼` no header
- Client-side via `computed`

## Arquivos
- **Modificar**: `resources/js/Pages/Purchases/Index.vue` — viewMode + toggle + render condicional
- **Criar**: `resources/js/Pages/Purchases/Partials/PurchasesTableMode.vue`
- Nenhuma alteração no backend

## Passos de Implementação
1. Adicionar `Table`, `TableBody`, `TableCell`, `TableHead`, `TableHeader`, `TableRow` do shadcn-vue se não existirem (`npx shadcn-vue add table`)
2. Criar `PurchasesTableMode.vue` com template da tabela recebendo `items`, `month`, `year` e emitindo `select` (ou similar) para abrir modal
3. Implementar ordenação client-side com `computed`
4. Em `Index.vue`: adicionar `viewMode` ref, toggle buttons no header, renderizar `PurchaseSummary` ou `PurchasesTableMode` condicionalmente
5. Testar build e lint
