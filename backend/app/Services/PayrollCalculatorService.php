<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\LaborRule;
use App\Models\Shift;
use Carbon\CarbonImmutable;
use RuntimeException;

class PayrollCalculatorService
{
    private const MONTHLY_REFERENCE_HOURS = 240;

    /**
     * @var array<string, LaborRule>
     */
    private array $rulesCache = [];

    /**
     * @var array<string, bool>
     */
    private array $holidayCache = [];

    public function __construct(
        private readonly ContractResolver $contractResolver,
    ) {
    }

    public function calculate(Shift $shift): array
    {
        $this->rulesCache = [];
        $this->holidayCache = [];

        $shift->loadMissing(['employee.contracts']);

        $start = $this->asImmutable($shift->fecha_inicio);
        $end = $this->asImmutable($shift->fecha_fin);

        $this->ensureValidShift($shift, $start, $end);

        $result = $this->emptyResult();

        foreach ($this->buildDaySegments($start, $end) as $daySegment) {
            $context = $this->resolveDayContext($daySegment['start']);
            $workedMinutes = 0;

            foreach ($this->buildScheduleBlocks($daySegment['start'], $daySegment['end'], $context['rule']) as $scheduleBlock) {
                $payBlocks = $this->splitBlockByDailyLimit(
                    $scheduleBlock,
                    $workedMinutes,
                    (float) $context['rule']->horas_max_diarias,
                    $context
                );

                foreach ($payBlocks as $payBlock) {
                    $this->accumulateResult($result, $this->calculatePayBlock($shift, $context['rule'], $payBlock));
                }

                $workedMinutes += $scheduleBlock['minutes'];
            }
        }

        return $this->normalizeResult($result);
    }

    /**
     * @return array<int, array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function buildDaySegments(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $segments = [];
        $cursor = $start;

        while ($cursor->lt($end)) {
            $segmentEnd = $this->minDate($end, $cursor->startOfDay()->addDay());
            $segments[] = [
                'start' => $cursor,
                'end' => $segmentEnd,
            ];
            $cursor = $segmentEnd;
        }

        return $segments;
    }

    /**
     * @return array<int, array{
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     minutes: int,
     *     is_daytime: bool
     * }>
     */
    private function buildScheduleBlocks(CarbonImmutable $start, CarbonImmutable $end, LaborRule $rule): array
    {
        $dayStart = $this->dateWithTime($start, (string) $rule->hora_diurna_inicio);
        $nightStart = $this->dateWithTime($start, (string) $rule->hora_nocturna_inicio);

        if (! $dayStart->lt($nightStart)) {
            throw new RuntimeException('La regla laboral debe tener una hora diurna anterior a la hora nocturna.');
        }

        $boundaries = [$start, $end];

        foreach ([$dayStart, $nightStart] as $boundary) {
            if ($boundary->gt($start) && $boundary->lt($end)) {
                $boundaries[] = $boundary;
            }
        }

        usort($boundaries, fn (CarbonImmutable $left, CarbonImmutable $right) => $left->getTimestamp() <=> $right->getTimestamp());

        $blocks = [];

        for ($index = 0; $index < count($boundaries) - 1; $index++) {
            $blockStart = $boundaries[$index];
            $blockEnd = $boundaries[$index + 1];

            if (! $blockStart->lt($blockEnd)) {
                continue;
            }

            $blocks[] = [
                'start' => $blockStart,
                'end' => $blockEnd,
                'minutes' => $blockStart->diffInMinutes($blockEnd),
                'is_daytime' => $this->isDaytime($blockStart, $dayStart, $nightStart),
            ];
        }

        return $blocks;
    }

