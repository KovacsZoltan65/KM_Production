<?php

declare(strict_types=1);

// Isolated SQL prototypes only. Never boots Laravel or reads production credentials.
ini_set('zend.exception_ignore_args', '1');

final class RmaVerificationSetupException extends RuntimeException {}

function safeVerificationError(Throwable $error): string
{
    if ($error instanceof RmaVerificationSetupException) {
        // Only fixed setup messages and allowlisted variable names are used here.
        return $error->getMessage();
    }

    if ($error instanceof PDOException) {
        $state = (string) ($error->errorInfo[0] ?? '');
        $state = preg_match('/^[A-Z0-9]{5}$/D', $state) === 1 ? $state : 'unknown';
        $driverCode = (int) ($error->errorInfo[1] ?? 0);

        return "Database operation failed (SQLSTATE $state, driver $driverCode); driver text omitted.";
    }

    return 'Verification check failed; inspect the named SQL prototype. Exception details omitted.';
}

set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, safeVerificationError($error).PHP_EOL);
    exit(1);
});

$config = [];
$requiredVariables = [
    'RMA_VERIFY_DB_HOST',
    'RMA_VERIFY_DB_PORT',
    'RMA_VERIFY_DB_NAME',
    'RMA_VERIFY_DB_USER',
    'RMA_VERIFY_DB_PASSWORD',
];
$missingVariables = [];
foreach ($requiredVariables as $variable) {
    $value = getenv($variable);
    if ($value === false || $value === '') {
        $missingVariables[] = $variable;
    } else {
        $config[$variable] = $value;
    }
}
if ($missingVariables !== []) {
    throw new RmaVerificationSetupException(
        'Missing required configuration: '.implode(', ', $missingVariables).
        '. Set these variables in the local process environment for an isolated disposable verification database.',
    );
}
$host = $config['RMA_VERIFY_DB_HOST'];
$port = $config['RMA_VERIFY_DB_PORT'];
$db = $config['RMA_VERIFY_DB_NAME'];
$user = $config['RMA_VERIFY_DB_USER'];
$password = $config['RMA_VERIFY_DB_PASSWORD'];
unset($config, $value);
if ($host !== '127.0.0.1') {
    throw new RmaVerificationSetupException('RMA_VERIFY_DB_HOST must be the loopback IPv4 address for an isolated local verification database.');
}
if (filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
    throw new RmaVerificationSetupException('RMA_VERIFY_DB_PORT must be an integer from 1 to 65535.');
}
if (preg_match('/^km_production_(?:test|testing)(?:_[a-z0-9]+)?$/D', $db) !== 1) {
    throw new RmaVerificationSetupException('RMA_VERIFY_DB_NAME must explicitly name a disposable database matching the verification-only test/testing name allowlist.');
}
$connect = static function () use ($host, $port, $db, $user, $password): PDO {
    try {
        return new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException) {
        // Do not chain the driver exception: its message/trace may contain credentials.
        throw new RmaVerificationSetupException(
            'Verification database connection failed. Check RMA_VERIFY_DB_* settings and local test database access; connection details omitted.',
        );
    }
};
$out = ['kind' => 'executed SQL prototypes, not application tests', 'utc' => gmdate('c'), 'tests' => [], 'audit' => []];
function check(bool $ok, string $message): void
{
    if (! $ok) {
        throw new RuntimeException($message);
    }
}
function rejects(PDO $p, string $sql, ?int $driverCode = null): void
{
    try {
        $p->exec($sql);
    } catch (PDOException $e) {
        check(in_array((int) $e->errorInfo[1], $driverCode === null ? [19, 1048, 1062, 1451, 1452, 1644, 3819] : [$driverCode], true), 'Unexpected rejection: '.safeVerificationError($e));

        return;
    }
    throw new RuntimeException('Expected rejection: '.$sql);
}
function test(string $id, callable $fn): void
{
    global $out;
    try {
        $detail = $fn();
        $out['tests'][] = ['id' => $id, 'status' => 'PASS', 'detail' => $detail];
    } catch (Throwable $e) {
        $out['tests'][] = ['id' => $id, 'status' => 'FAIL', 'detail' => safeVerificationError($e)];
    }
}
$mysql = $connect();
$out['mysql'] = $mysql->query('SELECT VERSION() AS version, @@transaction_isolation AS isolation_level')->fetch(PDO::FETCH_ASSOC);
$prefix = 'rma3b_'.bin2hex(random_bytes(5)).'_';
$out['mysql']['disposable_prefix'] = $prefix;
$created = [];
function createTable(PDO $p, string $name, string $columns, bool $my): void
{
    global $created;
    $p->exec("CREATE TABLE $name ($columns)".($my ? ' ENGINE=InnoDB' : ''));
    if ($my) {
        $created[] = $name;
    }
}
try {
    foreach (['mysql' => $mysql, 'sqlite' => new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])] as $engine => $p) {
        $my = $engine === 'mysql';
        $n = $my ? $prefix : 's_';
        if (! $my) {
            $p->exec('PRAGMA foreign_keys=ON');
            $out['sqlite'] = ['version' => $p->query('SELECT sqlite_version()')->fetchColumn(), 'foreign_keys' => (int) $p->query('PRAGMA foreign_keys')->fetchColumn()];
        }
        $table = function (string $suffix, string $columns) use ($p, $n, $my) {
            createTable($p, $n.$suffix, $columns, $my);
        };
        test("$engine:FND02-nullable", function () use ($table, $p, $n) {
            $table('nullable', 'id INT PRIMARY KEY, batch INT NULL, UNIQUE(batch)');
            $p->exec("INSERT INTO {$n}nullable VALUES(1,NULL),(2,NULL)");
            rejects($p, "INSERT INTO {$n}nullable VALUES(3,7),(4,7)");

            return 'Multiple NULL accepted; duplicate non-NULL rejected';
        });
        test("$engine:FND02-sentinel", function () use ($table, $p, $n) {
            $table('balance', 'id INT PRIMARY KEY, item INT NOT NULL, loc INT NOT NULL, batch INT NULL, identity_key INT NOT NULL, UNIQUE(item,loc,identity_key), CHECK((batch IS NULL AND identity_key=0) OR (batch IS NOT NULL AND identity_key=batch AND identity_key>0))');
            $p->exec("INSERT INTO {$n}balance VALUES(1,1,1,NULL,0),(2,1,1,5,5)");
            rejects($p, "INSERT INTO {$n}balance VALUES(3,1,1,NULL,0)");
            rejects($p, "INSERT INTO {$n}balance VALUES(3,1,1,NULL,9)");
            rejects($p, "INSERT INTO {$n}balance VALUES(3,1,1,5,0)");
            rejects($p, "INSERT INTO {$n}balance VALUES(3,1,1,NULL,NULL)");

            return 'Sentinel mapping and NULL-batch uniqueness enforced';
        });
        test("$engine:FND01-composite", function () use ($table, $p, $n) {
            $table('member', 'id INT PRIMARY KEY, serial INT NOT NULL, UNIQUE(id,serial)');
            $table('claim', "serial INT PRIMARY KEY, member INT NOT NULL UNIQUE, FOREIGN KEY(member,serial) REFERENCES {$n}member(id,serial)");
            $p->exec("INSERT INTO {$n}member VALUES(1,11),(2,12)");
            $p->exec("INSERT INTO {$n}claim VALUES(11,1)");
            rejects($p, "INSERT INTO {$n}claim VALUES(12,1)");
            rejects($p, "DELETE FROM {$n}member WHERE id=1");
            $p->exec("DELETE FROM {$n}claim WHERE serial=11");
            check((int) $p->query("SELECT COUNT(*) FROM {$n}claim")->fetchColumn() === 0, 'delete observation');

            return 'Composite mismatch/parent deletion rejected; raw claim deletion ALLOWED: needs restricted write route';
        });
        test("$engine:FND07-null-tuple-check", function () use ($table, $p, $n) {
            $table('nullable_fk', "id INT PRIMARY KEY, member INT NULL, serial INT NULL, FOREIGN KEY(member,serial) REFERENCES {$n}member(id,serial), CHECK(serial>0)");
            $p->exec("INSERT INTO {$n}nullable_fk VALUES(1,999,NULL)");
            $table('strict_tuple', "id INT PRIMARY KEY, member INT NULL, serial INT NULL, FOREIGN KEY(member,serial) REFERENCES {$n}member(id,serial), CHECK((member IS NULL AND serial IS NULL) OR (member IS NOT NULL AND serial IS NOT NULL AND serial>0))");
            rejects($p, "INSERT INTO {$n}strict_tuple VALUES(1,999,NULL)");

            return 'Partial NULL bypass reproduced; explicit NULL branches reject it';
        });
        test("$engine:FND07-sentinel-fk", function () use ($table, $p, $n) {
            $table('batch_parent', 'id INT PRIMARY KEY');
            $table('batch_fk', "id INT PRIMARY KEY, batch INT NULL, identity_key INT NOT NULL, FOREIGN KEY(batch) REFERENCES {$n}batch_parent(id) ON UPDATE RESTRICT ON DELETE RESTRICT, CHECK((batch IS NULL AND identity_key=0) OR (batch IS NOT NULL AND identity_key=batch AND identity_key>0)), UNIQUE(identity_key)");
            $p->exec("INSERT INTO {$n}batch_parent VALUES(5)");
            $p->exec("INSERT INTO {$n}batch_fk VALUES(1,NULL,0),(2,5,5)");
            rejects($p, "INSERT INTO {$n}batch_fk VALUES(3,6,6)");
            rejects($p, "DELETE FROM {$n}batch_parent WHERE id=5");

            return 'Sentinel CHECK + real batch FK + RESTRICT coexist';
        });
        test("$engine:FND03-warranty", function () use ($table, $p, $n) {
            $table('instance', 'id INT PRIMARY KEY, item INT NOT NULL, UNIQUE(id,item)');
            $table('evidence', 'id INT PRIMARY KEY, serial INT NOT NULL, UNIQUE(id,serial)');
            $table('rule', 'id INT PRIMARY KEY, item INT NOT NULL, UNIQUE(id,item)');
            $table('assessment', "id INT PRIMARY KEY, ord INT NOT NULL, serial INT NOT NULL, item INT NOT NULL, version INT NOT NULL, evidence INT NULL, rule INT NULL, UNIQUE(ord,version), FOREIGN KEY(serial,item) REFERENCES {$n}instance(id,item), FOREIGN KEY(evidence,serial) REFERENCES {$n}evidence(id,serial), FOREIGN KEY(rule,item) REFERENCES {$n}rule(id,item)");
            $p->exec("INSERT INTO {$n}instance VALUES(11,1),(12,2)");
            $p->exec("INSERT INTO {$n}evidence VALUES(1,11),(2,12)");
            $p->exec("INSERT INTO {$n}rule VALUES(1,1),(2,2)");
            $p->exec("INSERT INTO {$n}assessment VALUES(1,7,11,1,1,1,1),(2,7,12,2,2,2,2)");
            rejects($p, "INSERT INTO {$n}assessment VALUES(3,7,12,2,3,1,2)");
            rejects($p, "INSERT INTO {$n}assessment VALUES(3,7,12,2,3,2,1)");
            rejects($p, "INSERT INTO {$n}assessment VALUES(3,7,12,1,3,2,1)");
            rejects($p, "INSERT INTO {$n}assessment VALUES(3,7,12,2,2,2,2)");

            return 'Original/replacement evidence, item/rule and global order version enforced; membership still service guard';
        });
        test("$engine:FND04-REV02-attempt", function () use ($table, $p, $n) {
            $table('release', 'id INT PRIMARY KEY, member INT NOT NULL, serial INT NOT NULL, role INT NOT NULL, attempt INT NOT NULL, movement INT NOT NULL UNIQUE, command_key INT NOT NULL UNIQUE, UNIQUE(member,serial,role,attempt), CHECK(attempt>=1)');
            $p->exec("INSERT INTO {$n}release VALUES(1,1,11,1,1,1,1),(2,1,11,1,2,2,2)");
            rejects($p, "INSERT INTO {$n}release VALUES(3,1,11,1,2,3,3)");
            rejects($p, "INSERT INTO {$n}release VALUES(3,1,11,1,3,2,3)");
            $table('ownership', 'id INT PRIMARY KEY, member INT NOT NULL, serial INT NOT NULL, trigger_name VARCHAR(80) NOT NULL, sequence_no INT NOT NULL, source_key VARCHAR(80) NOT NULL UNIQUE, UNIQUE(member,serial,sequence_no)');
            $p->exec("INSERT INTO {$n}ownership VALUES(1,1,11,'same_condition',1,'first'),(2,1,11,'same_condition',2,'second')");
            rejects($p, "INSERT INTO {$n}ownership VALUES(3,1,11,'same_condition',3,'second')");

            return 'Repeated real shipments/events accepted; same attempt/movement/source occurrence rejected';
        });
        test("$engine:FND05-active-credit", function () use ($table, $p, $n) {
            $table('credit', 'id INT PRIMARY KEY, source_order INT NOT NULL, active_source INT NULL UNIQUE, amount INT NOT NULL, state VARCHAR(16) NOT NULL, CHECK(amount>0), CHECK((state IN (\'held\',\'applied\') AND active_source IS NOT NULL AND active_source=source_order) OR (state=\'released\' AND active_source IS NULL))');
            $p->exec("INSERT INTO {$n}credit VALUES(1,7,7,100,'held')");
            rejects($p, "INSERT INTO {$n}credit VALUES(2,7,7,100,'held')");
            rejects($p, "INSERT INTO {$n}credit VALUES(2,7,NULL,100,'applied')");
            $p->exec("UPDATE {$n}credit SET state='released',active_source=NULL WHERE id=1");
            $p->exec("INSERT INTO {$n}credit VALUES(2,7,7,100,'applied')");

            return 'One active allocation per source; released history preserved; release permission/policy unproven';
        });
        test("$engine:scope-owner", function () use ($table, $p, $n) {
            $table('scope_owner', "id INT PRIMARY KEY, scope VARCHAR(24) NOT NULL, repair INT NULL, customer INT NULL, production INT NULL, CHECK(scope IN('company_stock','customer_custody','external')), CHECK((repair IS NOT NULL AND customer IS NULL AND production IS NULL) OR (repair IS NULL AND (customer IS NOT NULL OR production IS NOT NULL)))");
            $p->exec("INSERT INTO {$n}scope_owner VALUES(1,'company_stock',NULL,1,1),(2,'customer_custody',1,NULL,NULL)");
            rejects($p, "INSERT INTO {$n}scope_owner VALUES(3,'other',1,NULL,NULL)");
            rejects($p, "INSERT INTO {$n}scope_owner VALUES(3,'company_stock',1,1,NULL)");
            rejects($p, "INSERT INTO {$n}scope_owner VALUES(3,'company_stock',NULL,NULL,NULL)");

            return 'Three scopes and repair/legacy owner XOR; balance posting isolation remains service test';
        });
        test("$engine:REV01-trigger", function () use ($table, $p, $n, $my) {
            $table('event', 'id INT PRIMARY KEY, correction_of INT NULL');
            $p->exec("INSERT INTO {$n}event VALUES(1,NULL)");
            foreach (['UPDATE', 'DELETE'] as $action) {
                $trigger = $n.'deny_'.strtolower($action);
                $sql = $my ? "CREATE TRIGGER $trigger BEFORE $action ON {$n}event FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='append only'" : "CREATE TRIGGER $trigger BEFORE $action ON {$n}event BEGIN SELECT RAISE(ABORT,'append only'); END";
                $p->exec($sql);
            }
            rejects($p, "UPDATE {$n}event SET correction_of=1 WHERE id=1");
            rejects($p, "DELETE FROM {$n}event WHERE id=1");
            $p->exec("INSERT INTO {$n}event VALUES(2,1)");

            return 'Disposable append-only triggers reject UPDATE/DELETE, correction INSERT accepted; privileges not deployed';
        });
        test("$engine:atomic-rollback", function () use ($table, $p, $n) {
            $table('atomic_log', 'id INT PRIMARY KEY, fact INT NOT NULL UNIQUE');
            $p->beginTransaction();
            try {
                $p->exec("INSERT INTO {$n}atomic_log VALUES(1,1)");
                $p->exec("INSERT INTO {$n}atomic_log VALUES(2,1)");
            } catch (PDOException $e) {
                $p->rollBack();
            }
            check((int) $p->query("SELECT COUNT(*) FROM {$n}atomic_log")->fetchColumn() === 0, 'partial commit');

            return 'Fault after fact insertion rolls back fact/audit prototype';
        });
    }
    test('mysql:FND07-referential-actions', function () use ($mysql, $prefix) {
        createTable($mysql, $prefix.'action_parent', 'id INT PRIMARY KEY', true);
        foreach (['RESTRICT', 'NO ACTION', 'CASCADE', 'SET NULL'] as $i => $action) {
            $name = $prefix.'action_'.$i;
            try {
                createTable($mysql, $name, "id INT PRIMARY KEY, parent INT NULL, CHECK(parent IS NULL OR parent>0), FOREIGN KEY(parent) REFERENCES {$prefix}action_parent(id) ON UPDATE $action ON DELETE $action", true);
                $observed[$action] = 'accepted';
            } catch (PDOException $e) {
                $observed[$action] = ['rejected' => (int) $e->errorInfo[1], 'message' => safeVerificationError($e)];
            }
        }
        check($observed['RESTRICT'] === 'accepted' && $observed['NO ACTION'] === 'accepted' && ($observed['CASCADE']['rejected'] ?? null) === 3823 && ($observed['SET NULL']['rejected'] ?? null) === 3823, 'Unexpected FK/CHECK compatibility');

        return $observed;
    });
    test('mysql:FND07-auto-increment-check', function () use ($mysql, $prefix) {
        rejects($mysql, "CREATE TABLE {$prefix}invalid_auto (id INT AUTO_INCREMENT PRIMARY KEY, CHECK(id>0)) ENGINE=InnoDB", 3818);

        return 'CHECK on AUTO_INCREMENT rejected (3818)';
    });
    test('mysql:FND07-cycle-index-engine', function () use ($mysql, $prefix) {
        createTable($mysql, $prefix.'cycle_a', 'id INT PRIMARY KEY, b INT NULL', true);
        createTable($mysql, $prefix.'cycle_b', "id INT PRIMARY KEY, a INT NULL, FOREIGN KEY(a) REFERENCES {$prefix}cycle_a(id)", true);
        $mysql->exec("ALTER TABLE {$prefix}cycle_a ADD FOREIGN KEY(b) REFERENCES {$prefix}cycle_b(id)");
        $mysql->exec("INSERT INTO {$prefix}cycle_a VALUES(1,NULL)");
        $mysql->exec("INSERT INTO {$prefix}cycle_b VALUES(1,1)");
        $mysql->exec("UPDATE {$prefix}cycle_a SET b=1 WHERE id=1");
        rejects($mysql, "INSERT INTO {$prefix}cycle_a VALUES(2,999)");
        $engine = $mysql->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}cycle_a'")->fetchColumn();
        check($engine === 'InnoDB', 'wrong engine');
        $indexes = $mysql->query("SHOW INDEX FROM {$prefix}claim")->fetchAll(PDO::FETCH_ASSOC);
        $mysql->exec("UPDATE {$prefix}cycle_a SET b=NULL");
        $mysql->exec("DELETE FROM {$prefix}cycle_b");
        // Remove only our FK to permit reverse-order cleanup of the empty cycle.
        $fk = $mysql->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}cycle_a' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetchColumn();
        check(is_string($fk) && preg_match('/^[a-zA-Z0-9_]+$/D', $fk) === 1, 'unsafe FK');
        $mysql->exec("ALTER TABLE {$prefix}cycle_a DROP FOREIGN KEY $fk");

        return ['engine' => $engine, 'claim_indexes' => array_values(array_unique(array_column($indexes, 'Key_name'))), 'cycle' => 'nullable insert then link; missing target rejected'];
    });
    $b = $connect();
    $b->exec('SET SESSION innodb_lock_wait_timeout=1');
    $out['connections'] = ['a' => $mysql->query('SELECT CONNECTION_ID()')->fetchColumn(), 'b' => $b->query('SELECT CONNECTION_ID()')->fetchColumn()];
    foreach (range(1, 10) as $c) {
        test("mysql:C$c", function () use ($mysql, $b, $prefix, $c) {
            $name = $prefix.'race_'.$c;
            createTable($mysql, $name, 'id INT PRIMARY KEY, owner_id INT NULL UNIQUE, value_no INT NOT NULL', true);
            $unique = in_array($c, [1, 2, 3, 4, 10], true);
            if (! $unique) {
                $mysql->exec("INSERT INTO $name VALUES(1,NULL,10)");
            }
            $mysql->beginTransaction();
            $b->beginTransaction();
            try {
                if ($unique) {
                    $mysql->exec("INSERT INTO $name VALUES(1,77,1)");
                } else {
                    $mysql->query("SELECT * FROM $name WHERE id=1 FOR UPDATE")->fetchAll();
                    $mysql->exec("UPDATE $name SET value_no=".($c === 5 ? '3' : '0').' WHERE id=1');
                }
                $start = microtime(true);
                rejects($b, $unique ? "INSERT INTO $name VALUES(2,77,1)" : "SELECT * FROM $name WHERE id=1 FOR UPDATE", 1205);
                $elapsed = microtime(true) - $start;
                check($elapsed >= 0.8, 'no overlapping lock wait observed');
                $b->rollBack();
                $mysql->commit();
                $b->beginTransaction();
                if ($unique) {
                    rejects($b, "INSERT INTO $name VALUES(2,77,1)", 1062);
                } else {
                    $current = (int) $b->query("SELECT value_no FROM $name WHERE id=1 FOR UPDATE")->fetchColumn();
                    check($current === ($c === 5 ? 3 : 0), 'stale locked state');
                    // Prototype guard: insufficient stock / closed case / no approval /
                    // already generated coverage / already installed child.
                    check($c === 5 ? $current < 7 : $current === 0, 'guard failed');
                }
                $b->commit();
                check((int) $mysql->query("SELECT COUNT(*) FROM $name")->fetchColumn() === 1, 'duplicate fact');

                return ['overlap_seconds' => round($elapsed, 3), 'mechanism' => $unique ? 'UNIQUE + retry duplicate rejected' : 'parent row lock + retry current-state service prototype guard', 'scope' => 'SQL mechanism only; no real domain movement/proof/policy assertions'];
            } finally {
                if ($b->inTransaction()) {
                    $b->rollBack();
                }
                if ($mysql->inTransaction()) {
                    $mysql->rollBack();
                }
            }
        });
    }
    // Existing test dataset only. Never read production/development connection settings.
    $queries = [
        'duplicate_balances' => 'SELECT COUNT(*) FROM (SELECT item_id,location_id,item_batch_id FROM stock_balances GROUP BY item_id,location_id,item_batch_id HAVING COUNT(*)>1) d',
        'orphan_production_references' => 'SELECT COUNT(*) FROM item_instances i LEFT JOIN production_orders p ON p.id=i.production_order_id WHERE i.production_order_id IS NOT NULL AND p.id IS NULL',
        'missing_reservation_owners' => 'SELECT COUNT(*) FROM stock_reservations WHERE customer_order_item_id IS NULL AND production_order_id IS NULL',
        'invalid_reservation_quantities' => 'SELECT COUNT(*) FROM stock_reservations WHERE reserved_quantity<=0',
        'missing_serial_origin' => "SELECT COUNT(*) FROM item_instances WHERE serial_number IS NULL OR serial_number='' OR production_order_id IS NULL",
        'invalid_procurement_sources' => 'SELECT COUNT(*) FROM purchase_requisition_item_sources s LEFT JOIN material_requirements m ON m.id=s.material_requirement_id LEFT JOIN purchase_requisition_items p ON p.id=s.purchase_requisition_item_id WHERE m.id IS NULL OR p.id IS NULL',
        'genealogy_self_edges' => 'SELECT COUNT(*) FROM item_instance_relations WHERE parent_item_instance_id=child_item_instance_id',
        'multiple_genealogy_parents' => 'SELECT COUNT(*) FROM (SELECT child_item_instance_id FROM item_instance_relations GROUP BY child_item_instance_id HAVING COUNT(DISTINCT parent_item_instance_id)>1) d',
        'negative_balances' => 'SELECT COUNT(*) FROM stock_balances WHERE quantity<0',
    ];
    $mysql->exec('SET TRANSACTION READ ONLY');
    $mysql->beginTransaction();
    foreach (['stock_balances', 'stock_movements', 'stock_reservations', 'item_instances', 'material_requirements', 'purchase_requisition_item_sources', 'item_instance_relations'] as $table) {
        $out['audit_rows'][$table] = (int) $mysql->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }
    foreach ($queries as $id => $sql) {
        try {
            $out['audit'][$id] = ['status' => 'EXECUTED', 'count' => (int) $mysql->query($sql)->fetchColumn(), 'sql' => $sql];
        } catch (PDOException $e) {
            $out['audit'][$id] = ['status' => 'BLOCKED', 'reason' => safeVerificationError($e), 'sql' => $sql];
        }
    }
    $mysql->rollBack();
    $out['audit_scope'] = 'Dedicated test dataset only; no representative legacy certification. Movement reconciliation and full genealogy cycle/origin evidence require business evidence and separate snapshot.';
} finally {
    $out['cleanup'] = [];
    foreach (array_reverse($created) as $name) {
        check(str_starts_with($name, $prefix) && preg_match('/^rma3b_[a-f0-9]{10}_[a-z0-9_]+$/D', $name) === 1, 'unsafe cleanup');
        try {
            $mysql->exec("DROP TABLE $name");
            $out['cleanup'][$name] = 'dropped';
        } catch (Throwable $e) {
            $out['cleanup'][$name] = 'FAILED: '.safeVerificationError($e);
        }
    }
}
$out['summary'] = ['passed' => count(array_filter($out['tests'], fn ($x) => $x['status'] === 'PASS')), 'failed' => count(array_filter($out['tests'], fn ($x) => $x['status'] === 'FAIL'))];
file_put_contents(__DIR__.'/rma-0003b-results.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
echo json_encode($out['summary']).PHP_EOL;
exit($out['summary']['failed'] || count(array_filter($out['cleanup'], fn ($x) => $x !== 'dropped')) ? 1 : 0);
