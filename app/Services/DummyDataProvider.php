<?php

namespace App\Services;

use App\Contracts\SecurityDataProvider;
use App\Models\SecurityAlert;

class DummyDataProvider implements SecurityDataProvider
{
    public function summary(): array
    {
        return [
            'total'    => SecurityAlert::count(),
            'critical' => SecurityAlert::where('severity', 'critical')->count(),
            'open'     => SecurityAlert::whereIn('status', ['open', 'investigating'])->count(),
            'mttr_min' => round(SecurityAlert::whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, detected_at, resolved_at)) as m')
                ->value('m') ?? 0),
        ];
    }

    public function timeline(int $days = 7): array
    {
        return SecurityAlert::where('detected_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(detected_at) as date, COUNT(*) as total')
            ->groupBy('date')->orderBy('date')->get()->toArray();
    }

    public function bySeverity(): array
    {
        return SecurityAlert::selectRaw('severity, COUNT(*) as total')
            ->groupBy('severity')->pluck('total', 'severity')->toArray();
    }

    public function topAttackTypes(int $limit = 5): array
    {
        return SecurityAlert::selectRaw('attack_type, COUNT(*) as total')
            ->groupBy('attack_type')->orderByDesc('total')->limit($limit)->get()->toArray();
    }

    public function recentAlerts(array $filters = [], int $limit = 20): array
    {
        return SecurityAlert::query()
            ->when($filters['severity'] ?? null, fn ($q, $v) => $q->where('severity', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest('detected_at')->limit($limit)->get()->toArray();
    }

        public function vulnSummary(): array
    {
        return ['total' => 0, 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'other' => 0];
    }

    public function vulnerabilities(array $filters = [], int $limit = 20): array
    {
        return [];
    }
        public function alertsPage(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $p = SecurityAlert::query()
            ->when($filters['severity'] ?? null, fn ($q, $v) => $q->where('severity', $v))
            ->latest('detected_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => collect($p->items())->map(fn ($a) => [
                'detected_at' => $a->detected_at,
                'agent'       => $a->source_system,
                'rule'        => $a->attack_type,
                'level'       => null,
                'severity'    => $a->severity,
            ])->all(),
            'total'     => $p->total(),
            'page'      => $p->currentPage(),
            'per_page'  => $perPage,
            'last_page' => $p->lastPage(),
        ];
    }
}