    /**
     * @param array{
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     minutes: int,
     *     is_daytime: bool
     * } $block
     * @param array{
     *     rule: LaborRule,
     *     is_sunday: bool,
     *     is_holiday: bool
     * } $context
     * @return array<int, array{
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     minutes: int,
     *     is_daytime: bool,
     *     is_extra: bool,
     *     is_sunday: bool,
     *     is_holiday: bool
     * }>
     */
    private function splitBlockByDailyLimit(array $block, int $workedMinutes, float $maxDailyHours, array $context): array
    {
        $segments = [];
        $limitMinutes = $this->hoursToMinutes($maxDailyHours);
        $regularMinutesAvailable = max($limitMinutes - $workedMinutes, 0);
        $regularMinutes = min($block['minutes'], $regularMinutesAvailable);

        if ($regularMinutes > 0) {
            $segments[] = $this->buildPayBlock(
                $block,
                $block['start'],
                $block['start']->addMinutes($regularMinutes),
                $regularMinutes,
                false,
                $context
            );
        }

        $extraMinutes = $block['minutes'] - $regularMinutes;

        if ($extraMinutes > 0) {
            $extraStart = $block['start']->addMinutes($regularMinutes);

            $segments[] = $this->buildPayBlock(
                $block,
                $extraStart,
                $block['end'],
                $extraMinutes,
                true,
                $context
            );
        }

        return $segments;
    }

    /**
     * @param array{
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     minutes: int,
     *     is_daytime: bool
     * } $sourceBlock
     * @param array{
     *     rule: LaborRule,
     *     is_sunday: bool,
     *     is_holiday: bool
     * } $context
     * @return array{
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     minutes: int,
     *     is_daytime: bool,
     *     is_extra: bool,
     *     is_sunday: bool,
     *     is_holiday: bool
     * }
     */
    private function buildPayBlock(
        array $sourceBlock,
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $minutes,
        bool $isExtra,
        array $context
    ): array {
        return [
            'start' => $start,
            'end' => $end,
            'minutes' => $minutes,
            'is_daytime' => $sourceBlock['is_daytime'],
            'is_extra' => $isExtra,
            'is_sunday' => $context['is_sunday'],
            'is_holiday' => $context['is_holiday'],
        ];
    }

    /**
     * @param array{
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     minutes: int,
     *     is_daytime: bool,
     *     is_extra: bool,
     *     is_sunday: bool,
     *     is_holiday: bool
     * } $block
     * @return array{
     *     total: float,
     *     horas_diurnas: float,
     *     horas_nocturnas: float,
     *     horas_extra_diurnas: float,
     *     horas_extra_nocturnas: float,
     *     detalle: array<string, mixed>
     * }
     */
    private function calculatePayBlock(Shift $shift, LaborRule $rule, array $block): array
    {
        $hourlyRate = $this->resolveHourlyRate($shift, $rule);
        $hours = $this->minutesToHours($block['minutes']);
        $premiums = $this->resolvePremiums($rule, $block);
        $premiumRate = array_sum($premiums);
        $baseValue = round($hours * $hourlyRate, 2);
        $totalValue = round($hours * $hourlyRate * (1 + $premiumRate), 2);

        return [
            'total' => $totalValue,
            'horas_diurnas' => $block['is_daytime'] && ! $block['is_extra'] ? $hours : 0.0,
            'horas_nocturnas' => ! $block['is_daytime'] && ! $block['is_extra'] ? $hours : 0.0,
            'horas_extra_diurnas' => $block['is_daytime'] && $block['is_extra'] ? $hours : 0.0,
            'horas_extra_nocturnas' => ! $block['is_daytime'] && $block['is_extra'] ? $hours : 0.0,
            'detalle' => [
                'inicio' => $block['start']->toDateTimeString(),
                'fin' => $block['end']->toDateTimeString(),
                'fecha' => $block['start']->toDateString(),
                'horas' => round($hours, 4),
                'jornada' => $block['is_daytime'] ? 'diurna' : 'nocturna',
                'tipo_hora' => $block['is_extra'] ? 'extra' : 'normal',
                'es_dominical' => $block['is_sunday'],
                'es_festivo' => $block['is_holiday'],
                'valor_hora_base' => round($hourlyRate, 2),
                'valor_base' => $baseValue,
                'porcentaje_recargo_total' => round($premiumRate * 100, 2),
                'recargos_aplicados' => $this->formatPremiums($premiums),
                'valor_total' => $totalValue,
            ],
        ];
    }

