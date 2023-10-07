<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class DateParser
{
    public static function parseDateByPeriodicity(array $data)
    {
        $periodicity = Arr::get($data, 'periodicity');

        $methods = [
            'daily' => fn ($value) => DateParser::dateForDaily($value),
            'weekly' => fn ($value) => DateParser::dateForWeekly($value),
            'monthly' => fn ($value) => DateParser::dateForMonthly($value),
            'annual' => fn ($value) => DateParser::dateForAnnual($value)
        ];
        return $methods[$periodicity]($data);
    }

    public static function dateForDaily(array $data)
    {

        $initialDate = Arr::get($data, 'date');
        $portion = Arr::get($data, 'portion');
        $number_installments_repetition = Arr::get($data, 'number_installments_repetition');

        $dateStart = Carbon::createFromFormat('Y-m-d', $initialDate);

        if ($portion > 0) {
            $dateStart->addDay($portion * $number_installments_repetition);
        }

        return $dateStart->toDateString();
    }

    public static function dateForWeekly(array $data)
    {
        $initialDate = Arr::get($data, 'date');
        $portion = Arr::get($data, 'portion');
        $number_installments_repetition = Arr::get($data, 'number_installments_repetition');

        $dateStart = Carbon::createFromFormat('Y-m-d', $initialDate);

        if ($portion > 0) {
            $dateStart->addDay(($portion * $number_installments_repetition) * 7);
        }

        return $dateStart->toDateString();
    }

    public static function dateForMonthly(array $data)
    {
        $initialDate = Arr::get($data, 'date');
        $portion = Arr::get($data, 'portion');
        $number_installments_repetition = Arr::get($data, 'number_installments_repetition');

        $dateStart = Carbon::createFromFormat('Y-m-d', $initialDate);

        if ($portion > 0) {
            $dateStart->addMonth($portion * $number_installments_repetition);
        }

        return $dateStart->toDateString();
    }

    public static function dateForAnnual(array $data)
    {
        $initialDate = Arr::get($data, 'date');
        $portion = Arr::get($data, 'portion');
        $number_installments_repetition = Arr::get($data, 'number_installments_repetition');

        $dateStart = Carbon::createFromFormat('Y-m-d', $initialDate);

        if ($portion > 0) {
            $dateStart->addYear($portion * $number_installments_repetition);
        }

        return $dateStart->toDateString();
    }

}
