<?php

declare(strict_types=1);

use App\Support\Procurement\ProcurementDecimal;
use App\Support\Procurement\ProcurementRequirementInput;
use App\Support\Procurement\ProcurementRequirementProvenance;
use App\Support\Procurement\ProcurementTiming;
use App\Support\Procurement\SupplierOptionQuery;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\Support\NonStrictProcurementRequirementCaller;

function supplierOptionInput(array $overrides = []): ProcurementRequirementInput
{
    return new ProcurementRequirementInput(...[
        'itemId' => 1,
        'requiredQuantity' => '3.000',
        'requiredDate' => '2026-09-30',
        'unit' => 'kg',
        'evaluationDate' => '2026-09-28',
        'provenance' => new ProcurementRequirementProvenance('material_requirement_netting', 1, 'net_requirement', '2026-09-28T10:00:00+02:00', 'all_requirements'),
        ...$overrides,
    ]);
}

it('rejects noncanonical or invalid authoritative input without rounding', function (array $overrides): void {
    expect(fn () => supplierOptionInput($overrides))->toThrow(ValidationException::class);
})->with([
    'negative' => [['requiredQuantity' => '-0.001']],
    'negative zero' => [['requiredQuantity' => '-0.000']],
    'overflow' => [['requiredQuantity' => '1000000000000000.000']],
    'precision' => [['requiredQuantity' => '0.0001']],
    'extra precision zero' => [['requiredQuantity' => '3.0000']],
    'exponent' => [['requiredQuantity' => '3e0']],
    'whitespace' => [['requiredQuantity' => ' 3.000']],
    'leading zero' => [['requiredQuantity' => '03.000']],
    'integer string' => [['requiredQuantity' => '3']],
    'missing quantity' => [['requiredQuantity' => '']],
    'invalid item id' => [['itemId' => 0]],
    'empty unit' => [['unit' => ' ']],
    'invalid leap date' => [['requiredDate' => '2026-02-29']],
    'normalized date' => [['evaluationDate' => '2026-09-31']],
    'non ISO date' => [['evaluationDate' => '2026-9-28']],
    'year zero' => [['evaluationDate' => '0000-01-01']],
]);

it('preserves zero maximum decimal null and past dates as immutable input', function (): void {
    foreach (['0.000', '0.001', '999999999999999.999'] as $quantity) {
        $input = supplierOptionInput(['requiredQuantity' => $quantity, 'requiredDate' => null]);
        expect($input->requiredQuantity)->toBe($quantity)->and($input->requiredDate)->toBeNull();
        expect(fn () => (new ReflectionProperty($input, 'requiredQuantity'))->setValue($input, '5.000'))->toThrow(Error::class);
        $query = new SupplierOptionQuery($input);
        expect($query->requirement)->toBe($input);
    }
    expect(supplierOptionInput(['requiredDate' => '2000-02-29'])->requiredDate)->toBe('2000-02-29');
    expect(fn () => supplierOptionInput(['requiredQuantity' => 3.0]))->toThrow(ValidationException::class);
});

it('supports only the documented provenance pair and complete netting scope', function (): void {
    foreach ([
        ['material_requirement_netting', 'net_requirement', 'all_requirements'],
        ['material_requirement_netting', 'net_requirement', 'complete_item_requirements'],
        ['supply_proposal', 'proposal_planned', null],
        ['purchase_requisition_item', 'pr_planned', null],
    ] as [$source, $basis, $scope]) {
        $value = new ProcurementRequirementProvenance($source, 1, $basis, '2026-09-28T08:00:00Z', $scope);
        expect($value->sourceType)->toBe($source)->and($value->quantityBasis)->toBe($basis)->and($value->nettingScope)->toBe($scope);
    }
    foreach ([
        ['supply_proposal', 'net_requirement', null],
        ['material_requirement_netting', 'proposal_planned', 'all_requirements'],
        ['material_requirement_netting', 'net_requirement', null],
        ['material_requirement_netting', 'net_requirement', 'selected_only'],
        ['purchase_requisition_item', 'pr_planned', 'all_requirements'],
        ['unknown', 'net_requirement', null],
    ] as [$source, $basis, $scope]) {
        expect(fn () => new ProcurementRequirementProvenance($source, 1, $basis, '2026-09-28T08:00:00Z', $scope))->toThrow(ValidationException::class);
    }
    expect(fn () => new ProcurementRequirementProvenance('supply_proposal', 0, 'proposal_planned', '2026-09-28T08:00:00Z'))->toThrow(ValidationException::class);
});

it('rejects non-string quantities from a non-strict caller before scalar conversion', function (mixed $quantity): void {
    try {
        NonStrictProcurementRequirementCaller::make($quantity);
        $this->fail('Non-string quantity was accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('required_quantity');
    }
})->with([
    'fractional float' => [1.234],
    'float losing precision on string conversion' => [1.2340000000000002],
    'zero float' => [0.0],
    'integer' => [3],
    'boolean' => [true],
    'null' => [null],
]);

it('preserves canonical decimal strings from a non-strict caller', function (string $quantity): void {
    expect(NonStrictProcurementRequirementCaller::make($quantity)->requiredQuantity)->toBe($quantity);
})->with(['0.000', '1.234', '999999999999999.999']);

it('requires a valid observed timestamp with explicit timezone', function (string $timestamp): void {
    expect(fn () => new ProcurementRequirementProvenance('supply_proposal', 1, 'proposal_planned', $timestamp))->toThrow(ValidationException::class);
})->with(['2026-09-28', '2026-09-28T08:00:00', '2026-02-30T08:00:00Z', '2026-09-28T24:00:00Z', '2026-09-28T08:61:00Z', '2026-09-28T08:00:00+25:00']);

it('preserves the legacy decimal parser acceptance independently of canonical query input', function (): void {
    foreach ([' 003.0000 ' => 3000, '-0.001' => -1, '999999999999999.999' => 999999999999999999, '0.0000' => 0] as $input => $expected) {
        expect(ProcurementDecimal::toScaledInteger($input, 3, 'quantity'))->toBe($expected);
    }
    expect(ProcurementDecimal::toScaledInteger('0.000001', 6, 'factor'))->toBe(1);
});

it('keeps calendar days across weekends and daylight saving without mutating the business clock', function (): void {
    $clock = Carbon::parse('2026-03-27', 'Europe/Budapest');
    $expected = ProcurementTiming::expectedDate($clock, 3);
    expect($clock->toDateString())->toBe('2026-03-27')
        ->and($expected->toDateString())->toBe('2026-03-30')
        ->and($expected->format('P'))->toBe('+02:00')
        ->and(ProcurementTiming::isLate($expected, $clock->copy()->addDays(3)))->toBeFalse();
});
