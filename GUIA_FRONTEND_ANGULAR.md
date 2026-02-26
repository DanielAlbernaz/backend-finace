# Guia de Integração Frontend Angular - Modal de Cadastro

## 📋 Resumo das Alterações

O backend agora suporta três tipos de lançamentos financeiros com lógicas distintas:

1. **Lançamento Único** (`repetition = 'only'`)
2. **Parcelamento** (`repetition = 'installments'`)
3. **Recorrente** (`repetition = 'fixed'`)

---

## 🔑 Campos Obrigatórios (Sempre)

Estes campos são **sempre obrigatórios**, independente do tipo de repetição:

```typescript
{
  type: 'expense' | 'revenue',        // ou 'despesa'/'receita' (será normalizado)
  value: number,                      // Valor do lançamento
  date: string,                       // Data de competência (YYYY-MM-DD)
  due_date: string,                   // Data de vencimento (YYYY-MM-DD)
  category_id: number,                // ID da categoria
  repetition: 'only' | 'installments' | 'fixed' | 'unico'
}
```

---

## 📝 Campos Opcionais (Sempre)

```typescript
{
  payment_date?: string | null,       // Data de pagamento (YYYY-MM-DD)
  descrition?: string | null,         // Descrição
  observation?: string | null          // Observações
}
```

---

## 🎯 Campos Condicionais por Tipo

### 1️⃣ Lançamento Único (`repetition = 'only'` ou `'unico'`)

**Nenhum campo adicional necessário.**

**Payload exemplo:**
```typescript
{
  type: 'expense',
  value: 500.00,
  date: '2026-01-15',
  due_date: '2026-01-20',
  category_id: 1,
  repetition: 'only',  // ou 'unico' (será normalizado para 'only')
  descrition: 'Compra única',
  observation: null
}
```

---

### 2️⃣ Parcelamento (`repetition = 'installments'`)

**Campos adicionais obrigatórios:**
- `number_installments_repetition`: número de parcelas (integer)

**Campo opcional:**
- `periodicity`: **NÃO é usado** para parcelamento (sempre mensal)

**Payload exemplo:**
```typescript
{
  type: 'expense',
  value: 1200.00,                      // Valor TOTAL que será dividido
  date: '2026-01-15',                  // Data inicial de competência
  due_date: '2026-01-20',              // Data inicial de vencimento
  category_id: 1,
  repetition: 'installments',
  number_installments_repetition: 12,  // ⚠️ OBRIGATÓRIO: quantidade de parcelas
  descrition: 'Compra parcelada',
  observation: null
}
```

**⚠️ Importante:**
- O backend **divide automaticamente** o valor total pela quantidade de parcelas
- As datas avançam **mês a mês** automaticamente
- O campo `periodicity` **não é necessário** (sempre mensal)

---

### 3️⃣ Lançamento Recorrente (`repetition = 'fixed'`)

**Campos adicionais obrigatórios:**
- `number_repetition`: número de repetições (integer)
- `periodicity`: frequência da recorrência

**Payload exemplo:**
```typescript
{
  type: 'expense',
  value: 1700.00,                      // Valor que será mantido em cada lançamento
  date: '2026-01-01',                  // Data inicial de competência
  due_date: '2026-01-05',              // Data inicial de vencimento
  category_id: 1,
  repetition: 'fixed',
  number_repetition: 12,               // ⚠️ OBRIGATÓRIO: quantidade de repetições
  periodicity: 'monthly',             // ⚠️ OBRIGATÓRIO: daily, weekly, monthly, annual
  descrition: 'Aluguel',
  observation: null
}
```

**Valores aceitos para `periodicity`:**
- `'daily'` - Diário
- `'weekly'` - Semanal
- `'monthly'` - Mensal (padrão se não informado)
- `'annual'` - Anual

---

## 🎨 Interface TypeScript Recomendada

```typescript
interface FinancialReleaseForm {
  // Campos sempre obrigatórios
  type: 'expense' | 'revenue' | 'despesa' | 'receita';
  value: number;
  date: string; // YYYY-MM-DD
  due_date: string; // YYYY-MM-DD
  category_id: number;
  repetition: 'only' | 'installments' | 'fixed' | 'unico';
  
  // Campos opcionais
  payment_date?: string | null;
  descrition?: string | null;
  observation?: string | null;
  
  // Campos condicionais para parcelamento
  number_installments_repetition?: number; // Obrigatório se repetition = 'installments'
  
  // Campos condicionais para recorrência
  number_repetition?: number; // Obrigatório se repetition = 'fixed'
  periodicity?: 'daily' | 'weekly' | 'monthly' | 'annual'; // Obrigatório se repetition = 'fixed'
}
```

