<?php

declare(strict_types=1);

use RmaTransactionBoundaryPoc\Prototype;

require __DIR__.'/Prototype.php';

// Runtime-only process: parent deliberately omits administrative/definer secrets.
$c = Prototype::configuration();
if (isset($c['admin_password']) || isset($c['definer_password'])) {
    throw new RuntimeException('Worker must not inherit administrative secrets');
}
$pdo = Prototype::connect($c);
$connection = Prototype::connection($pdo);
$state = Prototype::state($connection);
fwrite(STDOUT, json_encode(['ready' => true, 'state' => $state])."\n");
fflush(STDOUT);
if (trim((string) fgets(STDIN)) !== 'go') {
    exit(2);
}
try {
    $executor = new Prototype(fn () => $connection, fn () => []);
    $result = $executor->execute($argv[1]);
    fwrite(STDOUT, json_encode(['result' => $result, 'trace' => $executor->trace])."\n");
} catch (Throwable $error) {
    fwrite(STDOUT, json_encode(['error_class' => $error::class, 'code' => $error->getCode()])."\n");
    exit(1);
}
