<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use RmaTransactionBoundaryPoc\Prototype;
use RmaTransactionBoundaryPoc\UnknownCommitOutcome;

require __DIR__.'/Prototype.php';

if (is_file(__DIR__.'/evidence/results.json')) {
    throw new RuntimeException('Historical evidence must be preserved before any new run');
}

function check(mixed $condition, string $assertion): void
{
    if (! $condition) {
        throw new RuntimeException($assertion);
    }
}

/** @return array<string, mixed> */
function counts(PDO $observer, string $key): array
{
    check(! $observer->inTransaction(), 'Observer must use fresh autocommit reads');
    $out = [];
    foreach (['poc_commands', 'poc_business', 'poc_evidence'] as $table) {
        $s = $observer->prepare("SELECT COUNT(*) FROM $table WHERE command_key=?");
        $s->execute([$key]);
        $out[$table] = (int) $s->fetchColumn();
    }

    return $out;
}

/** @return array<string, mixed> */
function sqlError(Closure $action): array
{
    try {
        $action();
    } catch (PDOException $e) {
        return ['sqlstate' => (string) $e->getCode(), 'vendor_code' => $e->errorInfo[1] ?? null];
    }
    throw new RuntimeException('Expected database rejection did not occur');
}

/** @return array<string, mixed> */
function refused(Closure $action): array
{
    try {
        $action();
    } catch (RuntimeException $e) {
        return ['exception' => $e::class];
    }
    throw new RuntimeException('Expected entry rejection did not occur');
}

