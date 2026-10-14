<?php

namespace App\Support\Merlin;

use Illuminate\Database\Connection;
use PDO;

/** Transaction lifetime guard; never persists data or authorizes an actor. */
final class MerlinAdmissionTransactionState
{
    private bool $active = false;

    public function __construct(private readonly Connection $connection, private ?PDO $pdo) {}

    public function activate(): void
    {
        $this->active = true;
        $this->assertActive();
    }

    public function assertActive(): void
    {
        if (! $this->active || $this->pdo === null || $this->connection->getRawPdo() !== $this->pdo
            || $this->connection->transactionLevel() !== 1 || ! $this->pdo->inTransaction()) {
            throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_TRANSACTION_LOST');
        }
    }

    public function release(): void
    {
        $this->active = false;
        $this->pdo = null;
    }
}
