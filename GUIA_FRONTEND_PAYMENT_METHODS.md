# Guia de Implementação: Formas de Pagamento (Payment Methods)

## 📋 Visão Geral

O sistema agora suporta **Formas de Pagamento personalizadas**, permitindo que usuários criem métodos como "Cartão Itaú", "Carteira PicPay", etc. Cada lançamento financeiro pode ser associado a uma forma de pagamento.

---

## 🗂️ Estrutura da API

### Modelo: PaymentMethod

```typescript
interface PaymentMethod {
  id: number;
  finance_account_id: number | null;  // null = método padrão global
  name: string;                       // Ex: "Cartão Itaú", "Pix"
  type: 'cash' | 'pix' | 'debit' | 'credit_card' | 'wallet' | 'other';
  is_active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;          // Soft delete
}
```

**Regras importantes:**
- `finance_account_id = null` → Método padrão do sistema (Dinheiro, PIX, Débito, Crédito)
- `finance_account_id != null` → Método personalizado da conta
- `is_active = false` → Método desativado (não aparece na listagem)
- `deleted_at != null` → Método deletado (soft delete, não aparece na listagem)

---

## 🛣️ Endpoints da API

### 1. **Listar Métodos de Pagamento**

```
GET /api/payment-methods
```

**Resposta:**
```json
[
  {
    "id": 1,
    "finance_account_id": null,
    "name": "Dinheiro",
    "type": "cash",
    "is_active": true,
    "created_at": "2026-01-18T00:00:00.000000Z",
    "updated_at": "2026-01-18T00:00:00.000000Z",
    "deleted_at": null
  },
  {
    "id": 2,
    "finance_account_id": null,
    "name": "Pix",
    "type": "pix",
    "is_active": true,
    "created_at": "2026-01-18T00:00:00.000000Z",
    "updated_at": "2026-01-18T00:00:00.000000Z",
    "deleted_at": null
  },
  {
    "id": 5,
    "finance_account_id": 1,
    "name": "Cartão Itaú",
    "type": "credit_card",
    "is_active": true,
    "created_at": "2026-01-18T20:00:00.000000Z",
    "updated_at": "2026-01-18T20:00:00.000000Z",
    "deleted_at": null
  }
]
```

**Query Parameters (opcionais):**
- `type` - Filtra por tipo: `cash`, `pix`, `debit`, `credit_card`, `wallet`, `other`

**Exemplo:**
```
GET /api/payment-methods?type=credit_card
```

**Observações:**
- Retorna apenas métodos ativos (`is_active = true`)
- Retorna métodos globais (`finance_account_id = null`) + métodos da conta atual
- Métodos deletados (soft delete) não aparecem automaticamente
- Ordenação: métodos globais primeiro, depois por nome

---

### 2. **Obter Detalhes de um Método**

```
GET /api/payment-methods/{id}
```

**Resposta:**
```json
{
  "id": 5,
  "finance_account_id": 1,
  "name": "Cartão Itaú",
  "type": "credit_card",
  "is_active": true,
  "created_at": "2026-01-18T20:00:00.000000Z",
  "updated_at": "2026-01-18T20:00:00.000000Z",
  "deleted_at": null
}
```

---

### 3. **Criar Método Personalizado**

```
POST /api/payment-methods
```

**Body:**
```json
{
  "name": "Cartão Itaú",
  "type": "credit_card",
  "is_active": true  // Opcional, default: true
}
```

**Validações:**
- `name`: obrigatório, string, máximo 255 caracteres
- `type`: obrigatório, enum: `cash`, `pix`, `debit`, `credit_card`, `wallet`, `other`
- `is_active`: opcional, boolean (default: true)

**Resposta (201):**
```json
{
  "message": "Forma de pagamento criada com sucesso",
  "data": {
    "id": 5,
    "finance_account_id": 1,
    "name": "Cartão Itaú",
    "type": "credit_card",
    "is_active": true,
    "created_at": "2026-01-18T20:00:00.000000Z",
    "updated_at": "2026-01-18T20:00:00.000000Z",
    "deleted_at": null
  }
}
```

**Erros possíveis:**
- `422` - Já existe um método com este nome na conta
- `400` - Usuário não possui uma finança ativa

---

### 4. **Atualizar Método Personalizado**

```
PUT /api/payment-methods/{id}
ou
PATCH /api/payment-methods/{id}
```

**Body (todos os campos opcionais):**
```json
{
  "name": "Cartão Itaú - Final 1234",
  "type": "credit_card",
  "is_active": true
}
```

