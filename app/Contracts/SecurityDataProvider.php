<?php

namespace App\Contracts;

interface SecurityDataProvider
{
    public function summary(): array;
    public function timeline(int $days = 7): array;
    public function bySeverity(): array;
    public function topAttackTypes(int $limit = 5): array;
    public function recentAlerts(array $filters = [], int $limit = 20): array;
    public function vulnSummary(): array;
    public function vulnerabilities(array $filters = [], int $limit = 20): array;
}