[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.IO.Compression.FileSystem

$exportScript = Join-Path $PSScriptRoot 'export-project.ps1'
$powerShellExecutable = (Get-Process -Id $PID).Path
$temporaryRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("km-export-tests-{0}" -f [guid]::NewGuid().ToString('N'))
$failures = @()

function New-TestArchive {
    param(
        [Parameter(Mandatory = $true)][string] $ArchivePath,
        [Parameter(Mandatory = $true)][string[]] $Entries
    )

    $archive = [System.IO.Compression.ZipFile]::Open($ArchivePath, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        foreach ($entryName in $Entries) {
            $entry = $archive.CreateEntry($entryName)
            $writer = [System.IO.StreamWriter]::new($entry.Open())
            try {
                $writer.Write('test')
            }
            finally {
                $writer.Dispose()
            }
        }
    }
    finally {
        $archive.Dispose()
    }
}

try {
    New-Item -ItemType Directory -Path $temporaryRoot | Out-Null

    $cases = @(
        @{ Name = '.env elutasítva'; Entry = '.env'; ExpectedExitCode = 2 },
        @{ Name = '.env.testing elutasítva'; Entry = '.env.testing'; ExpectedExitCode = 2 },
        @{ Name = '.env.example engedélyezve'; Entry = '.env.example'; ExpectedExitCode = 0 },
        @{ Name = 'debug.log elutasítva'; Entry = 'debug.log'; ExpectedExitCode = 2 },
        @{ Name = '_ide_helper.php elutasítva'; Entry = '_ide_helper.php'; ExpectedExitCode = 2 },
        @{ Name = 'vendor elutasítva'; Entry = 'vendor/package/file.php'; ExpectedExitCode = 2 },
        @{ Name = 'node_modules elutasítva'; Entry = 'node_modules/package/index.js'; ExpectedExitCode = 2 },
        @{ Name = 'normál forrásfájl engedélyezve'; Entry = 'app/Services/ExampleService.php'; ExpectedExitCode = 0 }
    )

    for ($index = 0; $index -lt $cases.Count; $index++) {
        $case = $cases[$index]
        $archivePath = Join-Path $temporaryRoot ("case-{0}.zip" -f $index)
        New-TestArchive -ArchivePath $archivePath -Entries @($case.Entry)

        & $powerShellExecutable -NoProfile -ExecutionPolicy Bypass -File $exportScript -ValidateOnly $archivePath *> $null
        $actualExitCode = $LASTEXITCODE

        if ($actualExitCode -ne $case.ExpectedExitCode) {
            $failures += "{0}: várt exit code {1}, kapott {2}" -f $case.Name, $case.ExpectedExitCode, $actualExitCode
            Write-Host "FAIL: $($case.Name)" -ForegroundColor Red
        }
        else {
            Write-Host "PASS: $($case.Name)" -ForegroundColor Green
        }
    }

    if ($failures.Count -gt 0) {
        $failures | ForEach-Object { Write-Error $_ }
        exit 1
    }

    Write-Host "Minden ZIP-validációs teszt sikeres ($($cases.Count)/$($cases.Count))." -ForegroundColor Green
    exit 0
}
finally {
    $resolvedTemporaryRoot = [System.IO.Path]::GetFullPath($temporaryRoot)
    $systemTemporaryRoot = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
    $isOwnedTestDirectory = $resolvedTemporaryRoot.StartsWith($systemTemporaryRoot, [System.StringComparison]::OrdinalIgnoreCase) -and
        (Split-Path -Leaf $resolvedTemporaryRoot).StartsWith('km-export-tests-')

    if ($isOwnedTestDirectory -and (Test-Path -LiteralPath $resolvedTemporaryRoot)) {
        Remove-Item -LiteralPath $resolvedTemporaryRoot -Recurse -Force
    }
}