    /**
     * @param array{
     *     is_daytime: bool,
     *     is_extra: bool,
     *     is_sunday: bool,
     *     is_holiday: bool
     * } $block
     * @return array<string, float>
     */
    private function resolvePremiums(LaborRule $rule, array $block): array
    {
        $premiums = [];

        if ($block['is_extra']) {
            $premiums[$block['is_daytime'] ? 'extra_diurna' : 'extra_nocturna'] = $this->percentageToRate(
                $block['is_daytime'] ? $rule->extra_diurna : $rule->extra_nocturna
            );
        } elseif (! $block['is_daytime']) {
            $premiums['recargo_nocturno'] = $this->percentageToRate($rule->recargo_nocturno);
        }

        if ($block['is_holiday']) {
            $premiums['recargo_festivo'] = $this->percentageToRate($rule->recargo_dominical);
        } elseif ($block['is_sunday']) {
            $premiums['recargo_dominical'] = $this->percentageToRate($rule->recargo_dominical);
        }

        return $premiums;
    }

    /**
     * @param array<string, float> $premiums
     * @return array<string, float>
     */
    private function formatPremiums(array $premiums): array
    {
        return array_map(
            static fn (float $rate): float => round($rate * 100, 2),
            $premiums
        );
    }

    /**
     * @param array{
     *     total: float,
     *     horas_diurnas: float,
     *     horas_nocturnas: float,
     *     horas_extra_diurnas: float,
     *     horas_extra_nocturnas: float,
     *     detalle: array<int, array<string, mixed>>
     * } $result
     * @param array{
     *     total: float,
     *     horas_diurnas: float,
     *     horas_nocturnas: float,
     *     horas_extra_diurnas: float,
     *     horas_extra_nocturnas: float,
     *     detalle: array<string, mixed>
     * } $blockResult
     */
    private function accumulateResult(array &$result, array $blockResult): void
    {
        $result['total'] += $blockResult['total'];
        $result['horas_diurnas'] += $blockResult['horas_diurnas'];
        $result['horas_nocturnas'] += $blockResult['horas_nocturnas'];
        $result['horas_extra_diurnas'] += $blockResult['horas_extra_diurnas'];
        $result['horas_extra_nocturnas'] += $blockResult['horas_extra_nocturnas'];
        $result['detalle'][] = $blockResult['detalle'];
    }

    /**
     * @return array{
     *     total: float,
     *     horas_diurnas: float,
     *     horas_nocturnas: float,
     *     horas_extra_diurnas: float,
     *     horas_extra_nocturnas: float,
     *     detalle: array<int, array<string, mixed>>
     * }
     */
    private function emptyResult(): array
    {
        return [
            'total' => 0.0,
            'horas_diurnas' => 0.0,
            'horas_nocturnas' => 0.0,
            'horas_extra_diurnas' => 0.0,
            'horas_extra_nocturnas' => 0.0,
            'detalle' => [],
        ];
    }

    /**
     * @param array{
     *     total: float,
     *     horas_diurnas: float,
     *     horas_nocturnas: float,
     *     horas_extra_diurnas: float,
     *     horas_extra_nocturnas: float,
     *     detalle: array<int, array<string, mixed>>
     * } $result
     * @return array{
     *     total: float,
     *     horas_diurnas: float,
     *     horas_nocturnas: float,
     *     horas_extra_diurnas: float,
     *     horas_extra_nocturnas: float,
     *     detalle: array<int, array<string, mixed>>
     * }
     */
    private function normalizeResult(array $result): array
    {
        $result['total'] = round($result['total'], 2);
        $result['horas_diurnas'] = round($result['horas_diurnas'], 2);
        $result['horas_nocturnas'] = round($result['horas_nocturnas'], 2);
        $result['horas_extra_diurnas'] = round($result['horas_extra_diurnas'], 2);
        $result['horas_extra_nocturnas'] = round($result['horas_extra_nocturnas'], 2);

        return $result;
    }

