<?php

namespace Database\Factories;

use App\Models\SecurityAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityAlert>
 */
class SecurityAlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    $status = fake()->randomElement(['open', 'investigating', 'resolved', 'false_positive']);
    $detected = fake()->dateTimeBetween('-7 days', 'now');

    return [
        'source_ip' => fake()->ipv4(),
        'destination_ip' => '10.0.' . fake()->numberBetween(0, 10) . '.' . fake()->numberBetween(1, 254),
        'country' => fake()->randomElement(['CN', 'RU', 'US', 'BR', 'ID', 'IN', 'DE']),
        'attack_type' => fake()->randomElement(['Brute Force', 'SQL Injection', 'XSS', 'DDoS', 'Malware', 'Port Scan']),
        'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
        'status' => $status,
        'source_system' => fake()->randomElement(['Firewall', 'IDS', 'WAF', 'Endpoint']),
        'description' => fake()->sentence(),
        'detected_at' => $detected,
        'resolved_at' => $status === 'resolved'
            ? (clone $detected)->modify('+' . rand(5, 240) . ' minutes')
            : null,
    ];
}
}
