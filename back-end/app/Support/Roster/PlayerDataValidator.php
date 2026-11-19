<?php

namespace App\Support\Roster;

use Illuminate\Support\Carbon;

/**
 * Realistic bounds for player attributes. A value outside them is treated as bad
 * data and replaced by null (unknown) rather than kept or guessed; every
 * replacement is reported.
 */
class PlayerDataValidator
{
    /** Position category => group code. */
    public const POSITION_GROUPS = ['Goalkeeper' => 'GK', 'Defender' => 'DF', 'Midfielder' => 'MF', 'Forward' => 'FW'];

    /** field => [min, max], inclusive. Money bounds are in € millions. */
    private const BOUNDS = [
        'shirt_number' => [1, 99],
        'age' => [15, 45],
        'height_cm' => [150, 215],
        'market_value' => [0.01, 300],
        'highest_market_value' => [0.01, 300],
        'weekly_salary' => [0.0005, 2],
        'annual_salary' => [0.025, 104],
        'avg_rating' => [1, 10],
        'pass_accuracy_pct' => [0, 100],
        'dribble_success_pct' => [0, 100],
        'aerial_duels_won_pct' => [0, 100],
        'intl_caps' => [0, 250],
    ];

    private const MONEY = ['market_value', 'highest_market_value', 'weekly_salary', 'annual_salary'];

    /**
     * @param  array<string, mixed>  $attributes  player attributes about to be saved
     * @return array{0: array<string, mixed>, 1: list<string>} cleaned attributes and the problems found
     */
    public static function clean(array $attributes): array
    {
        $problems = [];

        if (array_key_exists('position', $attributes) && ! isset(self::POSITION_GROUPS[$attributes['position']])) {
            $problems[] = "position \"{$attributes['position']}\" is not one of GK/DF/MF/FW";
            $attributes['position'] = null;
        }

        foreach (self::BOUNDS as $field => [$min, $max]) {
            $raw = $attributes[$field] ?? null;
            if ($raw === null) {
                continue;
            }
            $value = in_array($field, self::MONEY, true) ? self::money($raw) : (is_numeric($raw) ? (float) $raw : null);
            if ($value === null || $value < $min || $value > $max) {
                $problems[] = "{$field} ".json_encode($raw, JSON_UNESCAPED_UNICODE)." is outside {$min}–{$max}";
                $attributes[$field] = null;
            }
        }

        if (isset($attributes['intl_goals'], $attributes['intl_caps']) && $attributes['intl_goals'] > $attributes['intl_caps'] * 2) {
            $problems[] = "intl_goals {$attributes['intl_goals']} is implausible for {$attributes['intl_caps']} caps";
            $attributes['intl_goals'] = null;
        }

        foreach (['contract_expires'] as $field) {
            if (! empty($attributes[$field]) && Carbon::parse($attributes[$field])->isPast()) {
                $problems[] = "{$field} {$attributes[$field]} has already passed (stale contract data)";
                $attributes[$field] = null;
            }
        }

        return [$attributes, $problems];
    }

    public static function positionGroup(?string $position): ?string
    {
        return self::POSITION_GROUPS[$position] ?? null;
    }

    /** "€1.30bn" / "€180.00m" / "€600k" / "-€5.00m" => € millions; null when unparseable. */
    public static function money(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (! is_string($value) || ! preg_match('/^([+-]?)€([\d.]+)(bn|m|k)$/u', $value, $m)) {
            return null;
        }
        $millions = (float) $m[2] * ['bn' => 1000, 'm' => 1, 'k' => 0.001][$m[3]];

        return $m[1] === '-' ? -$millions : $millions;
    }
}