**Resposta (200):**
```json
{
  "message": "Forma de pagamento atualizada com sucesso",
  "data": {
    "id": 5,
    "finance_account_id": 1,
    "name": "Cartão Itaú - Final 1234",
    "type": "credit_card",
    "is_active": true,
    "created_at": "2026-01-18T20:00:00.000000Z",
    "updated_at": "2026-01-18T20:05:00.000000Z",
    "deleted_at": null
  }
}
```

**Erros possíveis:**
- `403` - Tentativa de editar método padrão global
- `403` - Tentativa de editar método de outra conta
- `422` - Já existe outro método com o novo nome

---

### 5. **Desativar Método**

```
PATCH /api/payment-methods/{id}/disable
```

**Body:** (nenhum campo necessário)

**Resposta (200):**
```json
{
  "message": "Forma de pagamento desativada com sucesso",
  "data": {
    "id": 5,
    "finance_account_id": 1,
    "name": "Cartão Itaú",
    "type": "credit_card",
    "is_active": false,  // Alterado para false
    "created_at": "2026-01-18T20:00:00.000000Z",
    "updated_at": "2026-01-18T20:10:00.000000Z",
    "deleted_at": null
  }
}
```

**Erros possíveis:**
- `403` - Tentativa de desativar método padrão global
- `403` - Tentativa de desativar método de outra conta

**Observações:**
- Apenas altera `is_active = false`
- O método continua no banco de dados
- Não aparece mais na listagem de métodos disponíveis
- Lançamentos existentes que usam este método continuam funcionando

---

### 6. **Deletar Método (Soft Delete)**

```
DELETE /api/payment-methods/{id}
```

**Body:** (nenhum campo necessário)

**Resposta (200):**
```json
{
  "message": "Forma de pagamento removida com sucesso"
}
```

**Erros possíveis:**
- `403` - Tentativa de deletar método padrão global
- `403` - Tentativa de deletar método de outra conta
- `422` - Método está em uso em lançamentos financeiros (mensagem: "Não é possível deletar esta forma de pagamento pois ela está sendo usada em lançamentos financeiros. Use desativar (disable) em vez disso.")

**Observações:**
- **Soft delete** - o registro não é removido fisicamente
- Só permite deletar se não houver lançamentos usando este método
- Se estiver em uso, use `disable` em vez de `delete`

---

## 🔗 Integração com Lançamentos Financeiros

### Campo `payment_method_id` em FinancialRelease

Agora o campo `payment_method_id` está disponível na criação e atualização de lançamentos financeiros:

**Request de criação:**
```json
{
  "type": "expense",
  "value": 100.00,
  "date": "2026-01-18",
  "due_date": "2026-02-05",
  "category_id": 14,
  "payment_method_id": 5,  // ← NOVO: ID do método de pagamento (opcional)
  "repetition": "installments",
  "number_installments_repetition": 5
}
```

**Response com payment_method:**
```json
{
  "id": 1,
  "type": "expense",
  "value": 100.00,
  "date": "2026-01-18",
  "due_date": "2026-02-05",
  "category_id": 14,
  "payment_method_id": 5,
  "payment_method": {  // ← Relacionamento carregado (se incluir 'payment_method' no request)
    "id": 5,
    "name": "Cartão Itaú",
    "type": "credit_card",
    "is_active": true
  },
  "repetition": "installments",
  "portion": "1/5",
  ...
}
```

---

## 📱 Implementação no Angular

### 1. **Criar Interface TypeScript**

```typescript
// src/app/models/payment-method.model.ts

export interface PaymentMethod {
  id: number;
  finance_account_id: number | null;
  name: string;
  type: 'cash' | 'pix' | 'debit' | 'credit_card' | 'wallet' | 'other';
  is_active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export type PaymentMethodType = 'cash' | 'pix' | 'debit' | 'credit_card' | 'wallet' | 'other';
```

### 2. **Criar Service**