---

## ✅ Validações no Frontend

### Validação Geral

```typescript
// Validação básica sempre necessária
if (!form.type || !form.value || !form.date || !form.due_date || !form.category_id || !form.repetition) {
  // Exibir erro: campos obrigatórios faltando
}
```

### Validação para Parcelamento

```typescript
if (form.repetition === 'installments') {
  if (!form.number_installments_repetition || form.number_installments_repetition < 2) {
    // Exibir erro: número de parcelas inválido (mínimo 2)
  }
  if (form.number_installments_repetition > 240) {
    // Exibir erro: número máximo de parcelas é 240
  }
}
```

### Validação para Recorrente

```typescript
if (form.repetition === 'fixed') {
  if (!form.number_repetition || form.number_repetition < 1) {
    // Exibir erro: número de repetições inválido
  }
  if (form.number_repetition > 240) {
    // Exibir erro: número máximo de repetições é 240
  }
  if (!form.periodicity || !['daily', 'weekly', 'monthly', 'annual'].includes(form.periodicity)) {
    // Exibir erro: periodicity inválida
  }
}
```

---

## 🎯 Lógica do Modal

### 1. Campo `repetition` (Select/Dropdown)

```typescript
repetitionOptions = [
  { value: 'only', label: 'Lançamento Único' },
  { value: 'installments', label: 'Parcelado' },
  { value: 'fixed', label: 'Recorrente' }
];
```

### 2. Exibir/Ocultar Campos Condicionais

```typescript
// No template Angular
<div *ngIf="form.repetition === 'installments'">
  <label>Número de Parcelas</label>
  <input 
    type="number" 
    [(ngModel)]="form.number_installments_repetition"
    min="2" 
    max="240"
    required
  />
  <small>O valor será dividido automaticamente</small>
</div>

<div *ngIf="form.repetition === 'fixed'">
  <label>Número de Repetições</label>
  <input 
    type="number" 
    [(ngModel)]="form.number_repetition"
    min="1" 
    max="240"
    required
  />
  
  <label>Frequência</label>
  <select [(ngModel)]="form.periodicity" required>
    <option value="daily">Diário</option>
    <option value="weekly">Semanal</option>
    <option value="monthly" selected>Mensal</option>
    <option value="annual">Anual</option>
  </select>
  <small>O valor será mantido em cada lançamento</small>
</div>
```

### 3. Mensagens de Ajuda

**Para Parcelamento:**
- "O valor total será dividido igualmente entre as parcelas"
- "As parcelas avançam automaticamente mês a mês"

**Para Recorrente:**
- "O mesmo valor será repetido em cada lançamento"
- "Escolha a frequência da recorrência"

---

## 📤 Exemplo de Envio (HTTP Service)

```typescript
createFinancialRelease(form: FinancialReleaseForm): Observable<any> {
  // Normaliza valores antes de enviar (opcional, backend também faz)
  const payload = {
    ...form,
    // Backend normaliza 'unico' para 'only' e 'despesa'/'receita' para 'expense'/'revenue'
    // Mas você pode fazer isso no frontend também
  };
  
  // Remove campos undefined/null desnecessários
  if (form.repetition === 'only') {
    delete payload.number_installments_repetition;
    delete payload.number_repetition;
    delete payload.periodicity;
  } else if (form.repetition === 'installments') {
    delete payload.number_repetition;
    delete payload.periodicity; // Não é usado em parcelamento
  } else if (form.repetition === 'fixed') {
    delete payload.number_installments_repetition;
  }
  
  return this.http.post('/api/financial_release', payload);
}
```

---

## 🔄 Fluxo Completo do Modal

### 1. Inicialização do Formulário

```typescript
form: FinancialReleaseForm = {
  type: 'expense',
  value: null,
  date: new Date().toISOString().split('T')[0], // Data atual
  due_date: new Date().toISOString().split('T')[0],
  category_id: null,
  repetition: 'only', // Padrão: lançamento único
  payment_date: null,
  descrition: null,
  observation: null
};
```

### 2. Mudança de Tipo de Repetição

