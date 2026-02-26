<?php

namespace App\Services\Installment;

use App\Helpers\DateParser;
use App\Models\Installment;
use App\Repositories\Installment\InstallmentRepository;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class InstallmentService extends BaseService implements InstallmentServiceInterface
{
    public function __construct(
        InstallmentRepository $repository
    ) {
        parent::__construct($repository);
    }

    /**
     * Cria lançamentos baseado no tipo de repetição
     *
     * @param array $data
     * @return array
     */
    public function createInstallments(array $data): array
    {
        // Lançamento único - retorna apenas um registro
        if ($data['repetition'] === 'only') {
            return $this->createSingleRelease($data);
        }

        // Parcelamento - divide valor e cria installments
        // Disponível em todos os planos (Free e Pro)
        if ($data['repetition'] === 'installments') {
            return $this->createInstallmentReleases($data);
        }

        // Recorrente - mantém valor e não cria installments
        // Recorrências são permitidas em todos os planos (não requerem feature)
        if ($data['repetition'] === 'fixed') {
            return $this->createFixedReleases($data);
        }

        // Fallback para lançamento único
        return $this->createSingleRelease($data);
    }

    /**
     * Cria um lançamento único (repetition = only)
     */
    private function createSingleRelease(array $data): array
    {
        return [
            'type' => $data['type'],
            'value' => $data['value'],
            'date' => $data['date'],
            'due_date' => $data['due_date'],
            'payment_date' => $data['payment_date'] ?? null,
            'descrition' => $data['descrition'] ?? null,
            'observation' => $data['observation'] ?? null,
            'repetition' => 'only',
            'portion' => null,
            'category_id' => $data['category_id'],
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'finance_account_id' => $data['finance_account_id'],
            'created_by' => $data['created_by'],
            'installment_id' => null,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Cria lançamentos parcelados (repetition = installments)
     * Divide o valor total pela quantidade de parcelas
     * Cria registro em installments
     * Sempre avança mês a mês (não usa periodicity)
     */
    private function createInstallmentReleases(array $data): array
    {
        $quantity = (int) $data['number_installments_repetition'];
        $totalValue = (float) $data['value'];

        // Divide o valor total pela quantidade de parcelas
        $valuePerInstallment = round($totalValue / $quantity, 2);

        // Cria registro na tabela installments
        $installment = Installment::create([
            'type' => 'installments'
        ]);

        $releases = [];
        // Armazena as strings de data para criar novas instâncias a cada iteração
        $initialDateStr = $data['date'];
        $initialDueDateStr = $data['due_date'];

        for ($i = 0; $i < $quantity; $i++) {
            // Calcula data de competência (date) - avança mês a mês a partir da data inicial
            // Cria uma nova instância do Carbon a cada iteração usando copy() antes de addMonths
            $competenceDate = Carbon::createFromFormat('Y-m-d', $initialDateStr)->copy();
            $competenceDate->addMonths($i);

            // Calcula data de vencimento (due_date) - avança mês a mês mantendo o mesmo dia do vencimento inicial
            // Cria uma nova instância do Carbon a cada iteração usando copy() antes de addMonths
            $dueDate = Carbon::createFromFormat('Y-m-d', $initialDueDateStr)->copy();
            $dueDate->addMonths($i);

            $releases[] = [
                'type' => $data['type'],
                'value' => $valuePerInstallment, // Valor dividido
                'date' => $competenceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'payment_date' => null, // Sempre null inicialmente
                'descrition' => $data['descrition'] ?? null,
                'observation' => $data['observation'] ?? null,
                'repetition' => 'installments',
                'portion' => ($i + 1) . '/' . $quantity, // Formato: "1/12", "2/12", etc.
                'category_id' => $data['category_id'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'finance_account_id' => $data['finance_account_id'],
                'created_by' => $data['created_by'],
                'installment_id' => $installment->id, // Vinculado ao installment criado
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];
        }

        return $releases;
    }

    /**
     * Cria lançamentos recorrentes (repetition = fixed)
     * Mantém o valor original (não divide)
     * NÃO cria registro em installments
     */
    private function createFixedReleases(array $data): array
    {
        $quantity = (int) $data['number_repetition'];
        $value = (float) $data['value']; // Mantém valor original

        $releases = [];
        $initialDate = Carbon::createFromFormat('Y-m-d', $data['date']);
        $initialDueDate = Carbon::createFromFormat('Y-m-d', $data['due_date']);

        // Determina a periodicity (padrão: monthly)
        $periodicity = $data['periodicity'] ?? 'monthly';

        for ($i = 0; $i < $quantity; $i++) {
            // Calcula data de competência baseado na periodicity
            // O método já clona internamente, então não precisa clonar aqui
            $competenceDate = $this->calculateDateByPeriodicity(
                $initialDate,
                $i,
                $periodicity
            );

            // Calcula data de vencimento baseado na periodicity
            // O método já clona internamente, então não precisa clonar aqui
            $dueDate = $this->calculateDateByPeriodicity(
                $initialDueDate,
                $i,
                $periodicity
            );

            $releases[] = [
                'type' => $data['type'],
                'value' => $value, // Valor original, não dividido
                'date' => $competenceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'payment_date' => null, // Sempre null inicialmente
                'descrition' => $data['descrition'] ?? null,
                'observation' => $data['observation'] ?? null,
                'repetition' => 'fixed',
                'portion' => ($i + 1) . '/' . $quantity, // Formato: "1/12", "2/12", etc. (retornado para o frontend)
                'category_id' => $data['category_id'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'finance_account_id' => $data['finance_account_id'],
                'created_by' => $data['created_by'],
                'installment_id' => null, // NÃO cria installments
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];
        }

        return $releases;
    }

    /**
     * Calcula data baseado na periodicity
     *
     * @param Carbon $baseDate Data base
     * @param int $index Índice da parcela (0-based)
     * @param string $periodicity daily, weekly, monthly, annual
     * @return Carbon
     */
    private function calculateDateByPeriodicity(Carbon $baseDate, int $index, string $periodicity): Carbon
    {
        if ($index === 0) {
            return clone $baseDate;
        }

        // Cria um clone para não modificar a data base
        $calculatedDate = clone $baseDate;

        switch ($periodicity) {
            case 'daily':
                return $calculatedDate->addDays($index);
            case 'weekly':
                return $calculatedDate->addWeeks($index);
            case 'monthly':
                return $calculatedDate->addMonths($index);
            case 'annual':
                return $calculatedDate->addYears($index);
            default:
                // Default: monthly
                return $calculatedDate->addMonths($index);
        }
    }
}
