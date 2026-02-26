# Mapeamento do Banco de Dados - Sistema Financeiro

## 📊 Visão Geral

Este documento descreve a estrutura completa das tabelas do banco de dados e seus relacionamentos.

---

## 🗂️ Tabelas Principais

### 1. **users** (Usuários)
**Descrição:** Armazena os usuários do sistema.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `name` | VARCHAR | Nome do usuário |
| `email` | VARCHAR (UNIQUE) | Email do usuário |
| `email_verified_at` | TIMESTAMP (NULL) | Data de verificação do email |
| `password` | VARCHAR | Senha criptografada |
| `remember_token` | VARCHAR (NULL) | Token de "lembrar-me" |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

**Relacionamentos:**
- `hasMany` → `FinanceAccount` (ownedFinanceAccounts) - Finanças onde é owner
- `belongsToMany` → `FinanceAccount` (financeAccounts) - Finanças compartilhadas (via pivot `finance_account_users`)
- `hasMany` → `FinancialRelease` (createdFinancialReleases) - Lançamentos criados (auditoria)
- `hasMany` → `Category` (categories) - Categorias customizadas

---

### 2. **finance_accounts** (Contas Financeiras)
**Descrição:** Representa uma conta financeira compartilhada entre múltiplos usuários.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `name` | VARCHAR | Nome da conta financeira |
| `owner_id` | BIGINT UNSIGNED (FK) | ID do usuário dono (cascade delete) |
| `plan_id` | BIGINT UNSIGNED (FK, NULL) | ID do plano (Free/Pro) |
| `subscription_status` | ENUM | Status: 'active', 'inactive', 'expired', 'cancelled' |
| `subscription_ends_at` | TIMESTAMP (NULL) | Data de expiração da assinatura |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

**Relacionamentos:**
- `belongsTo` → `User` (owner) - Dono da conta
- `belongsTo` → `Plan` (plan) - Plano associado
- `belongsToMany` → `User` (users) - Usuários com acesso (via pivot `finance_account_users`)
- `hasMany` → `FinancialRelease` (financialReleases) - Lançamentos financeiros
- `hasMany` → `FinanceAccountInvite` (invites) - Convites pendentes

---

### 3. **finance_account_users** (Pivot: Usuários ↔ Contas Financeiras)
**Descrição:** Tabela pivot que relaciona usuários com contas financeiras e define seus papéis.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `finance_account_id` | BIGINT UNSIGNED (FK) | ID da conta financeira (cascade delete) |
| `user_id` | BIGINT UNSIGNED (FK) | ID do usuário (cascade delete) |
| `role` | ENUM | Papel: 'owner', 'editor', 'viewer' (default: 'viewer') |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

**Constraints:**
- `UNIQUE(finance_account_id, user_id)` - Um usuário só pode ter um papel por conta

---

### 4. **finance_account_invites** (Convites para Contas Financeiras)
**Descrição:** Armazena convites enviados para usuários se juntarem a uma conta financeira.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `finance_account_id` | BIGINT UNSIGNED (FK) | ID da conta financeira (cascade delete) |
| `email` | VARCHAR | Email do convidado |
| `role` | ENUM | Papel oferecido: 'owner', 'editor', 'viewer' |
| `token` | VARCHAR (UNIQUE) | Token único do convite |
| `accepted_at` | TIMESTAMP (NULL) | Data de aceitação (NULL = pendente) |
| `expires_at` | TIMESTAMP | Data de expiração do convite |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

**Índices:**
- `INDEX(token)` - Para busca rápida por token
- `INDEX(email)` - Para busca por email

**Relacionamentos:**
- `belongsTo` → `FinanceAccount` (financeAccount) - Conta financeira do convite

---

### 5. **plans** (Planos)
**Descrição:** Define os planos disponíveis (Free, Pro, etc.).

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `name` | VARCHAR | Nome do plano (ex: 'Free', 'Pro') |
| `price` | DECIMAL(10,2) | Preço do plano (default: 0) |
| `max_users` | INTEGER | Máximo de usuários permitidos (default: 1) |
| `features` | JSON (NULL) | Features habilitadas no plano |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

**Relacionamentos:**
- `hasMany` → `FinanceAccount` (financeAccounts) - Contas financeiras com este plano

---

### 6. **categories** (Categorias)
**Descrição:** Categorias para classificar lançamentos financeiros. Podem ser padrão (globais) ou customizadas por usuário.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `title` | VARCHAR | Nome da categoria |
| `type` | ENUM | Tipo: 'expense' ou 'revenue' |
| `user_id` | BIGINT UNSIGNED (FK, NULL) | ID do usuário (NULL = categoria padrão/global) |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

