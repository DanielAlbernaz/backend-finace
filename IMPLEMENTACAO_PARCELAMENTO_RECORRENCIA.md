# Implementação de Parcelamento e Recorrência

## ✅ Implementação Completa

A lógica de parcelamento (installments) e lançamentos recorrentes (fixed) foi implementada no `InstallmentService`.

---

## 📋 Estrutura da Implementação

### Arquivo: `app/Services/Installment/InstallmentService.php`

O serviço foi refatorado com três métodos privados principais:

1. **`createSingleRelease()`** - Lançamento único (`repetition = only`)
2. **`createInstallmentReleases()`** - Parcelamento (`repetition = installments`)
3. **`createFixedReleases()`** - Recorrente (`repetition = fixed`)

---

## 🔄 Fluxo de Execução

```
createFinancialRelease() [FinancialReleaseService]
    ↓
createInstallments() [InstallmentService]
    ↓
    ├─→ repetition = 'only' → createSingleRelease()
    ├─→ repetition = 'installments' → createInstallmentReleases()
    └─→ repetition = 'fixed' → createFixedReleases()
```

---

## 1️⃣ Parcelamento (`repetition = installments`)

### Regras Implementadas:

✅ **Cria registro em `installments`**
- Tipo: `'installments'`
- Um registro por grupo de parcelas

✅ **Divide o valor total**
- `value_per_installment = total_value / quantity`
- Arredondamento para 2 casas decimais

✅ **Gera N registros em `financial_releases`**
- Um registro para cada parcela
- `installment_id` preenchido (vinculado ao installment criado)
- `repetition = 'installments'`
- `status = 'pending'` (calculado automaticamente)

✅ **Data de competência (`date`)**
- Avança mês a mês a partir da data inicial
- Usa `Carbon::addMonths($i)`

✅ **Data de vencimento (`due_date`)**
- Avança mês a mês mantendo o mesmo dia
- Calculado separadamente da data de competência
- Usa `Carbon::addMonths($i)` na data de vencimento inicial

✅ **Campo `portion`**
- Formato: `"1/12"`, `"2/12"`, ..., `"12/12"`
- Calculado como `($i + 1) . '/' . $quantity`

### Exemplo:

**Input:**
```json
{
  "type": "expense",
  "value": 1200.00,
  "date": "2026-01-15",
  "due_date": "2026-01-20",
  "repetition": "installments",
  "number_installments_repetition": 12
}
```

**Output:**
- 1 registro em `installments` (type: 'installments')
- 12 registros em `financial_releases`:
  - Parcela 1: value=100.00, date=2026-01-15, due_date=2026-01-20, portion="1/12"
  - Parcela 2: value=100.00, date=2026-02-15, due_date=2026-02-20, portion="2/12"
  - ...
  - Parcela 12: value=100.00, date=2026-12-15, due_date=2026-12-20, portion="12/12"

---

## 2️⃣ Lançamento Recorrente (`repetition = fixed`)

### Regras Implementadas:

✅ **NÃO cria registro em `installments`**
- `installment_id = null` em todos os registros

✅ **Mantém valor original**
- `value` não é dividido
- Cada lançamento tem o mesmo valor

✅ **Gera N registros em `financial_releases`**
- Onde N = `number_repetition`
- `repetition = 'fixed'`
- `status = 'pending'` (calculado automaticamente)

✅ **Data de competência (`date`)**
- Avança baseado na `periodicity`:
  - `daily`: adiciona dias
  - `weekly`: adiciona semanas
  - `monthly`: adiciona meses (padrão)
  - `annual`: adiciona anos

✅ **Data de vencimento (`due_date`)**
- Avança baseado na mesma `periodicity`
- Calculado separadamente da data de competência

✅ **Campo `portion`** (opcional/informativo)
- Formato: `"1/12"`, `"2/12"`, etc.
- Apenas para referência visual

### Exemplo:

**Input:**
```json
{
  "type": "expense",
  "value": 1700.00,
  "date": "2026-01-01",
  "due_date": "2026-01-05",
  "repetition": "fixed",
  "number_repetition": 12,
  "periodicity": "monthly"
}
```