```typescript
// src/app/services/payment-method.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { PaymentMethod, PaymentMethodType } from '../models/payment-method.model';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class PaymentMethodService {
  private apiUrl = `${environment.apiUrl}/payment-methods`;

  constructor(private http: HttpClient) {}

  /**
   * Lista todos os métodos de pagamento disponíveis
   * Retorna métodos globais + métodos da conta atual
   */
  list(type?: PaymentMethodType): Observable<PaymentMethod[]> {
    let params = new HttpParams();
    if (type) {
      params = params.set('type', type);
    }
    return this.http.get<PaymentMethod[]>(this.apiUrl, { params });
  }

  /**
   * Obtém detalhes de um método de pagamento
   */
  getById(id: number): Observable<PaymentMethod> {
    return this.http.get<PaymentMethod>(`${this.apiUrl}/${id}`);
  }

  /**
   * Cria um novo método de pagamento personalizado
   */
  create(data: { name: string; type: PaymentMethodType; is_active?: boolean }): Observable<{ message: string; data: PaymentMethod }> {
    return this.http.post<{ message: string; data: PaymentMethod }>(this.apiUrl, data);
  }

  /**
   * Atualiza um método de pagamento
   */
  update(id: number, data: Partial<{ name: string; type: PaymentMethodType; is_active: boolean }>): Observable<{ message: string; data: PaymentMethod }> {
    return this.http.put<{ message: string; data: PaymentMethod }>(`${this.apiUrl}/${id}`, data);
  }

  /**
   * Desativa um método de pagamento
   */
  disable(id: number): Observable<{ message: string; data: PaymentMethod }> {
    return this.http.patch<{ message: string; data: PaymentMethod }>(`${this.apiUrl}/${id}/disable`, {});
  }

  /**
   * Deleta um método de pagamento (soft delete)
   */
  delete(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.apiUrl}/${id}`);
  }

  /**
   * Verifica se é método padrão (global)
   */
  isDefault(method: PaymentMethod): boolean {
    return method.finance_account_id === null;
  }

  /**
   * Obtém label traduzido para o tipo
   */
  getTypeLabel(type: PaymentMethodType): string {
    const labels: Record<PaymentMethodType, string> = {
      cash: 'Dinheiro',
      pix: 'Pix',
      debit: 'Débito',
      credit_card: 'Crédito',
      wallet: 'Carteira Digital',
      other: 'Outro'
    };
    return labels[type] || type;
  }

  /**
   * Obtém ícone para o tipo
   */
  getTypeIcon(type: PaymentMethodType): string {
    const icons: Record<PaymentMethodType, string> = {
      cash: 'money',
      pix: 'qr_code',
      debit: 'credit_card',
      credit_card: 'credit_card',
      wallet: 'account_balance_wallet',
      other: 'more_horiz'
    };
    return icons[type] || 'more_horiz';
  }
}
```

### 3. **Componente de Listagem**

```typescript
// src/app/components/payment-methods/payment-methods.component.ts

import { Component, OnInit } from '@angular/core';
import { PaymentMethodService } from '../../services/payment-method.service';
import { PaymentMethod, PaymentMethodType } from '../../models/payment-method.model';

@Component({
  selector: 'app-payment-methods',
  templateUrl: './payment-methods.component.html',
  styleUrls: ['./payment-methods.component.css']
})
export class PaymentMethodsComponent implements OnInit {
  paymentMethods: PaymentMethod[] = [];
  loading = false;
  selectedType: PaymentMethodType | null = null;

  constructor(private paymentMethodService: PaymentMethodService) {}

  ngOnInit(): void {
    this.loadPaymentMethods();
  }

  loadPaymentMethods(): void {
    this.loading = true;
    this.paymentMethodService.list(this.selectedType || undefined).subscribe({
      next: (methods) => {
        this.paymentMethods = methods;
        this.loading = false;
      },
      error: (error) => {
        console.error('Erro ao carregar métodos de pagamento:', error);
        this.loading = false;
      }
    });
  }

  onTypeFilterChange(type: PaymentMethodType | null): void {
    this.selectedType = type;
    this.loadPaymentMethods();
  }

  onDelete(id: number): void {
    if (confirm('Tem certeza que deseja deletar esta forma de pagamento?')) {
      this.paymentMethodService.delete(id).subscribe({
        next: () => {
          this.loadPaymentMethods();
        },
        error: (error) => {
          if (error.status === 422) {
            alert('Não é possível deletar esta forma de pagamento pois ela está em uso. Use desativar em vez disso.');
          } else {
            alert('Erro ao deletar forma de pagamento');
          }
        }
      });
    }
  }

  onDisable(id: number): void {
    this.paymentMethodService.disable(id).subscribe({
      next: () => {
        this.loadPaymentMethods();
      },
      error: (error) => {
        alert('Erro ao desativar forma de pagamento');
      }
    });
  }

  isDefault(method: PaymentMethod): boolean {
    return this.paymentMethodService.isDefault(method);
  }

  getTypeLabel(type: PaymentMethodType): string {
    return this.paymentMethodService.getTypeLabel(type);
  }
}
```

### 4. **Template HTML - Listagem**

```html
<!-- src/app/components/payment-methods/payment-methods.component.html -->

