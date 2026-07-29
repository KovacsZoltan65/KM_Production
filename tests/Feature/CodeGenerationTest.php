<?php

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Services\CodeCreationService;
use App\Services\CodeGeneratorService;
use App\Support\CodeGeneration\CodeDefinitionRegistry;
use App\Support\CodeGeneration\CodeUniqueCollisionDetector;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

function codeGenerationUser(string $permission = 'suppliers.create'): User
{
    seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo($permission);

    return $user;
}

it('0001-ről indítja az üres számsort', function (): void {
    expect(app(CodeGeneratorService::class)->generate('supplier'))->toBe('SUP-0001');
});

it('a legnagyobb érvényes numerikus részt növeli és a hibás értékeket kihagyja', function (): void {
    Supplier::factory()->create(['code' => 'SUP-0008']);
    Supplier::factory()->create(['code' => 'SUP-12']);
    Supplier::factory()->create(['code' => 'SUP-RÉGI']);
    Supplier::factory()->create(['code' => 'OLD-SUP-9999']);

    expect(app(CodeGeneratorService::class)->generate('supplier'))->toBe('SUP-0013');
});

it('a konfigurált prefixet és sorszámhosszt használja', function (): void {
    config()->set('code_generation.prefixes.supplier', 'VENDOR');
    config()->set('code_generation.sequence_length', 6);

    expect(app(CodeGeneratorService::class)->generate('supplier'))->toBe('VENDOR-000001');
});

it('üres prefixkonfigurációnál biztonságos alapértékre áll vissza', function (): void {
    config()->set('code_generation.prefixes.supplier', '');

    expect(app(CodeGeneratorService::class)->generate('supplier'))->toBe('SUP-0001');
});

it('a soft delete rekordot is beleszámítja a számsorba', function (): void {
    $supplier = Supplier::factory()->create(['code' => 'SUP-0007']);
    $supplier->delete();

    expect(app(CodeGeneratorService::class)->generate('supplier'))->toBe('SUP-0008');
});

it('külön MAT és PRD cikkszámsort kezel', function (): void {
    Item::factory()->purchasedMaterial()->create(['item_number' => 'MAT-0004']);
    Item::factory()->finishedProduct()->create(['item_number' => 'PRD-0009']);

    $generator = app(CodeGeneratorService::class);

    expect($generator->generate('item', ['item_type' => ItemType::PurchasedMaterial->value]))
        ->toBe('MAT-0005')
        ->and($generator->generate('item', ['item_type' => ItemType::ManufacturedPart->value]))
        ->toBe('PRD-0010')
        ->and($generator->generate('item', ['item_type' => ItemType::SemiFinishedProduct->value]))
        ->toBe('PRD-0010')
        ->and($generator->generate('item', ['item_type' => ItemType::FinishedProduct->value]))
        ->toBe('PRD-0010');
});

it('ismeretlen kódtípust validációs hibával utasít el', function (): void {
    app(CodeDefinitionRegistry::class)->resolve('model_class_from_client');
})->throws(ValidationException::class);

it('minden támogatott registry definíció valós modellt és mezőt használ', function (): void {
    $registry = app(CodeDefinitionRegistry::class);

    foreach ($registry->supportedTypes() as $type) {
        $context = $type === 'item'
            ? ['item_type' => ItemType::PurchasedMaterial->value]
            : [];
        $definition = $registry->resolve($type, $context);

        expect(class_exists($definition->modelClass))->toBeTrue()
            ->and(Schema::hasColumn($definition->table, $definition->column))->toBeTrue();
    }
});

it('MySQL hibaüzenetből csak a célzott kód unique indexét ismeri fel', function (): void {
    $definition = app(CodeDefinitionRegistry::class)->resolve('supplier');
    $detector = app(CodeUniqueCollisionDetector::class);
    $codeCollision = new QueryException(
        'mysql',
        'insert into suppliers',
        [],
        new PDOException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'SUP-0001' for key 'suppliers.suppliers_code_unique'", 23000),
    );
    $otherCollision = new QueryException(
        'mysql',
        'insert into suppliers',
        [],
        new PDOException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'mail@example.com' for key 'suppliers.suppliers_email_unique'", 23000),
    );

    expect($detector->isCodeCollision($codeCollision, $definition))->toBeTrue()
        ->and($detector->isCodeCollision($otherCollision, $definition))->toBeFalse();
});