```typescript
onRepetitionChange() {
  // Limpa campos condicionais quando muda o tipo
  if (this.form.repetition === 'only') {
    this.form.number_installments_repetition = null;
    this.form.number_repetition = null;
    this.form.periodicity = null;
  } else if (this.form.repetition === 'installments') {
    this.form.number_repetition = null;
    this.form.periodicity = null;
    // Define valor padrão para parcelas
    if (!this.form.number_installments_repetition) {
      this.form.number_installments_repetition = 2;
    }
  } else if (this.form.repetition === 'fixed') {
    this.form.number_installments_repetition = null;
    // Define valores padrão
    if (!this.form.number_repetition) {
      this.form.number_repetition = 12;
    }
    if (!this.form.periodicity) {
      this.form.periodicity = 'monthly';
    }
  }
}
```

### 3. Validação Antes de Enviar

```typescript
validateForm(): boolean {
  // Validação básica
  if (!this.form.type || !this.form.value || !this.form.date || 
      !this.form.due_date || !this.form.category_id || !this.form.repetition) {
    this.showError('Preencha todos os campos obrigatórios');
    return false;
  }
  
  // Validação condicional
  if (this.form.repetition === 'installments') {
    if (!this.form.number_installments_repetition || 
        this.form.number_installments_repetition < 2 || 
        this.form.number_installments_repetition > 240) {
      this.showError('Número de parcelas deve ser entre 2 e 240');
      return false;
    }
  }
  
  if (this.form.repetition === 'fixed') {
    if (!this.form.number_repetition || 
        this.form.number_repetition < 1 || 
        this.form.number_repetition > 240) {
      this.showError('Número de repetições deve ser entre 1 e 240');
      return false;
    }
    if (!this.form.periodicity) {
      this.showError('Selecione a frequência da recorrência');
      return false;
    }
  }
  
  return true;
}
```

### 4. Envio do Formulário

```typescript
onSubmit() {
  if (!this.validateForm()) {
    return;
  }
  
  this.loading = true;
  this.financialReleaseService.createFinancialRelease(this.form)
    .subscribe({
      next: (response) => {
        this.loading = false;
        // Se retornou array, foram criadas múltiplas parcelas/recorrências
        if (Array.isArray(response)) {
          this.showSuccess(`${response.length} lançamentos criados com sucesso!`);
        } else {
          this.showSuccess('Lançamento criado com sucesso!');
        }
        this.closeModal();
        this.onSuccess.emit();
      },
      error: (error) => {
        this.loading = false;
        this.showError(error.error?.message || 'Erro ao criar lançamento');
      }
    });
}
```

---

## 📊 Resumo dos Campos por Tipo

| Campo | Único | Parcelado | Recorrente |
|-------|-------|-----------|------------|
| `type` | ✅ | ✅ | ✅ |
| `value` | ✅ | ✅ (total) | ✅ (por lançamento) |
| `date` | ✅ | ✅ (inicial) | ✅ (inicial) |
| `due_date` | ✅ | ✅ (inicial) | ✅ (inicial) |
| `category_id` | ✅ | ✅ | ✅ |
| `repetition` | ✅ | ✅ | ✅ |
| `number_installments_repetition` | ❌ | ✅ | ❌ |
| `number_repetition` | ❌ | ❌ | ✅ |
| `periodicity` | ❌ | ❌ | ✅ |

---

## ⚠️ Pontos de Atenção

1. **Parcelamento vs Recorrente:**
   - Parcelamento: divide valor, cria `installments`, sempre mensal
   - Recorrente: mantém valor, não cria `installments`, pode ter periodicity

2. **Campos de Data:**
   - `date`: data de competência (quando o lançamento "ocorre")
   - `due_date`: data de vencimento (quando deve ser pago)
   - Para parcelamento/recorrência, são as datas **iniciais**

3. **Normalização:**
   - Backend aceita `'unico'` e normaliza para `'only'`
   - Backend aceita `'despesa'/'receita'` e normaliza para `'expense'/'revenue'`
   - Mas é melhor normalizar no frontend também

4. **Resposta da API:**
   - Lançamento único: retorna objeto único
   - Parcelamento/Recorrente: retorna array de objetos

---

## 🎯 Checklist de Implementação

- [ ] Adicionar campo `repetition` no formulário (select)
- [ ] Adicionar campo `number_installments_repetition` (condicional para parcelamento)
- [ ] Adicionar campo `number_repetition` (condicional para recorrência)
- [ ] Adicionar campo `periodicity` (condicional para recorrência)
- [ ] Implementar validações condicionais
- [ ] Implementar lógica de mostrar/ocultar campos
- [ ] Adicionar mensagens de ajuda para cada tipo
- [ ] Tratar resposta da API (objeto único vs array)
- [ ] Adicionar campo `due_date` (se ainda não tiver)

---

**Última atualização:** 2026-01-18