**Relacionamentos:**
- `belongsTo` → `User` (user) - Usuário dono (NULL = categoria padrão)
- `hasMany` → `FinancialRelease` (financialReleases) - Lançamentos desta categoria

**Regras de Negócio:**
- Se `user_id` é NULL → Categoria padrão (disponível para todos)
- Se `user_id` não é NULL → Categoria customizada do usuário

---

### 7. **financial_releases** (Lançamentos Financeiros)
**Descrição:** Registra receitas e despesas financeiras.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `type` | ENUM | Tipo: 'expense' ou 'revenue' |
| `value` | DECIMAL(11,2) | Valor do lançamento |
| `date` | DATE | Data de competência |
| `due_date` | DATE (NOT NULL) | Data de vencimento |
| `payment_date` | DATE (NULL) | Data de pagamento efetivo |
| `status` | ENUM | Status: 'pending', 'paid', 'overdue' (calculado automaticamente) |
| `descrition` | LONGTEXT (NULL) | Descrição do lançamento |
| `observation` | LONGTEXT (NULL) | Observações adicionais |
| `repetition` | ENUM | Repetição: 'only', 'installments', 'fixed' |
| `portion` | VARCHAR (NULL) | Número da parcela (ex: "1/12") |
| `category_id` | BIGINT UNSIGNED (FK) | ID da categoria |
| `finance_account_id` | BIGINT UNSIGNED (FK) | ID da conta financeira (cascade delete) |
| `created_by` | BIGINT UNSIGNED (FK) | ID do usuário que criou (auditoria, cascade delete) |
| `installment_id` | BIGINT UNSIGNED (FK, NULL) | ID da parcela (se aplicável) |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

**Relacionamentos:**
- `belongsTo` → `FinanceAccount` (financeAccount) - Conta financeira
- `belongsTo` → `Category` (category) - Categoria
- `belongsTo` → `User` (creator) - Usuário que criou (auditoria)
- `belongsTo` → `Installment` (installment) - Parcela (se aplicável)

**Regras de Negócio:**
- `status` é calculado automaticamente:
  - `paid`: se `payment_date` não é NULL
  - `overdue`: se `payment_date` é NULL e `due_date` < hoje
  - `pending`: se `payment_date` é NULL e `due_date` >= hoje

---

### 8. **installments** (Parcelas)
**Descrição:** Agrupa lançamentos que fazem parte de uma mesma parcela.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `type` | ENUM | Tipo: 'installments' ou 'fixed' |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

**Relacionamentos:**
- `hasMany` → `FinancialRelease` (financialReleases) - Lançamentos desta parcela

---

### 9. **types** (Tipos)
**Descrição:** Tabela de tipos (atualmente não utilizada, mantida para compatibilidade).

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

---

## 🔗 Diagrama de Relacionamentos

```
┌─────────────┐
│    users    │
└──────┬──────┘
       │
       │ 1:N (owner_id)
       │
       ▼
┌─────────────────────┐         ┌──────────────┐
│ finance_accounts    │────────▶│    plans     │
└──────┬──────────────┘  N:1    └──────────────┘
       │ (plan_id)
       │
       │ 1:N (finance_account_id)
       │
       ▼
┌─────────────────────┐
│ financial_releases  │
└──────┬──────────────┘
       │
       │ N:1 (category_id)      ┌──────────────┐
       ├────────────────────────▶│  categories │
       │                         └──────┬───────┘
       │                                │
       │ N:1 (created_by)              │ N:1 (user_id, NULL)
       │                                │
       │ N:1 (installment_id, NULL)    │
       │                                │
       ▼                                ▼
┌──────────────┐              ┌─────────────┐
│ installments │              │    users    │
└──────────────┘              └─────────────┘

┌─────────────────────┐
│ finance_accounts    │
└──────┬──────────────┘
       │
       │ 1:N (finance_account_id)
       │
       ▼
┌──────────────────────────┐
│ finance_account_invites │
└──────────────────────────┘

┌─────────────┐                    ┌─────────────────────┐
│    users    │◀───────────────────│ finance_accounts   │
└─────────────┘  N:N (via pivot)   └─────────────────────┘
                    │
                    │
                    ▼
        ┌──────────────────────────┐
        │ finance_account_users     │
        │ (pivot: role)             │
        └──────────────────────────┘
```

---

## 📋 Resumo dos Relacionamentos

### Relacionamentos One-to-Many (1:N)

1. **User → FinanceAccount** (ownedFinanceAccounts)
   - Um usuário pode ser dono de múltiplas contas financeiras
   - Campo: `finance_accounts.owner_id`