<div class="payment-methods-container">
  <div class="header">
    <h2>Formas de Pagamento</h2>
    <button (click)="showCreateModal = true" class="btn-primary">
      Nova Forma de Pagamento
    </button>
  </div>

  <!-- Filtro por tipo -->
  <div class="filters">
    <select [(ngModel)]="selectedType" (change)="onTypeFilterChange(selectedType)">
      <option [value]="null">Todos os tipos</option>
      <option value="cash">Dinheiro</option>
      <option value="pix">Pix</option>
      <option value="debit">Débito</option>
      <option value="credit_card">Crédito</option>
      <option value="wallet">Carteira Digital</option>
      <option value="other">Outro</option>
    </select>
  </div>

  <!-- Lista de métodos -->
  <div class="methods-list" *ngIf="!loading">
    <div *ngFor="let method of paymentMethods" class="method-card">
      <div class="method-info">
        <h3>{{ method.name }}</h3>
        <span class="badge badge-{{ method.type }}">
          {{ getTypeLabel(method.type) }}
        </span>
        <span *ngIf="isDefault(method)" class="badge badge-default">
          Padrão
        </span>
      </div>

      <div class="method-actions">
        <button 
          *ngIf="!isDefault(method)" 
          (click)="onDisable(method.id)"
          class="btn-secondary">
          Desativar
        </button>
        <button 
          *ngIf="!isDefault(method)" 
          (click)="onDelete(method.id)"
          class="btn-danger">
          Deletar
        </button>
      </div>
    </div>
  </div>

  <div *ngIf="loading" class="loading">
    Carregando...
  </div>
</div>
```

### 5. **Select de Métodos de Pagamento no Formulário de Lançamento**

```typescript
// No componente de criação/edição de lançamento financeiro

export class FinancialReleaseFormComponent {
  paymentMethods: PaymentMethod[] = [];
  selectedPaymentMethodId: number | null = null;

  constructor(
    private paymentMethodService: PaymentMethodService,
    private financialReleaseService: FinancialReleaseService
  ) {}

  ngOnInit(): void {
    // Carrega métodos de pagamento disponíveis
    this.paymentMethodService.list().subscribe({
      next: (methods) => {
        this.paymentMethods = methods;
      },
      error: (error) => {
        console.error('Erro ao carregar métodos de pagamento:', error);
      }
    });
  }

  onSubmit(): void {
    const formData = {
      type: this.form.value.type,
      value: this.form.value.value,
      date: this.form.value.date,
      due_date: this.form.value.due_date,
      category_id: this.form.value.category_id,
      payment_method_id: this.selectedPaymentMethodId, // ← NOVO
      repetition: this.form.value.repetition,
      // ... outros campos
    };

    this.financialReleaseService.create(formData).subscribe({
      next: (response) => {
        // Sucesso
      },
      error: (error) => {
        // Erro
      }
    });
  }
}
```

```html
<!-- No template do formulário -->

<div class="form-group">
  <label>Forma de Pagamento</label>
  <select 
    [(ngModel)]="selectedPaymentMethodId" 
    name="payment_method_id"
    class="form-control">
    <option [value]="null">Selecione uma forma de pagamento (opcional)</option>
    <optgroup label="Métodos Padrão">
      <option 
        *ngFor="let method of paymentMethods.filter(m => m.finance_account_id === null)" 
        [value]="method.id">
        {{ method.name }} ({{ getTypeLabel(method.type) }})
      </option>
    </optgroup>
    <optgroup label="Métodos Personalizados">
      <option 
        *ngFor="let method of paymentMethods.filter(m => m.finance_account_id !== null)" 
        [value]="method.id">
        {{ method.name }} ({{ getTypeLabel(method.type) }})
      </option>
    </optgroup>
  </select>
</div>
```

### 6. **Modal de Criação/Edição**

```typescript
// Componente para criar/editar método de pagamento

export class PaymentMethodFormComponent {
  form: FormGroup;
  isEditing = false;
  methodId: number | null = null;

  types: PaymentMethodType[] = ['cash', 'pix', 'debit', 'credit_card', 'wallet', 'other'];

  constructor(
    private fb: FormBuilder,
    private paymentMethodService: PaymentMethodService
  ) {
    this.form = this.fb.group({
      name: ['', [Validators.required, Validators.maxLength(255)]],
      type: ['other', Validators.required],
      is_active: [true]
    });
  }

