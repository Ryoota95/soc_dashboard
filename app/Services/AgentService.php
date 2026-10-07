<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentService
{
    public function overview(string $name): array
    {
        return [
            'info'          => $this->info($name),
            'inventory'     => $this->inventory($name),
            'events'        => $this->safe(fn () => $this->events($name)),
            'mitre'         => $this->safe(fn () => $this->mitre($name)),
            'compliance'    => $this->safe(fn () => $this->compliance($name)),
            'vulnerability' => $this->safe(fn () => $this->vulnerability($name)),
            'sca'           => $this->safe(fn () => $this->sca($name)),
            'fim'           => $this->safe(fn () => $this->fim($name)),
        ];
    }

    private function search(string $index, array $body): array
    {
        $url = rtrim(config('opensearch.url'), '/') . '/' . $index . '/_search';

        return Http::withBasicAuth(config('opensearch.user'), config('opensearch.password'))
            ->withOptions(['verify' => filter_var(config('opensearch.verify_ssl'), FILTER_VALIDATE_BOOLEAN)])
            ->acceptJson()
            ->timeout(15)
            ->post($url, $body)
            ->throw()
            ->json();
    }

    // satu bagian gagal tidak boleh merusak seluruh halaman
    private function safe(callable $fn)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::warning('Bagian agent gagal: ' . $e->getMessage());
            return null;
        }
    }

    private function scope(string $name, array $extra = []): array
    {
        return ['bool' => ['filter' => array_merge([['term' => ['agent.name' => $name]]], $extra)]];
    }

    private function vf(string $key): string
    {
        return config("opensearch.vuln.fields.$key");
    }

    private function buckets(array $r, string $agg): array
    {
        return $r['aggregations'][$agg]['buckets'] ?? [];
    }

    // ambil 1 dokumen terbaru
    private function first(string $index, array $query, ?string $sort = null): ?array
    {
        return $this->safe(function () use ($index, $query, $sort) {
            $body = ['size' => 1, 'query' => $query];
            if ($sort) {
                $body['sort'] = [[$sort => ['order' => 'desc', 'unmapped_type' => 'date']]];
            }
            return $this->search($index, $body)['hits']['hits'][0]['_source'] ?? null;
        });
    }

    private function info(string $name): array
    {
        $alert = $this->first(config('opensearch.index'), $this->scope($name), '@timestamp');
        $vuln  = $this->first(config('opensearch.vuln.index'), $this->scope($name));
        $mon   = $this->first(config('opensearch.agent.monitoring_index'), ['match_phrase' => ['name' => $name]], 'timestamp');

        return [
            'name'       => $name,
            'id'         => data_get($alert, 'agent.id') ?? data_get($vuln, 'agent.id') ?? data_get($mon, 'id'),
            'ip'         => data_get($alert, 'agent.ip') ?? data_get($mon, 'ip'),
            'status'     => data_get($mon, 'status'),
            'version'    => data_get($vuln, 'agent.version'),
            'os'         => data_get($vuln, 'host.os.full'),
            'kernel'     => data_get($vuln, 'host.os.kernel'),
            'last_seen'  => data_get($mon, 'timestamp'),
            'last_alert' => data_get($alert, '@timestamp'),
        ];
    }

    private function inventory(string $name): array
    {
        $f   = config('opensearch.agent.fields');
        $hw  = $this->first(config('opensearch.agent.hardware_index'), $this->scope($name));
        $sys = $this->first(config('opensearch.agent.system_index'), $this->scope($name));

        return [
            'hostname' => data_get($sys, $f['hostname']) ?? $name,
            'cpu'      => data_get($hw, $f['cpu_name']),
            'cores'    => data_get($hw, $f['cpu_cores']),
            'memory'   => data_get($hw, $f['memory_total']),
            'serial'   => data_get($hw, $f['serial']),
        ];
    }

    // jumlah alert per 30 menit, 24 jam terakhir
    private function events(string $name): array
    {
        $r = $this->search(config('opensearch.index'), [
            'size'  => 0,
            'query' => $this->scope($name, [['range' => ['@timestamp' => ['gte' => 'now-24h']]]]),
            'aggs'  => ['t' => ['date_histogram' => [
                'field'          => '@timestamp',
                'fixed_interval' => '30m',
                'time_zone'      => 'Asia/Jakarta',
                'min_doc_count'  => 0,
            ]]],
        ]);

        return collect($this->buckets($r, 't'))
            ->map(fn ($b) => ['time' => $b['key_as_string'], 'total' => $b['doc_count']])
            ->all();
    }

    private function mitre(string $name): array
    {
        $r = $this->search(config('opensearch.index'), [
            'size'  => 0,
            'query' => $this->scope($name),
            'aggs'  => ['m' => ['terms' => ['field' => 'rule.mitre.tactic', 'size' => 5]]],
        ]);

        return collect($this->buckets($r, 'm'))
            ->map(fn ($b) => ['name' => $b['key'], 'total' => $b['doc_count']])
            ->all();
    }

    private function compliance(string $name): array
    {
        $frameworks = [
            'pci_dss'     => 'PCI DSS',
            'gdpr'        => 'GDPR',
            'hipaa'       => 'HIPAA',
            'nist_800_53' => 'NIST 800-53',
            'tsc'         => 'TSC',
        ];

        $aggs = [];
        foreach (array_keys($frameworks) as $k) {
            $aggs[$k] = ['terms' => ['field' => "rule.$k", 'size' => 5]];
        }

        $r = $this->search(config('opensearch.index'), [
            'size'  => 0,
            'query' => $this->scope($name),
            'aggs'  => $aggs,
        ]);

        $out = [];
        foreach ($frameworks as $k => $label) {
            $out[$k] = [
                'label' => $label,
                'items' => collect($this->buckets($r, $k))
                    ->map(fn ($b) => ['name' => (string) $b['key'], 'total' => $b['doc_count']])
                    ->all(),
            ];
        }
        return $out;
    }

    private function vulnerability(string $name): array
    {
        $r = $this->search(config('opensearch.vuln.index'), [
            'size'  => 0,
            'query' => $this->scope($name),
            'aggs'  => [
                'sev' => ['terms' => ['field' => $this->vf('severity'), 'size' => 10]],
                'pkg' => ['terms' => ['field' => $this->vf('package'), 'size' => 5]],
            ],
        ]);

        $by = collect($this->buckets($r, 'sev'))
            ->mapWithKeys(fn ($b) => [strtolower($b['key']) => $b['doc_count']]);

        return [
            'critical' => $by['critical'] ?? 0,
            'high'     => $by['high'] ?? 0,
            'medium'   => $by['medium'] ?? 0,
            'low'      => $by['low'] ?? 0,
            'packages' => collect($this->buckets($r, 'pkg'))
                ->map(fn ($b) => ['name' => $b['key'], 'total' => $b['doc_count']])
                ->all(),
        ];
    }

    // ringkasan SCA terakhir per policy (dari alert grup "sca")
    private function sca(string $name): array
    {
        $r = $this->search(config('opensearch.index'), [
            'size'  => 0,
            'query' => $this->scope($name, [
                ['term' => ['rule.groups' => 'sca']],
                ['exists' => ['field' => 'data.sca.score']],
            ]),
            'aggs'  => ['p' => [
                'terms' => ['field' => 'data.sca.policy', 'size' => 5],
                'aggs'  => ['last' => ['top_hits' => ['size' => 1, 'sort' => [['@timestamp' => 'desc']]]]],
            ]],
        ]);

        return collect($this->buckets($r, 'p'))->map(function ($b) {
            $s = $b['last']['hits']['hits'][0]['_source'] ?? [];
            return [
                'policy'  => data_get($s, 'data.sca.policy'),
                'end'     => data_get($s, '@timestamp'),
                'passed'  => data_get($s, 'data.sca.passed'),
                'failed'  => data_get($s, 'data.sca.failed'),
                'invalid' => data_get($s, 'data.sca.invalid'),
                'score'   => data_get($s, 'data.sca.score'),
            ];
        })->all();
    }

    private function fim(string $name): array
    {
        $r = $this->search(config('opensearch.index'), [
            'size'  => 10,
            'sort'  => [['@timestamp' => 'desc']],
            'query' => $this->scope($name, [['term' => ['rule.groups' => 'syscheck']]]),
        ]);

        return collect($r['hits']['hits'] ?? [])->map(function ($h) {
            $s = $h['_source'];
            return [
                'time'   => data_get($s, '@timestamp'),
                'path'   => data_get($s, 'syscheck.path'),
                'action' => data_get($s, 'syscheck.event'),
                'rule'   => data_get($s, 'rule.description'),
                'level'  => data_get($s, 'rule.level'),
                'rule_id' => data_get($s, 'rule.id'),
            ];
        })->all();
    }
}