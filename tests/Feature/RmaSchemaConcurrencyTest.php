<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Support\RmaSchemaFixture;

// Real root transactions: RefreshDatabase's enclosing transaction would hide commits.
uses(DatabaseMigrations::class);

it('serializes two MySQL claim acquirers and persists only the committed winner', function (): void {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('MySQL two-connection locking proof; SQLite is not a substitute.');
    }

    $fixture = new RmaSchemaFixture;
    $firstCase = $fixture->request();
    $secondCase = $fixture->request();
    config(['database.connections.rma_schema_peer' => config('database.connections.mysql')]);
    $peer = DB::connection('rma_schema_peer');
    $peer->statement('SET SESSION innodb_lock_wait_timeout=1');

    try {
        DB::beginTransaction();
        $fixture->member($firstCase);
        // The competing INSERT actually waits on the uncommitted serial PK.
        try {
            $peer->table('repair_request_items')->insert($fixture->memberData($secondCase));
            $this->fail('The uncommitted serial claim must block the competing acquire.');
        } catch (QueryException $exception) {
            expect((int) $exception->errorInfo[1])->toBe(1205);
        }
        expect($peer->table('active_repair_claims')->count())->toBe(0);
        DB::commit();
        expect($peer->table('active_repair_claims')->count())->toBe(1);
        try {
            $peer->table('repair_request_items')->insert($fixture->memberData($secondCase));
            $this->fail('The committed claim must reject the competing acquire.');
        } catch (QueryException $exception) {
            expect((int) $exception->errorInfo[1])->toBe(1062);
        }
        expect($peer->table('repair_request_items')->where('repair_request_id', $secondCase)->exists())->toBeFalse();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('rma_schema_peer');
    }
});

it('makes a rolled back serial claim available to another MySQL connection', function (): void {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('MySQL root rollback and cross-connection visibility proof.');
    }

    $fixture = new RmaSchemaFixture;
    $firstCase = $fixture->request();
    $secondCase = $fixture->request();
    config(['database.connections.rma_schema_peer' => config('database.connections.mysql')]);
    $peer = DB::connection('rma_schema_peer');
    try {
        DB::beginTransaction();
        $fixture->member($firstCase);
        DB::rollBack();
        $peer->table('repair_request_items')->insert($fixture->memberData($secondCase));
        expect((int) DB::table('active_repair_claims')->value('repair_request_item_id'))
            ->toBe((int) $peer->table('repair_request_items')->where('repair_request_id', $secondCase)->value('id'));
        expect(DB::table('repair_request_items')->where('repair_request_id', $firstCase)->exists())->toBeFalse();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('rma_schema_peer');
    }
});
