<?php

use PHPUnit\Framework\Assert;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

function ciMysqlShellPath(string $path): string
{
    $path = str_replace('\\', '/', $path);

    return preg_replace_callback('/^([A-Za-z]):\//', fn (array $match): string => '/'.strtolower($match[1]).'/', $path);
}

it('protects MySQL credentials and cleans temporary files on success and failure', function (bool $fails) {
    $root = dirname(__DIR__, 3);
    $bash = PHP_OS_FAMILY === 'Windows' ? 'C:/Program Files/Git/bin/bash.exe' : (new ExecutableFinder)->find('bash');
    if (! is_string($bash) || ! is_file($bash)) {
        Assert::markTestSkipped('Bash is required for the CI MySQL client regression.');
    }
    $directory = $root.'/storage/framework/testing/ci-mysql-test-'.bin2hex(random_bytes(8));
    mkdir($directory.'/bin', 0700, true);
    mkdir($directory.'/tmp', 0700);
    $sentinel = 'CI_SYNTHETIC_'.bin2hex(random_bytes(16));
    $password = " {$sentinel}#;\"'\\\t\r\nend ";
    $fake = <<<'BASH'
#!/usr/bin/env bash
set -eu
config=${1#--defaults-file=}
printf '%s\n' "$@" > "$CI_CAPTURE/arguments"
printf '%s\n' "$config" > "$CI_CAPTURE/path"
cp "$config" "$CI_CAPTURE/config"
stat -c '%a' "$config" > "$CI_CAPTURE/mode"
stat -c '%a' "$(dirname "$config")" > "$CI_CAPTURE/directory-mode"
if [[ "$CI_FAKE_FAIL" == 1 ]]; then
    printf '%s\n' "$TEST_MYSQL_PASSWORD"
    printf '%s\n' "$TEST_MYSQL_PASSWORD" >&2
    exit 7
fi
printf '1\n'
BASH;
    file_put_contents($directory.'/bin/mysql', $fake);
    chmod($directory.'/bin/mysql', 0700);

    try {
        $process = new Process([$bash, '-c', 'PATH="$CI_FAKE_BIN:$PATH"; export PATH; bash scripts/ci-mysql-client.sh probe'], $root, [
            'CI_FAKE_BIN' => ciMysqlShellPath($directory.'/bin'),
            'CI_CAPTURE' => ciMysqlShellPath($directory),
            'CI_FAKE_FAIL' => $fails ? '1' : '0',
            'RUNNER_TEMP' => ciMysqlShellPath($directory.'/tmp'),
            'TEST_MYSQL_HOST' => '127.0.0.1',
            'TEST_MYSQL_PORT' => '33060',
            'TEST_MYSQL_DATABASE' => 'km_production_testing',
            'TEST_MYSQL_USERNAME' => 'km_testing',
            'TEST_MYSQL_PASSWORD' => $password,
            'MYSQL_PWD' => $sentinel,
        ]);
        $process->run();
        expect($process->getExitCode())->toBe($fails ? 1 : 0);
        $arguments = file_get_contents($directory.'/arguments');
        $output = $process->getOutput().$process->getErrorOutput();
        expect(str_contains($arguments.$output, $sentinel))->toBeFalse()
            ->and(str_contains($arguments, '--password'))->toBeFalse()
            ->and(str_contains($arguments, '--no-login-paths'))->toBeTrue()
            ->and(glob($directory.'/tmp/*'))->toBe([]);
        $configuration = file_get_contents($directory.'/config');
        $escaped = strtr($password, ['\\' => '\\\\', '"' => '\\"', "\t" => '\\t', "\r" => '\\r', "\n" => '\\n']);
        expect(str_contains($configuration, 'password="'.$escaped.'"'))->toBeTrue()
            ->and(substr_count($configuration, "\npassword="))->toBe(1);
        if (PHP_OS_FAMILY !== 'Windows') {
            expect(trim(file_get_contents($directory.'/mode')))->toBe('600')
                ->and(trim(file_get_contents($directory.'/directory-mode')))->toBe('700');
        }
    } finally {
        foreach (glob($directory.'/bin/*') as $file) {
            unlink($file);
        }
        foreach (glob($directory.'/*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($directory.'/bin');
        rmdir($directory.'/tmp');
        rmdir($directory);
    }
})->with([false, true]);

it('refuses unsafe MySQL targets before invoking the client', function () {
    $root = dirname(__DIR__, 3);
    $bash = PHP_OS_FAMILY === 'Windows' ? 'C:/Program Files/Git/bin/bash.exe' : (new ExecutableFinder)->find('bash');
    if (! is_string($bash) || ! is_file($bash)) {
        Assert::markTestSkipped('Bash is required for the CI MySQL client regression.');
    }
    $process = new Process([$bash, 'scripts/ci-mysql-client.sh', 'probe'], $root, [
        'TEST_MYSQL_DATABASE' => 'production',
    ]);
    $process->run();
    expect($process->getExitCode())->toBe(1)
        ->and(trim($process->getErrorOutput()))->toBe('MySQL test environment guard failed.')
        ->and($process->getOutput())->toBe('');
});