  onSubmit(): void {
    if (this.form.valid) {
      const formData = this.form.value;

      if (this.isEditing && this.methodId) {
        this.paymentMethodService.update(this.methodId, formData).subscribe({
          next: () => {
            // Sucesso
            this.close();
          },
          error: (error) => {
            if (error.status === 422) {
              alert('Já existe uma forma de pagamento com este nome.');
            } else {
              alert('Erro ao atualizar forma de pagamento');
            }
          }
        });
      } else {
        this.paymentMethodService.create(formData).subscribe({
          next: () => {
            // Sucesso
            this.close();
          },
          error: (error) => {
            if (error.status === 422) {
              alert('Já existe uma forma de pagamento com este nome.');
            } else {
              alert('Erro ao criar forma de pagamento');
            }
          }
        });
      }
    }
  }

  close(): void {
    // Fechar modal
  }
}
```

---

## 🎨 Exemplos de Uso

### Exemplo 1: Criar método personalizado "Cartão Itaú"

```typescript
this.paymentMethodService.create({
  name: 'Cartão Itaú',
  type: 'credit_card',
  is_active: true
}).subscribe({
  next: (response) => {
    console.log('Método criado:', response.data);
  }
});
```

### Exemplo 2: Listar apenas cartões de crédito

```typescript
this.paymentMethodService.list('credit_card').subscribe({
  next: (methods) => {
    console.log('Cartões de crédito:', methods);
  }
});
```

### Exemplo 3: Criar lançamento com forma de pagamento

```typescript
const releaseData = {
  type: 'expense',
  value: 100.00,
  date: '2026-01-18',
  due_date: '2026-02-05',
  category_id: 14,
  payment_method_id: 5,  // ← Cartão Itaú
  repetition: 'only'
};

this.financialReleaseService.create(releaseData).subscribe({
  next: (response) => {
    console.log('Lançamento criado com forma de pagamento:', response);
  }
});
```

---

## ⚠️ Regras de Negócio Importantes

1. **Métodos Padrão (Globais)**
   - Não podem ser editados
   - Não podem ser desativados
   - Não podem ser deletados
   - `finance_account_id === null`

2. **Métodos Personalizados**
   - Podem ser editados, desativados e deletados
   - Pertencem a uma conta financeira específica
   - `finance_account_id !== null`

3. **Soft Delete**
   - Métodos deletados não aparecem na listagem
   - Não podem ser deletados se estiverem em uso
   - Use `disable` se o método estiver em uso

4. **Listagem**
   - Retorna apenas métodos ativos (`is_active = true`)
   - Retorna métodos globais + métodos da conta atual
   - Métodos deletados não aparecem automaticamente

---

## 🔄 Fluxo Completo

```
1. Usuário cria método personalizado → POST /payment-methods
2. Método aparece na listagem → GET /payment-methods
3. Usuário seleciona método no formulário de lançamento
4. Lançamento é criado com payment_method_id → POST /financial_release
5. Método pode ser editado → PUT /payment-methods/{id}
6. Método pode ser desativado → PATCH /payment-methods/{id}/disable
7. Método pode ser deletado (se não estiver em uso) → DELETE /payment-methods/{id}
```

---

## 📝 Checklist de Implementação

- [ ] Criar interface `PaymentMethod` no Angular
- [ ] Criar service `PaymentMethodService`
- [ ] Criar componente de listagem de métodos
- [ ] Criar modal de criação/edição de métodos
- [ ] Adicionar campo `payment_method_id` no formulário de lançamento
- [ ] Adicionar select de métodos no formulário
- [ ] Implementar ações: editar, desativar, deletar
- [ ] Filtrar por tipo (opcional)
- [ ] Tratar erros específicos (422, 403)
- [ ] Exibir métodos padrão vs personalizados visualmente

---

## 🎯 Tipos de Métodos Disponíveis

| Tipo | Descrição | Exemplo |
|------|-----------|---------|
| `cash` | Dinheiro físico | Dinheiro |
| `pix` | Pix | Pix |
| `debit` | Cartão de débito | Débito |
| `credit_card` | Cartão de crédito | Cartão Itaú, Cartão Nubank |
| `wallet` | Carteira digital | PicPay, Mercado Pago |
| `other` | Outro | Boleto, Transferência |

---

## 🚀 Próximos Passos (Evolução Futura)

A estrutura está preparada para evoluir para:
- Cartões de crédito com fatura (invoice)
- Datas de fechamento e vencimento de fatura
- Limites de crédito por método
- Relatórios por forma de pagamento

---

**Dúvidas? Consulte o backend em:** `app/Http/Controllers/PaymentMethodController.php`
