<?php

use App\Support\Roster\PlayerDataValidator;

test('values inside realistic bounds pass untouched', function () {
    $attributes = [
        'position' => 'Forward', 'shirt_number' => 9, 'age' => 26, 'height_cm' => 195,
        'market_value' => '€200.00m', 'weekly_salary' => '€600k', 'intl_caps' => 48, 'intl_goals' => 55,
        'contract_expires' => '2034-06-30',
    ];

    expect(PlayerDataValidator::clean($attributes))->toBe([$attributes, []]);
});

test('out-of-bounds values become null and are reported', function (string $field, mixed $value) {
    [$clean, $problems] = PlayerDataValidator::clean([$field => $value]);

    expect($clean[$field])->toBeNull()->and($problems)->toHaveCount(1);
})->with([
    'shirt 0' => ['shirt_number', 0],
    'shirt 100' => ['shirt_number', 100],
    'age 12' => ['age', 12],
    'height 2 m 40' => ['height_cm', 240],
    'market value €1.5bn' => ['market_value', '€1.50bn'],
    'unparseable money' => ['market_value', 'lots'],
    'rating 11' => ['avg_rating', 11],
    'unknown position' => ['position', 'Libero'],
    'expired contract' => ['contract_expires', '2020-06-30'],
]);

test('positions map to GK/DF/MF/FW', function () {
    expect(array_map(PlayerDataValidator::positionGroup(...), ['Goalkeeper', 'Defender', 'Midfielder', 'Forward', 'Coach']))
        ->toBe(['GK', 'DF', 'MF', 'FW', null]);
});

test('money strings parse to € millions', function () {
    expect(array_map(PlayerDataValidator::money(...), ['€1.30bn', '€180.00m', '€600k', '-€5.00m', 'n/a']))
        ->toBe([1300.0, 180.0, 0.6, -5.0, null]);
});