**Output:**
- 0 registros em `installments`
- 12 registros em `financial_releases`:
  - Lançamento 1: value=1700.00, date=2026-01-01, due_date=2026-01-05, portion="1/12"
  - Lançamento 2: value=1700.00, date=2026-02-01, due_date=2026-02-05, portion="2/12"
  - ...
  - Lançamento 12: value=1700.00, date=2026-12-01, due_date=2026-12-05, portion="12/12"

---

## 3️⃣ Lançamento Único (`repetition = only`)

### Regras Implementadas:

✅ **Cria apenas 1 registro**
- `installment_id = null`
- `portion = null`
- `repetition = 'only'`

### Exemplo:

**Input:**
```json
{
  "type": "revenue",
  "value": 5000.00,
  "date": "2026-01-10",
  "due_date": "2026-01-10",
  "repetition": "only"
}
```

**Output:**
- 1 registro em `financial_releases` com os dados originais

---

## 🔧 Métodos Auxiliares

### `calculateDateByPeriodicity()`

Calcula datas baseado na periodicity para lançamentos recorrentes:

- **daily**: `addDays($index)`
- **weekly**: `addWeeks($index)`
- **monthly**: `addMonths($index)` (padrão)
- **annual**: `addYears($index)`

---

## 📊 Campos Utilizados

### Para Parcelamento (`installments`):
- `number_installments_repetition` - Quantidade de parcelas
- `date` - Data inicial de competência
- `due_date` - Data inicial de vencimento
- **Sempre avança mês a mês** (não usa periodicity)

### Para Recorrente (`fixed`):
- `number_repetition` - Quantidade de repetições
- `periodicity` - Frequência (daily, weekly, monthly, annual)
- `date` - Data inicial de competência
- `due_date` - Data inicial de vencimento

### Para Único (`only`):
- `date` - Data de competência
- `due_date` - Data de vencimento

---

## ✅ Validações e Segurança

1. **Verificação de plano**: Parcelamentos e recorrências requerem feature `installments` no plano
2. **Validação de dados**: Todos os campos obrigatórios são verificados
3. **Cálculo de status**: Status é calculado automaticamente via `updateStatus()` do modelo
4. **Integridade referencial**: Foreign keys são respeitadas

---

## 🎯 Garantias da Implementação

✅ **Nunca recalcula parcelas dinamicamente**
- Todas as parcelas/recorrências existem fisicamente no banco
- Cada registro é independente

✅ **Nunca mistura parcelamento com recorrência**
- Lógica completamente separada
- `installments` sempre cria registro em `installments`
- `fixed` nunca cria registro em `installments`

✅ **Status calculado automaticamente**
- Baseado em `due_date` e `payment_date`
- Não requer lógica adicional

✅ **Relatórios funcionam corretamente**
- Cada lançamento é um registro independente
- Filtros por data, status, etc. funcionam normalmente

---

## 📝 Observações Importantes

1. **Arredondamento**: Valores de parcelas são arredondados para 2 casas decimais
2. **Datas**: Usa `Carbon` para manipulação de datas (respeita meses com diferentes quantidades de dias)
3. **Performance**: Para múltiplos registros, usa `FinancialRelease::insert()` (bulk insert)
4. **Auditoria**: Campo `created_by` é preenchido automaticamente

---

## 🧪 Testes Sugeridos

1. **Parcelamento**: Criar despesa de R$ 1.200 em 12x e verificar:
   - 1 registro em `installments`
   - 12 registros em `financial_releases`
   - Valores corretos (R$ 100,00 cada)
   - Datas avançando mês a mês

2. **Recorrente**: Criar aluguel de R$ 1.700 por 12 meses e verificar:
   - 0 registros em `installments`
   - 12 registros em `financial_releases`
   - Valores iguais (R$ 1.700,00 cada)
   - Datas avançando conforme periodicity

3. **Único**: Criar receita única e verificar:
   - 1 registro em `financial_releases`
   - Dados originais preservados

---

**Última atualização:** 2026-01-18
**Status:** ✅ Implementação Completa