2. **FinanceAccount → FinancialRelease** (financialReleases)
   - Uma conta financeira tem múltiplos lançamentos
   - Campo: `financial_releases.finance_account_id`

3. **FinanceAccount → FinanceAccountInvite** (invites)
   - Uma conta financeira pode ter múltiplos convites
   - Campo: `finance_account_invites.finance_account_id`

4. **Plan → FinanceAccount** (financeAccounts)
   - Um plano pode ser usado por múltiplas contas
   - Campo: `finance_accounts.plan_id`

5. **Category → FinancialRelease** (financialReleases)
   - Uma categoria pode ter múltiplos lançamentos
   - Campo: `financial_releases.category_id`

6. **User → Category** (categories)
   - Um usuário pode ter múltiplas categorias customizadas
   - Campo: `categories.user_id` (NULL = categoria padrão)

7. **User → FinancialRelease** (createdFinancialReleases)
   - Um usuário pode criar múltiplos lançamentos (auditoria)
   - Campo: `financial_releases.created_by`

8. **Installment → FinancialRelease** (financialReleases)
   - Uma parcela pode ter múltiplos lançamentos
   - Campo: `financial_releases.installment_id` (NULL = não é parcela)

### Relacionamentos Many-to-Many (N:N)

1. **User ↔ FinanceAccount** (via `finance_account_users`)
   - Múltiplos usuários podem ter acesso a múltiplas contas
   - Pivot: `finance_account_users` com campo `role` ('owner', 'editor', 'viewer')
   - Unique constraint: `(finance_account_id, user_id)`

### Relacionamentos Many-to-One (N:1)

1. **FinancialRelease → FinanceAccount**
   - Múltiplos lançamentos pertencem a uma conta financeira

2. **FinancialRelease → Category**
   - Múltiplos lançamentos pertencem a uma categoria

3. **FinancialRelease → User** (creator)
   - Múltiplos lançamentos são criados por um usuário

4. **FinancialRelease → Installment**
   - Múltiplos lançamentos podem fazer parte de uma parcela

5. **FinanceAccount → User** (owner)
   - Múltiplas contas podem ter o mesmo dono

6. **FinanceAccount → Plan**
   - Múltiplas contas podem usar o mesmo plano

7. **Category → User**
   - Múltiplas categorias podem pertencer a um usuário (ou NULL para padrão)

---

## 🔐 Constraints e Índices Importantes

### Foreign Keys (Cascade Delete)

- `finance_accounts.owner_id` → `users.id` (CASCADE)
- `finance_account_users.finance_account_id` → `finance_accounts.id` (CASCADE)
- `finance_account_users.user_id` → `users.id` (CASCADE)
- `finance_account_invites.finance_account_id` → `finance_accounts.id` (CASCADE)
- `financial_releases.finance_account_id` → `finance_accounts.id` (CASCADE)
- `financial_releases.created_by` → `users.id` (CASCADE)
- `financial_releases.category_id` → `categories.id` (CASCADE)
- `categories.user_id` → `users.id` (CASCADE)

### Unique Constraints

- `users.email` (UNIQUE)
- `finance_account_users(finance_account_id, user_id)` (UNIQUE)
- `finance_account_invites.token` (UNIQUE)

### Índices

- `finance_account_invites.token` (INDEX)
- `finance_account_invites.email` (INDEX)

---

## 📝 Observações Importantes

1. **Multi-tenancy**: O sistema usa `finance_account_id` como filtro padrão para isolar dados entre contas financeiras.

2. **Categorias Globais**: Categorias com `user_id = NULL` são padrão e disponíveis para todos os usuários.

3. **Status Automático**: O campo `status` em `financial_releases` é calculado automaticamente baseado em `payment_date` e `due_date`.

4. **Auditoria**: O campo `created_by` em `financial_releases` rastreia quem criou cada lançamento.

5. **Planos**: O sistema suporta planos Free/Pro com limites de usuários e features configuráveis via JSON.

6. **Convites**: O sistema de convites permite adicionar usuários a contas financeiras com diferentes papéis (owner, editor, viewer).

---

## 🎯 Fluxo de Dados Principal

1. **Usuário** cria/possui uma **Conta Financeira**
2. **Conta Financeira** está associada a um **Plano** (Free/Pro)
3. **Usuários** são adicionados à **Conta Financeira** via pivot com **Papel**
4. **Lançamentos Financeiros** são criados vinculados à **Conta Financeira**
5. **Lançamentos** são categorizados usando **Categorias** (padrão ou customizadas)
6. **Lançamentos** podem fazer parte de **Parcelas** (installments)

---

**Última atualização:** 2026-01-18
**Versão do sistema:** 1.0
