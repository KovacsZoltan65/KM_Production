[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [string] $OutputPath,

    [string] $ValidateOnly
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Get-ArchiveEntryNames {
    param([Parameter(Mandatory = $true)][string] $ArchivePath)

    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $archive = [System.IO.Compression.ZipFile]::OpenRead($ArchivePath)

    try {
        return @($archive.Entries | ForEach-Object { $_.FullName.Replace('\', '/').TrimStart('/') })
    }
    finally {
        $archive.Dispose()
    }
}

function Get-ForbiddenArchiveEntries {
    param([Parameter(Mandatory = $true)][string[]] $EntryNames)

    $forbidden = foreach ($entryName in $EntryNames) {
        $normalized = $entryName.Replace('\', '/').TrimStart('/')

        if ([string]::IsNullOrWhiteSpace($normalized) -or $normalized.EndsWith('/')) {
            continue
        }

        $lowerPath = $normalized.ToLowerInvariant()
        $segments = @($lowerPath.Split('/') | Where-Object { $_ -ne '' })
        $leaf = $segments[-1]
        $isEnvironmentFile = $leaf.StartsWith('.env') -and $leaf -ne '.env.example'
        $hasForbiddenDirectory = $segments -contains 'node_modules' -or
            $segments -contains 'vendor' -or
            $segments -contains 'coverage' -or
            $lowerPath -match '(^|/)public/build(/|$)' -or
            $lowerPath -match '(^|/)storage/logs(/|$)'
        $isLog = $leaf.EndsWith('.log')
        $isIdeHelper = $leaf -eq '_ide_helper.php' -or $leaf -eq '_ide_helper_models.php'
        $isCredentialFile = $leaf -eq 'auth.json' -or
            $leaf -eq 'composer-auth.json' -or
            $leaf -match '^(id_rsa|id_ed25519)(\.pub)?$' -or
            $leaf -match '\.(pem|p12|pfx|key)$'
        $isDatabaseOrDump = $leaf -match '\.(sql|dump|sqlite|sqlite3|db|bak)(\.gz)?$'

        if ($isEnvironmentFile -or $hasForbiddenDirectory -or $isLog -or $isIdeHelper -or $isCredentialFile -or $isDatabaseOrDump) {
            $normalized
        }
    }

    return @($forbidden | Sort-Object -Unique)
}

function Test-ProjectArchive {
    param(
        [Parameter(Mandatory = $true)][string] $ArchivePath,
        [switch] $RequireEnvironmentExample
    )

    if (-not (Test-Path -LiteralPath $ArchivePath -PathType Leaf)) {
        throw "A ZIP nem található: $ArchivePath"
    }

    $entries = @(Get-ArchiveEntryNames -ArchivePath $ArchivePath)
    $forbiddenEntries = @(Get-ForbiddenArchiveEntries -EntryNames $entries)

    if ($forbiddenEntries.Count -gt 0) {
        Write-Host 'A ZIP tiltott fájlokat tartalmaz:' -ForegroundColor Red
        $forbiddenEntries | ForEach-Object { Write-Host " - $_" -ForegroundColor Red }
        return $false
    }

    if ($RequireEnvironmentExample -and -not ($entries -contains '.env.example')) {
        Write-Host 'A ZIP-ből hiányzik a kötelező .env.example fájl.' -ForegroundColor Red
        return $false
    }

    return $true
}

function Get-UniqueOutputPath {
    param([Parameter(Mandatory = $true)][string] $RequestedPath)

    $fullPath = [System.IO.Path]::GetFullPath($RequestedPath)
    if (-not (Test-Path -LiteralPath $fullPath)) {
        return $fullPath
    }

    $directory = Split-Path -Parent $fullPath
    $baseName = [System.IO.Path]::GetFileNameWithoutExtension($fullPath)
    $extension = [System.IO.Path]::GetExtension($fullPath)
    $counter = 1

    do {
        $candidate = Join-Path $directory ("{0}-{1}{2}" -f $baseName, $counter, $extension)
        $counter++
    } while (Test-Path -LiteralPath $candidate)

    return $candidate
}

if ($ValidateOnly) {
    try {
        if (Test-ProjectArchive -ArchivePath $ValidateOnly) {
            Write-Host "A ZIP biztonsági ellenőrzése sikeres: $ValidateOnly" -ForegroundColor Green
            exit 0
        }

        exit 2
    }
    catch {
        Write-Error $_.Exception.Message
        exit 1
    }
}

$createdArchive = $null

try {
    if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
        throw 'A Git parancs nem érhető el.'
    }

    $insideWorkTree = (& git rev-parse --is-inside-work-tree 2>$null).Trim()
    if ($LASTEXITCODE -ne 0 -or $insideWorkTree -ne 'true') {
        throw 'A scriptet egy Git repositoryban kell futtatni.'
    }

    $repositoryRoot = (& git rev-parse --show-toplevel).Trim()
    if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($repositoryRoot)) {
        throw 'A Git repository gyökérkönyvtára nem határozható meg.'
    }

    $shortCommit = (& git -C $repositoryRoot rev-parse --short HEAD).Trim()
    if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($shortCommit)) {
        throw 'Az aktuális commit azonosítója nem határozható meg.'
    }

    $projectName = Split-Path -Leaf $repositoryRoot
    $defaultFileName = "{0}-{1}.zip" -f $projectName, $shortCommit

    if ([string]::IsNullOrWhiteSpace($OutputPath)) {
        $requestedOutput = Join-Path $repositoryRoot $defaultFileName
    }
    elseif (Test-Path -LiteralPath $OutputPath -PathType Container) {
        $requestedOutput = Join-Path $OutputPath $defaultFileName
    }
    else {
        $requestedOutput = $OutputPath
        if ([System.IO.Path]::GetExtension($requestedOutput) -ne '.zip') {
            $requestedOutput += '.zip'
        }
    }

    $targetPath = Get-UniqueOutputPath -RequestedPath $requestedOutput
    $targetDirectory = Split-Path -Parent $targetPath
    if (-not (Test-Path -LiteralPath $targetDirectory -PathType Container)) {
        New-Item -ItemType Directory -Path $targetDirectory -Force | Out-Null
    }

    Write-Host "Projekt exportálása a commitolt állapotból ($shortCommit)..."
    & git -C $repositoryRoot archive --format=zip --worktree-attributes "--output=$targetPath" HEAD
    if ($LASTEXITCODE -ne 0) {
        throw "A git archive hibával leállt (exit code: $LASTEXITCODE)."
    }

    $createdArchive = $targetPath
    if (-not (Test-ProjectArchive -ArchivePath $targetPath -RequireEnvironmentExample)) {
        throw 'Az elkészült ZIP nem felelt meg a biztonsági ellenőrzésnek.'
    }

    Write-Host "Biztonságos projekt-export elkészült: $targetPath" -ForegroundColor Green
    exit 0
}
catch {
    if ($createdArchive -and (Test-Path -LiteralPath $createdArchive -PathType Leaf)) {
        Remove-Item -LiteralPath $createdArchive -Force
        Write-Host "A hibás, most létrehozott ZIP törölve: $createdArchive" -ForegroundColor Yellow
    }

    Write-Error $_.Exception.Message
    exit 1
}
