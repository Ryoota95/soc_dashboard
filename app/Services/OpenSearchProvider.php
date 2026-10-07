<?php

namespace App\Services;

use App\Contracts\SecurityDataProvider;
use Illuminate\Support\Facades\Http;

class OpenSearchProvider implements SecurityDataProvider
{
    private function f(string $key): string
    {
        return config("opensearch.fields.$key");
    }

    private function lv(string $key): int
    {
        return config("opensearch.levels.$key");
    }

    private function search(array $body): array
    {
        $url = rtrim(config('opensearch.url'), '/') . '/' . config('opensearch.index') . '/_search';

        return Http::withBasicAuth(config('opensearch.user'), config('opensearch.password'))
            ->withOptions(['verify' => filter_var(config('opensearch.verify_ssl'), FILTER_VALIDATE_BOOLEAN)])
            ->acceptJson()
            ->timeout(15)
            ->post($url, $body)
            ->throw()
            ->json();
    }

    private function severityLabel($level): string
    {
        $level = (int) $level;
        return match (true) {
            $level >= $this->lv('critical') => 'critical',
            $level >= $this->lv('high')     => 'high',
            $level >= $this->lv('medium')   => 'medium',
            default                         => 'low',
        };
    }

    public function summary(): array
    {
        $r = $this->search([
            'size' => 0,
            'track_total_hits' => true,
            'aggs' => [
                'critical' => ['filter' => ['range' => [$this->f('level') => ['gte' => $this->lv('critical')]]]],
                'high'     => ['filter' => ['range' => [$this->f('level') => ['gte' => $this->lv('high'), 'lt' => $this->lv('critical')]]]],
                'agents'   => ['cardinality' => ['field' => $this->f('agent')]],
            ],
        ]);

        return [
            'total'    => $r['hits']['total']['value'] ?? 0,
            'critical' => $r['aggregations']['critical']['doc_count'] ?? 0,
            'high'     => $r['aggregations']['high']['doc_count'] ?? 0,
            'agents'   => $r['aggregations']['agents']['value'] ?? 0,
        ];
    }

    public function timeline(int $days = 7): array
    {
        $r = $this->search([
            'size' => 0,
            'query' => ['range' => [$this->f('time') => ['gte' => "now-{$days}d"]]],
            'aggs' => ['per_day' => [
                'date_histogram' => [
                    'field' => $this->f('time'),
                    'calendar_interval' => 'day',
                    'time_zone' => 'Asia/Jakarta',
                ],
            ]],
        ]);

        return collect($r['aggregations']['per_day']['buckets'] ?? [])
            ->map(fn ($b) => [
                'date'  => substr($b['key_as_string'], 0, 10),
                'total' => $b['doc_count'],
            ])->all();
    }

    public function bySeverity(): array
    {
        $r = $this->search([
            'size' => 0,
            'aggs' => ['sev' => ['range' => [
                'field'  => $this->f('level'),
                'keyed'  => true,
                'ranges' => [
                    ['key' => 'low',      'to'   => $this->lv('medium')],
                    ['key' => 'medium',   'from' => $this->lv('medium'), 'to' => $this->lv('high')],
                    ['key' => 'high',     'from' => $this->lv('high'),   'to' => $this->lv('critical')],
                    ['key' => 'critical', 'from' => $this->lv('critical')],
                ],
            ]]],
        ]);

        return collect($r['aggregations']['sev']['buckets'] ?? [])
            ->map(fn ($b) => $b['doc_count'])->all();
    }

    // Top rule yang paling sering muncul
    public function topAttackTypes(int $limit = 5): array
    {
        $r = $this->search([
            'size' => 0,
            'aggs' => ['rules' => ['terms' => ['field' => $this->f('rule'), 'size' => $limit]]],
        ]);

        return collect($r['aggregations']['rules']['buckets'] ?? [])
            ->map(fn ($b) => ['attack_type' => $b['key'], 'total' => $b['doc_count']])
            ->all();
    }

    public function recentAlerts(array $filters = [], int $limit = 20): array
    {
        $filter = [];

        if (!empty($filters['severity'])) {
            $range = match ($filters['severity']) {
                'critical' => ['gte' => $this->lv('critical')],
                'high'     => ['gte' => $this->lv('high'), 'lt' => $this->lv('critical')],
                'medium'   => ['gte' => $this->lv('medium'), 'lt' => $this->lv('high')],
                'low'      => ['lt' => $this->lv('medium')],
                default    => null,
            };
            if ($range) {
                $filter[] = ['range' => [$this->f('level') => $range]];
            }
        }

        $r = $this->search([
            'size'  => $limit,
            'sort'  => [[$this->f('time') => 'desc']],
            'query' => ['bool' => ['filter' => $filter]],
        ]);

        return collect($r['hits']['hits'] ?? [])->map(function ($h) {
            $s = $h['_source'];
            $level = data_get($s, $this->f('level'));
            return [
                'detected_at' => data_get($s, $this->f('time')),
                'agent'       => data_get($s, $this->f('agent')),
                'rule'        => data_get($s, $this->f('rule')),
                'level'       => $level,
                'severity'    => $this->severityLabel($level),
            ];
        })->all();
    }

        private function vf(string $key): string
    {
        return config("opensearch.vuln.fields.$key");
    }

    private function searchVuln(array $body): array
    {
        $url = rtrim(config('opensearch.url'), '/') . '/' . config('opensearch.vuln.index') . '/_search';

        return Http::withBasicAuth(config('opensearch.user'), config('opensearch.password'))
            ->withOptions(['verify' => filter_var(config('opensearch.verify_ssl'), FILTER_VALIDATE_BOOLEAN)])
            ->acceptJson()
            ->timeout(15)
            ->post($url, $body)
            ->throw()
            ->json();
    }

    public function vulnSummary(): array
    {
        $r = $this->searchVuln([
            'size' => 0,
            'track_total_hits' => true,
            'aggs' => ['sev' => ['terms' => ['field' => $this->vf('severity'), 'size' => 10]]],
        ]);

        $total = $r['hits']['total']['value'] ?? 0;
        $by = collect($r['aggregations']['sev']['buckets'] ?? [])
            ->mapWithKeys(fn ($b) => [strtolower($b['key']) => $b['doc_count']]);

        $critical = $by['critical'] ?? 0;
        $high     = $by['high'] ?? 0;
        $medium   = $by['medium'] ?? 0;
        $low      = $by['low'] ?? 0;

        return [
            'total'    => $total,
            'critical' => $critical,
            'high'     => $high,
            'medium'   => $medium,
            'low'      => $low,
            'other'    => $total - $critical - $high - $medium - $low,
        ];
    }

        public function vulnerabilities(array $filters = [], int $limit = 20): array
    {
        $filter = [];

        if (!empty($filters['severity'])) {
            $filter[] = ['term' => [$this->vf('severity') => ucfirst(strtolower($filters['severity']))]];
        }

        $r = $this->searchVuln([
            'size'  => $limit,
            'query' => ['bool' => ['filter' => $filter]],
        ]);

        return collect($r['hits']['hits'] ?? [])->map(function ($h) {
            $s = $h['_source'];
            return [
                'agent'       => data_get($s, $this->vf('agent')),
                'package'     => data_get($s, $this->vf('package')),
                'version'     => data_get($s, $this->vf('version')),
                'cve'         => data_get($s, $this->vf('cve')),
                'severity'    => data_get($s, $this->vf('severity')),
                'description' => (string) data_get($s, $this->vf('description')),
                'raw'         => array_merge(['_index' => $h['_index'] ?? null], $s),
            ];
        })->all();
    }
}