$evidence = ['run_utc' => gmdate('c'), 'verdict' => 'POC BLOCKED', 'tests' => [], 'limitations' => []];
$admin = null;
$failure = false;
try {
    $c = Prototype::configuration();
    $admin = Prototype::connect($c, admin: true, database: false);
    $server = $admin->query('SELECT @@datadir AS data_root,@@port AS port,VERSION() AS version,@@server_uuid AS server_uuid')->fetch(PDO::FETCH_ASSOC);
    check(str_replace('\\', '/', rtrim($server['data_root'], '\\/')) === str_replace('\\', '/', $c['owned_data_root']), 'Owned data root mismatch');
    check((int) $server['port'] === $c['port'] && str_starts_with($server['version'], '8.4.'), 'Owned server profile mismatch');
    $schemas = $admin->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
    check(array_diff($schemas, ['information_schema', 'mysql', 'performance_schema', 'sys']) === [], 'Fresh server contains unexpected databases');
    $evidence['isolation'] = [
        'result' => 'PASS', 'launcher_owned_listener_gate' => true, 'fresh_datadir_matched' => true,
        'only_system_schemas_before_setup' => true, 'explicit_database_allowlist' => 'rma_c302_disposable',
        'no_application_bootstrap_or_default_connection' => true, 'server_uuid' => $server['server_uuid'],
        'private_datadir_hash' => hash('sha256', $server['data_root']), 'generated_test_only_secrets' => true,
    ];
    $admin->exec('CREATE DATABASE rma_c302_disposable CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs');
    $admin->exec('USE rma_c302_disposable');
    foreach (['poc_actor101' => $c['runtime_password'], 'poc_definer' => $c['definer_password']] as $user => $secret) {
        $admin->exec("CREATE USER '$user'@'127.0.0.1' IDENTIFIED BY ".$admin->quote($secret).($user === 'poc_definer' ? ' ACCOUNT LOCK' : ''));
    }
    $admin->exec("GRANT SELECT,INSERT,UPDATE,TRIGGER ON rma_c302_disposable.* TO 'poc_definer'@'127.0.0.1'");
    foreach (explode('-- @statement', file_get_contents(__DIR__.'/schema.mysql')) as $sql) {
        if (trim($sql) !== '') {
            $admin->exec($sql);
        }
    }
    foreach (['poc_commands', 'poc_business', 'poc_evidence'] as $table) {
        foreach (['UPDATE', 'DELETE'] as $verb) {
            $admin->exec("CREATE DEFINER='poc_definer'@'127.0.0.1' TRIGGER {$table}_".strtolower($verb).
                " BEFORE $verb ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='POC immutable fact'");
        }
        $admin->exec("GRANT SELECT ON rma_c302_disposable.$table TO 'poc_actor101'@'127.0.0.1'");
    }
    $admin->exec("GRANT SELECT,INSERT ON rma_c302_disposable.poc_caller_sentinel TO 'poc_actor101'@'127.0.0.1'");
    $admin->exec("GRANT EXECUTE ON PROCEDURE rma_c302_disposable.poc_post TO 'poc_actor101'@'127.0.0.1'");
    $admin->exec("GRANT EXECUTE ON PROCEDURE rma_c302_disposable.poc_post TO 'poc_definer'@'127.0.0.1'");
    $admin->exec('CREATE PROCEDURE poc_private_helper() SELECT 1');
    $engines = $admin->query("SELECT DISTINCT ENGINE FROM information_schema.tables WHERE table_schema='rma_c302_disposable'")->fetchAll(PDO::FETCH_COLUMN);
    check($engines === ['InnoDB'], 'All proof tables must be transactional');
    $evidence['environment'] = [
        'mysql' => $server['version'], 'php' => PHP_VERSION,
        'laravel' => Application::VERSION,
        'pdo_client' => $admin->getAttribute(PDO::ATTR_CLIENT_VERSION), 'engines' => $engines,
        'settings' => $admin->query('SELECT @@SESSION.sql_mode AS sql_mode,@@SESSION.transaction_isolation AS isolation_level,@@SESSION.autocommit AS autocommit')->fetch(PDO::FETCH_ASSOC),
    ];
    $evidence['privileges'] = [
        'runtime' => $admin->query("SHOW GRANTS FOR 'poc_actor101'@'127.0.0.1'")->fetchAll(PDO::FETCH_COLUMN),
        'definer' => $admin->query("SHOW GRANTS FOR 'poc_definer'@'127.0.0.1'")->fetchAll(PDO::FETCH_COLUMN),
        'setup' => 'fresh isolated server root; generated credential; harness parent only',
        'definer_login_locked' => (bool) $admin->query("SELECT account_locked='Y' FROM mysql.user WHERE User='poc_definer'")->fetchColumn(),
    ];
    $factory = fn () => Prototype::connection(Prototype::connect($c));
    $make = fn (?Closure $callers = null) => new Prototype($factory, $callers ?? fn () => []);
    $one = ['poc_commands' => 1, 'poc_business' => 1, 'poc_evidence' => 1];
    $zero = array_fill_keys(array_keys($one), 0);
    $run = function (string $id, string $precondition, string $operation, string $expected, string $wp7, Closure $test) use (&$evidence, &$failure): void {
        $row = ['id' => $id, 'preconditions' => $precondition, 'principal' => 'restricted runtime; setup-only observer where stated',
            'operation' => $operation, 'expected' => $expected, 'wp7' => $wp7,
            'cleanup' => 'Connections/roots closed per case; owned server/data removed by launcher after artifacts'];
        try {
            $actual = $test();
            $row += $actual;
            $row['status'] = $actual['status'] ?? 'PASS';
        } catch (Throwable $error) {
            $failure = true;
            $row['status'] = 'FAIL';
            $row['actual'] = ['error_class' => $error::class, 'code' => $error->getCode(),
                'assertion' => $error instanceof PDOException ? 'Database operation failed; secrets/query text suppressed' : $error->getMessage()];
        }
        $evidence['tests'][] = $row;
        echo $id.' '.$row['status'].PHP_EOL;
    };
    $run('T01', 'Fresh root/lease, observer autocommit, valid actor', 'Execute complete command with precommit observer pause', 'Invisible before root commit, exactly three records after', 'V38/V42/V43', function () use ($make, $admin, $one, $zero): array {
        $executor = $make();
        $before = null;
        $result = $executor->execute('T01', afterCall: function ($connection) use ($admin, &$before, $zero): void {
            check($connection->transactionLevel() === 1 && $connection->getPdo()->inTransaction(), 'No explicit owned root');
            $before = counts($admin, 'T01');
            check($before === $zero, 'Routine independently committed');
        });
        check(counts($admin, 'T01') === $one, 'Missing committed triplet');

        return ['actual' => 'Complete root committed', 'database_evidence' => ['before_commit' => $before, 'after_commit' => counts($admin, 'T01')], 'laravel_pdo' => $executor->trace, 'result' => $result];
    });
    $run('T02', 'Valid command; fault after business INSERT', 'Executor calls with after_business fault', 'All triplet records absent; root unwound', 'V38/V43', function () use ($make, $admin, $zero): array {
        $executor = $make();
        $error = sqlError(fn () => $executor->execute('T02', fault: 'after_business'));
        check(counts($admin, 'T02') === $zero && $error['sqlstate'] === '45000', 'Injected failure retained partial work');

        return ['actual' => $error, 'database_evidence' => counts($admin, 'T02'), 'laravel_pdo' => $executor->trace];
    });
    $run('T03', 'Caller root with trans2 savepoint and unrelated sentinel', 'Attempt executor entry from registered nested caller', 'Reject before factory/write; caller still active and savepoint usable', 'V42/V43', function () use ($factory, $admin, $zero): array {
        $caller = $factory();
        $trace = [];
        $caller->transaction(function () use ($caller, &$trace): void {
            $caller->getPdo()->exec("INSERT INTO poc_caller_sentinel VALUES (3,'unrelated')");
            $caller->transaction(function () use ($caller, &$trace): void {
                $executor = new Prototype(fn () => throw new RuntimeException('Factory must not be reached'), fn () => [$caller]);
                refused(fn () => $executor->execute('T03'));
                $trace[] = Prototype::state($caller);
                check($caller->transactionLevel() === 2 && $caller->getPdo()->inTransaction(), 'Caller root damaged');
            });
        });
        check(counts($admin, 'T03') === $zero && (int) $admin->query('SELECT COUNT(*) FROM poc_caller_sentinel WHERE id=3')->fetchColumn() === 1, 'Caller work lost or nested effects written');
        $caller->disconnect();

        return ['actual' => 'Rejected before connection factory; unrelated root committed intact', 'database_evidence' => counts($admin, 'T03'), 'laravel_pdo' => $trace];
    });
    $run('T04', 'Direct runtime root containing unrelated sentinel', 'Catch routine failure then COMMIT caller root; repeat failure with no BEGIN', 'Original 45000; failed statement0; unrelated caller survives, no routine rollback', 'V38/V42/V43', function () use ($c, $admin, $zero): array {
        $pdo = Prototype::connect($c);
        $pdo->beginTransaction();
        $pdo->exec("INSERT INTO poc_caller_sentinel VALUES (4,'survives')");
        $a = sqlError(fn () => Prototype::call($pdo, 'T04', fault: 'after_business'));
        check($pdo->inTransaction(), 'Routine rolled back caller root');
        $pdo->commit();
        $b = sqlError(fn () => Prototype::call($pdo, 'T04_raw', fault: 'after_evidence'));
        check(counts($admin, 'T04') === $zero && counts($admin, 'T04_raw') === $zero, 'Caught raw CALL retained partial work');
        check((int) $admin->query('SELECT COUNT(*) FROM poc_caller_sentinel WHERE id=4')->fetchColumn() === 1, 'Routine lost unrelated caller work');

        return ['actual' => [$a, $b], 'database_evidence' => ['failed_root' => counts($admin, 'T04'), 'failed_autocommit' => counts($admin, 'T04_raw'), 'sentinel_survived' => true], 'laravel_pdo' => ['connection_id' => (int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn(), 'pdo_active_after_commit' => $pdo->inTransaction()]];
    });
    $run('T05', 'Restricted runtime connected; T01 existing', 'Protected INSERT/UPDATE/DELETE, DDL, mysql schema read; session bypass attempts', 'Actual DB privilege denial, unchanged T01', 'V31/V33/V41/V43', function () use ($c, $admin, $one): array {
        $pdo = Prototype::connect($c);
        $pdo->exec('SET @allow_delete=1,@actor_id=101');
        $errors = [];
        foreach (["INSERT INTO poc_commands VALUES ('raw','x',101,1,'')", "UPDATE poc_commands SET amount=2 WHERE command_key='T01'", "DELETE FROM poc_commands WHERE command_key='T01'", "UPDATE poc_business SET amount=2 WHERE command_key='T01'", "DELETE FROM poc_evidence WHERE command_key='T01'", 'TRUNCATE TABLE poc_evidence', 'CREATE TABLE unauthorized(id INT)', 'SELECT * FROM mysql.user'] as $sql) {
            $error = sqlError(fn () => $pdo->exec($sql));
            check(in_array($error['vendor_code'], [1142, 1044], true), 'Expected privilege denial code');
            $errors[] = $error;
        }
        check(counts($admin, 'T01') === $one, 'DML denial lost original records');

        return ['actual' => $errors, 'database_evidence' => counts($admin, 'T01'), 'laravel_pdo' => ['direct_pdo' => true, 'connection_id' => (int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn()]];
    });
    $run('T06', 'Only complete public routine EXECUTE granted', 'Valid call and private-helper attempt', 'Complete command succeeds, private helper denied', 'V41/V43', function () use ($make, $c, $admin, $one): array {
        $executor = $make();
        $executor->execute('T06');
        $pdo = Prototype::connect($c);
        $denied = sqlError(fn () => $pdo->exec('CALL poc_private_helper()'));
        check($denied['vendor_code'] === 1370 && counts($admin, 'T06') === $one, 'Public/helper privilege scope wrong');

        return ['actual' => $denied, 'database_evidence' => counts($admin, 'T06'), 'laravel_pdo' => $executor->trace];
    });
    $run('T07', 'Stable identity and payload hash', 'Same command twice; changed payload using same key', 'One triplet, replay result; changed payload conflict', 'V05/V06/V43', function () use ($make, $admin, $one): array {
        $executor = $make();
        $first = $executor->execute('T07', 7);
        $second = $executor->execute('T07', 7);
        $error = sqlError(fn () => $executor->execute('T07', 8));
        check($first['payload_hash'] === $second['payload_hash'] && (int) $second['replayed'] === 1 && counts($admin, 'T07') === $one, 'Idempotency failed');

        return ['actual' => ['replayed' => true, 'changed_payload' => $error], 'database_evidence' => counts($admin, 'T07'), 'laravel_pdo' => $executor->trace];
    });
    $run('T08', 'Independent child runtime process, parent uncommitted key, pipe barrier', 'Worker submits same key; observer confirms actual unique-lock wait before parent commit', 'Two sessions, one effect; worker replay', 'V06/V40/V42/V43', function () use ($c, $admin, $one): array {
        $parent = Prototype::connection(Prototype::connect($c));
        $parent->beginTransaction();
        Prototype::call($parent->getPdo(), 'T08');
        $workerConfig = $c;
        unset($workerConfig['admin_password'], $workerConfig['definer_password'], $workerConfig['owned_data_root']);
        $env = [];
        foreach (['SystemRoot', 'WINDIR', 'TEMP', 'TMP', 'PATH'] as $name) {
            $value = getenv($name);
            if (is_string($value)) {
                $env[$name] = $value;
            }
        }
        $env['RMA_POC_PRIVATE_CONFIG'] = json_encode($workerConfig, JSON_THROW_ON_ERROR);
        $process = proc_open([PHP_BINARY, '-d', 'xdebug.mode=off', __DIR__.'/worker.php', 'T08'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__, $env);
        check(is_resource($process), 'Runtime worker start failed');
        try {
            stream_set_timeout($pipes[1], 12);
            $ready = json_decode((string) fgets($pipes[1]), true, flags: JSON_THROW_ON_ERROR);
            $workerId = $ready['state']['connection_id'];
            $parentState = Prototype::state($parent);
            check($workerId !== $parentState['connection_id'], 'Concurrency reused one session');
            fwrite($pipes[0], "go\n");
            fflush($pipes[0]);
            $waitSeen = false;
            $deadline = microtime(true) + 5;
            do {
                $stmt = $admin->prepare('SELECT COUNT(*) FROM performance_schema.data_lock_waits w JOIN performance_schema.threads t ON t.THREAD_ID=w.REQUESTING_THREAD_ID WHERE t.PROCESSLIST_ID=?');
                $stmt->execute([$workerId]);
                $waitSeen = (int) $stmt->fetchColumn() > 0;
                if (! $waitSeen) {
                    usleep(20000);
                }
            } while (! $waitSeen && microtime(true) < $deadline);
            check($waitSeen, 'No actual concurrent lock wait observed');
            $parent->commit();
            $result = json_decode((string) fgets($pipes[1]), true, flags: JSON_THROW_ON_ERROR);
            check(isset($result['result']) && (int) $result['result']['replayed'] === 1, 'Concurrent worker failed to replay');
            check(counts($admin, 'T08') === $one, 'Concurrent duplicate effects');

            return ['actual' => 'Actual lock wait observed; worker replayed after parent commit', 'database_evidence' => counts($admin, 'T08'), 'laravel_pdo' => ['parent' => $parentState, 'worker' => $result['trace'], 'actual_lock_wait' => true]];
        } finally {
            if ($parent->transactionLevel() > 0) {
                $parent->rollBack(0);
            }
            $parent->disconnect();
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_terminate($process);
            proc_close($process);
        }
    });
    $run('T09', 'Observer administrator holds synthetic mutex; runtime timeout1s', 'First root times out1205; release lock in retry hook; retry entire command on fresh session', 'One complete effect after bounded whole-root retry', 'V38/V40/V42/V43', function () use ($c, $admin, $one): array {
        $admin->beginTransaction();
        $admin->exec('UPDATE poc_mutex SET revision=revision WHERE id=1');
        $factory = function () use ($c) {
            $pdo = Prototype::connect($c);
            $pdo->exec('SET SESSION innodb_lock_wait_timeout=1');

            return Prototype::connection($pdo);
        };
        $executor = new Prototype($factory, fn () => []);
        try {
            $executor->execute('T09', retryHook: function () use ($admin): void {
                $admin->commit();
            });
        } finally {
            if ($admin->inTransaction()) {
                $admin->rollBack();
            }
        }
        $entries = array_values(array_filter($executor->trace, fn ($r) => $r['phase'] === 'entry'));
        $errors = array_values(array_filter($executor->trace, fn ($r) => $r['phase'] === 'error'));
        check(count($entries) === 2 && $entries[0]['connection_id'] !== $entries[1]['connection_id'] && count($errors) === 1 && counts($admin, 'T09') === $one, 'Whole-root lock retry not proven');

        return ['actual' => '1205 lock conflict; two attempts, distinct sessions, one command', 'database_evidence' => counts($admin, 'T09'), 'laravel_pdo' => $executor->trace, 'limitation' => 'Lock timeout exercised; genuine1213 deadlock not exercised'];
    });
    $run('T10', 'Deterministically withheld acknowledgement after actual commit', 'Executor reports UNKNOWN; fresh primary canonical lookup, no second execute', 'Durable result resolved without blind replay', 'V06/V40/V43', function () use ($make, $c, $admin, $one): array {
        $executor = $make();
        try {
            $executor->execute('T10', loseAcknowledgement: true);
            throw new RuntimeException('Expected unknown outcome');
        } catch (UnknownCommitOutcome) {
            $fresh = Prototype::connect($c);
            $stmt = $fresh->prepare('SELECT command_key,payload_hash FROM poc_commands WHERE command_key=?');
            $stmt->execute(['T10']);
            $resolved = $stmt->fetch(PDO::FETCH_ASSOC);
            check($resolved['payload_hash'] === hash('sha256', 'T10:1:101:') && counts($admin, 'T10') === $one, 'Unknown result not resolved');
        }

        return ['status' => 'PARTIAL', 'actual' => 'UNKNOWN signal and fresh-primary resolution verified; no replay', 'database_evidence' => counts($admin, 'T10'), 'laravel_pdo' => $executor->trace, 'limitation' => 'Fault was injected AFTER confirmed PDO commit; real network/commit ambiguity and in-flight wait NOT tested'];
    });
    $run('T11', 'Immutable T01 evidence; faults after business and after evidence', 'Fault both trigger locations; setup principal tries evidence UPDATE/DELETE', 'Atomic triplet0 after faults; trigger denies privileged immutable mutation', 'V33/V38/V43', function () use ($make, $admin, $zero): array {
        $executor = $make();
        $errors = [];
        foreach (['after_business', 'after_evidence'] as $fault) {
            $key = 'T11_'.$fault;
            $errors[] = sqlError(fn () => $executor->execute($key, fault: $fault));
            check(counts($admin, $key) === $zero, 'Evidence partial atomicity failure');
        }
        foreach (["UPDATE poc_evidence SET actor_id=102 WHERE command_key='T01'", "DELETE FROM poc_evidence WHERE command_key='T01'"] as $sql) {
            $error = sqlError(fn () => $admin->exec($sql));
            check($error['sqlstate'] === '45000', 'Immutable trigger did not enforce');
            $errors[] = $error;
        }

        return ['actual' => $errors, 'database_evidence' => ['fault_triplets_absent' => true, 'privileged_immutable_mutation_denied' => true], 'laravel_pdo' => $executor->trace];
    });
    $run('T12', 'Same executor after failed command, fresh lease factory', 'Fail command then issue valid second command', 'Failure root0; new connection/depth0; success clean', 'V38/V42/V43', function () use ($make, $admin, $one, $zero): array {
        $executor = $make();
        sqlError(fn () => $executor->execute('T12_bad', fault: 'after_business'));
        $executor->execute('T12_good');
        $entries = array_values(array_filter($executor->trace, fn ($r) => $r['phase'] === 'entry'));
        check(count($entries) === 2 && $entries[0]['connection_id'] !== $entries[1]['connection_id'] && counts($admin, 'T12_bad') === $zero && counts($admin, 'T12_good') === $one, 'Failed connection contaminated next operation');

        return ['actual' => 'Failed lease discarded; next fresh lease succeeded', 'database_evidence' => ['failed' => counts($admin, 'T12_bad'), 'next' => counts($admin, 'T12_good')], 'laravel_pdo' => $executor->trace];
    });
    $run('T13', 'Runtime101 authenticated, attacker supplies actor102 and session flags', 'Direct routine actor substitution and invalid hash', 'Actor/payload denial; zero writes', 'V14/V41/V43', function () use ($c, $admin, $zero): array {
        $pdo = Prototype::connect($c);
        $pdo->exec('SET @actor_id=102,@allow_delete=1');
        $error = sqlError(fn () => Prototype::call($pdo, 'T13', actor: 102));
        check($error['sqlstate'] === '45000' && counts($admin, 'T13') === $zero, 'Actor impersonation succeeded');
        $hashError = sqlError(function () use ($pdo): void {
            $s = $pdo->prepare('CALL poc_post(?,?,?,?,?)');
            try {
                $s->execute(['T13_hash', str_repeat('0', 64), 101, 1, '']);
            } finally {
                $s->closeCursor();
            }
        });

        return ['actual' => [$error, $hashError], 'database_evidence' => counts($admin, 'T13'), 'laravel_pdo' => ['connection_id' => (int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn(), 'actor_bound_to_login' => true], 'limitation' => 'One synthetic actor per DB principal; not general shared-login HTTP-user authentication'];
    });
    $run('T14', 'Existing service-style outer transaction on separate registered connection', 'Dedicated executor entry during outer caller root', 'Reject independent RMA commit; preserve caller root', 'V42/V43/V48', function () use ($factory, $admin, $zero): array {
        $caller = $factory();
        $caller->beginTransaction();
        $caller->getPdo()->exec("INSERT INTO poc_caller_sentinel VALUES (14,'service-style')");
        $executor = new Prototype($factory, fn () => [$caller]);
        $error = refused(fn () => $executor->execute('T14'));
        $state = Prototype::state($caller);
        check($state['laravel_depth'] === 1 && $state['pdo_active'] && counts($admin, 'T14') === $zero, 'Cross-service boundary failed');
        $caller->rollBack();
        $caller->disconnect();
        check((int) $admin->query('SELECT COUNT(*) FROM poc_caller_sentinel WHERE id=14')->fetchColumn() === 0, 'Caller rollback not preserved');

        return ['status' => 'PARTIAL', 'actual' => $error, 'database_evidence' => counts($admin, 'T14'), 'laravel_pdo' => $state, 'limitation' => 'Actual existing production/inventory services were NOT invoked or modified; registered-connection contract simulation only'];
    });
    $evidence['limitations'] = [
        'Minimal single-INSERT/trigger complete command only; no full RMA finalizer/schema/lifecycle or shared inventory proof.',
        'No routines COMMIT/ROLLBACK; statement atomicity preserves unrelated caller writes for caught raw CALL error. Not general multi-statement CALL totality.',
        'One synthetic actor bound to runtime database login; shared-principal backend actor binding and deployment secret isolation unproven.',
        'Executor only knows explicitly registered caller connections; global application/worker scope discovery unproven.',
        'T10 acknowledgement loss is simulated after successful commit, not actual network/unknown-commit ambiguity.',
        'T14 simulates existing service transaction shape without running existing application services.',
        'Real1213 deadlock, afterCommit cache callbacks, persistent/pool lifecycle, mode0 driver and complete canonical48 remain unverified.',
    ];
    $evidence['verdict'] = $failure ? 'POC FAILED' : 'POC PARTIALLY VERIFIED';
} catch (Throwable $error) {
    $failure = true;
    $evidence['fatal'] = ['error_class' => $error::class, 'code' => $error->getCode(),
        'reason' => $error instanceof PDOException ? 'Database setup failed; SQL/secret values suppressed' : $error->getMessage()];
} finally {
    $evidence['source_hashes'] = [];
    foreach (['Prototype.php', 'worker.php', 'run.php', 'schema.mysql', 'launch.ps1'] as $source) {
        $evidence['source_hashes'][$source] = hash_file('sha256', __DIR__.'/'.$source);
    }
    $target = __DIR__.'/evidence';
    if (! is_dir($target)) {
        mkdir($target, 0755, true);
    }
    file_put_contents($target.'/results.json', json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    if ($admin !== null && ! $admin->inTransaction()) {
        try {
            $admin->exec('SHUTDOWN');
        } catch (Throwable) { /* launcher stops only its owned child PID */
        }
    }
}
echo $evidence['verdict'].PHP_EOL;
exit($failure ? 1 : 0);