it('a jogosult felhasználónak kódjavaslatot ad az endpoint', function (): void {
    $user = codeGenerationUser();

    actingAs($user)
        ->getJson('/admin/code-generation/supplier')
        ->assertOk()
        ->assertJson([
            'code' => 'SUP-0001',
            'generated' => true,
            'type' => 'supplier',
        ]);
});

it('a generáló endpoint autentikációt és entitásspecifikus create jogot követel', function (): void {
    getJson('/admin/code-generation/supplier')->assertRedirect('/login');

    $user = codeGenerationUser('customers.create');

    actingAs($user)
        ->getJson('/admin/code-generation/supplier')
        ->assertForbidden();
});

it('a generált kód ütközésekor következő kóddal ment', function (): void {
    Supplier::factory()->create(['code' => 'SUP-0001']);
    $user = codeGenerationUser();

    actingAs($user)
        ->post('/admin/suppliers', [
            'code' => 'SUP-0001',
            'name' => 'Új beszállító',
            'is_active' => true,
            '_code_was_generated' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'SUP-0001')
            && str_contains($message, 'SUP-0002'));

    assertDatabaseHas('suppliers', [
        'code' => 'SUP-0002',
        'name' => 'Új beszállító',
    ]);
});

it('a kézi kód ütközésekor nem ment és következő kódot javasol', function (): void {
    Supplier::factory()->create(['code' => 'SUP-BUDAPEST']);
    $user = codeGenerationUser();

    actingAs($user)
        ->from('/admin/suppliers')
        ->post('/admin/suppliers', [
            'code' => 'SUP-BUDAPEST',
            'name' => 'Nem menthető',
            'is_active' => true,
            '_code_was_generated' => false,
        ])
        ->assertRedirect('/admin/suppliers')
        ->assertSessionHasErrors(['code'])
        ->assertSessionHasErrors([
            'code_suggestion' => 'SUP-0001',
        ]);

    assertDatabaseMissing('suppliers', ['name' => 'Nem menthető']);
});

it('szerkesztéskor nem engedi az üzleti kód módosítását', function (): void {
    $supplier = Supplier::factory()->create(['code' => 'SUP-0007']);
    $user = codeGenerationUser('suppliers.update');

    actingAs($user)
        ->put("/admin/suppliers/{$supplier->id}", [
            'code' => 'SUP-9999',
            'name' => 'Módosított név',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('code');

    expect($supplier->refresh()->code)->toBe('SUP-0007');
});

it('a generált kód retry limitjénél biztonságosan leáll', function (): void {
    config()->set('code_generation.max_create_attempts', 2);
    $repository = new FailingCodeRepository(codeCollisionException());

    app(CodeCreationService::class)->create('supplier', [
        'code' => 'SUP-0001',
        'name' => 'Versenyhelyzet',
        '_code_was_generated' => true,
    ], $repository);
})->throws(ValidationException::class);

it('más adatbázishibára nem próbál új kódot generálni', function (): void {
    $exception = new QueryException(
        'sqlite',
        'insert into suppliers',
        [],
        new PDOException('NOT NULL constraint failed: suppliers.name'),
    );
    $repository = new FailingCodeRepository($exception);

    app(CodeCreationService::class)->create('supplier', [
        'code' => 'SUP-0001',
        'name' => null,
        '_code_was_generated' => true,
    ], $repository);
})->throws(QueryException::class);

function codeCollisionException(): QueryException
{
    return new QueryException(
        'sqlite',
        'insert into suppliers',
        [],
        new PDOException('UNIQUE constraint failed: suppliers.code', 23000),
    );
}

/**
 * Meghatározott adatbázishibát dobó teszt-repository.
 */
final class FailingCodeRepository implements AdminRepositoryInterface
{
    public int $createCalls = 0;

    public function __construct(private readonly QueryException $exception) {}

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        throw new LogicException('A tesztben nem támogatott művelet.');
    }

    public function create(array $attributes): Model
    {
        $this->createCalls++;

        throw $this->exception;
    }

    public function update(Model $model, array $attributes): Model
    {
        throw new LogicException('A tesztben nem támogatott művelet.');
    }

    public function delete(Model $model): void
    {
        throw new LogicException('A tesztben nem támogatott művelet.');
    }
}