    /**
     * @return array{
     *     rule: LaborRule,
     *     is_sunday: bool,
     *     is_holiday: bool
     * }
     */
    private function resolveDayContext(CarbonImmutable $date): array
    {
        return [
            'rule' => $this->resolveLaborRule($date),
            'is_sunday' => $date->dayOfWeek === CarbonImmutable::SUNDAY,
            'is_holiday' => $this->isHoliday($date),
        ];
    }

    private function resolveLaborRule(CarbonImmutable $date): LaborRule
    {
        $dateKey = $date->toDateString();

        if (isset($this->rulesCache[$dateKey])) {
            return $this->rulesCache[$dateKey];
        }

        $rule = LaborRule::query()
            ->whereDate('vigente_desde', '<=', $dateKey)
            ->orderByDesc('vigente_desde')
            ->first();

        if (! $rule instanceof LaborRule) {
            throw new RuntimeException("No existe una regla laboral vigente para la fecha {$dateKey}.");
        }

        return $this->rulesCache[$dateKey] = $rule;
    }

    private function isHoliday(CarbonImmutable $date): bool
    {
        $dateKey = $date->toDateString();

        if (array_key_exists($dateKey, $this->holidayCache)) {
            return $this->holidayCache[$dateKey];
        }

        return $this->holidayCache[$dateKey] = Holiday::query()
            ->whereDate('fecha', $dateKey)
            ->exists();
    }

    private function resolveHourlyRate(Shift $shift, LaborRule $rule): float
    {
        $maxDailyHours = (float) $rule->horas_max_diarias;

        if (! $shift->employee) {
            throw new RuntimeException('El turno debe tener un empleado asociado para calcular nomina.');
        }

        $contract = $this->contractResolver->resolve(
            $shift->employee,
            $this->asImmutable($shift->fecha_inicio)
        );

        $salary = (float) $contract->salario_base;

        if ($salary <= 0) {
            throw new RuntimeException('El contrato activo del empleado debe tener un salario_base mayor a cero.');
        }

        if ($maxDailyHours <= 0) {
            throw new RuntimeException('La regla laboral debe definir horas_max_diarias mayores a cero.');
        }

        return round($salary / self::MONTHLY_REFERENCE_HOURS, 2);
    }

    private function ensureValidShift(Shift $shift, CarbonImmutable $start, CarbonImmutable $end): void
    {
        if (! $shift->employee) {
            throw new RuntimeException('El turno debe tener un empleado asociado para calcular nomina.');
        }

        if (! $start->lt($end)) {
            throw new RuntimeException('La fecha_fin del turno debe ser posterior a fecha_inicio.');
        }
    }

    private function isDaytime(CarbonImmutable $moment, CarbonImmutable $dayStart, CarbonImmutable $nightStart): bool
    {
        return $moment->greaterThanOrEqualTo($dayStart) && $moment->lt($nightStart);
    }

    private function dateWithTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        [$hour, $minute, $second] = array_pad(explode(':', $time), 3, 0);

        return $date->setTime((int) $hour, (int) $minute, (int) $second);
    }

    private function percentageToRate(float|string $percentage): float
    {
        return ((float) $percentage) / 100;
    }

    private function minutesToHours(int $minutes): float
    {
        return $minutes / 60;
    }

    private function hoursToMinutes(float $hours): int
    {
        return (int) round($hours * 60);
    }

    private function minDate(CarbonImmutable $left, CarbonImmutable $right): CarbonImmutable
    {
        return $left->lte($right) ? $left : $right;
    }

    private function asImmutable(mixed $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value);
    }
}
