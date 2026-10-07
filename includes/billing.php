<?php

// Water rates live in config/pricing.json and are edited from pricing.php.
// Bill = minimum charge for the first N cubic meters, plus excess rate per cubic meter above N.
// A bill is due "due_days" days after its meter reading.

const PRICING_FILE = __DIR__ . "/../config/pricing.json";

const DEFAULT_PRICING = [
    "minimum_charge" => 120,
    "minimum_cubic"  => 10,
    "excess_rate"    => 8,
    "due_days"       => 15,
];

function get_pricing(): array
{
    if (is_file(PRICING_FILE)) {
        $data = json_decode(file_get_contents(PRICING_FILE), true);

        if (is_array($data)) {
            return array_merge(DEFAULT_PRICING, $data);
        }
    }

    return DEFAULT_PRICING;
}

function save_pricing(array $pricing): bool
{
    if (!is_dir(dirname(PRICING_FILE))) {
        mkdir(dirname(PRICING_FILE), 0775, true);
    }

    return file_put_contents(PRICING_FILE, json_encode($pricing, JSON_PRETTY_PRINT)) !== false;
}

function compute_bill(float $usage, ?array $pricing = null): float
{
    $pricing ??= get_pricing();

    $excess = max(0, $usage - $pricing["minimum_cubic"]);

    return $pricing["minimum_charge"] + ($excess * $pricing["excess_rate"]);
}
