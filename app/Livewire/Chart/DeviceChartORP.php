<?php

namespace App\Livewire\Chart;

use App\Enums\IntervalFrequency;
use App\Livewire\ChartPoolDetail;
use App\Models\Pool\StateLog;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Livewire\Component;

class DeviceChartORP extends ChartWidget
{
    protected static ?string $heading = 'Sanitation (ORP)';

    public string $device;
    public array $filters = [];

    protected function getData(): array
    {
        $filters = ChartPoolDetail::extractFilter($this->filters);
        $startDate = $filters['startDate'] ?? now()->subMonth();
        $endDate = $filters['endDate'] ?? now();
        $frequency = $filters['frequency'] ?? 'Weekly';

        $frequencyEnum = IntervalFrequency::from($frequency);

        $orp = $this->getOrp($this->device);
        $data = Trend::query(StateLog::query()->where('device', $this->device));
        if ($startDate && $endDate) {
            $data = $data->between($startDate, $endDate);
        }
        $data = $data->interval($frequencyEnum->toTrendInterval())->count();
        return [
            'datasets' => [
                [
                    'label' => 'Chlorine',
                    'data' => $orp['data'],
                ],
            ],
              'labels' => $data->map(function ($value) use ($frequencyEnum) {
                if ($frequencyEnum === IntervalFrequency::Weekly) {
                    $split = explode('-', $value->date);
                    $value->date = $split[0] . '-W' . $split[1];
                }
                return Carbon::parse($value->date)->format('M d H:i');
            })->toArray(),
        ];
    }

    public function getOrp(string $device): ?array
    {
        $orp = [];
        $stateLogs = StateLog::where('device', $device)
        ->orderBy('created_at', 'asc')
        ->get()
        ->toArray();

        foreach ($stateLogs as $stateLog) {
            if (isset($stateLog['formatted_sensors']['orp'])) {
                $orp['data'][] = $stateLog['formatted_sensors']['orp']['value'];
            }
        }
        return $orp;
    }


    protected function getType(): string
    {
        return 'line';
    }
}