#requires -Version 7.4
[CmdletBinding()]
param(
    [ValidateSet('Preflight', 'PoC')][string]$Mode = 'Preflight',
    [switch]$ConfirmAuthorizedPoC,
    [string]$MySqlBinary,
    [string]$PhpBinary,
    [string]$TemporaryParent = [IO.Path]::GetTempPath()
)
# Dot-sourcing defines helpers only. No filesystem or database operation at import.
$script:RmaRepository = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../../..')).TrimEnd('\')
$script:RmaPocDirectory = $PSScriptRoot

function Initialize-RmaNative {
    if (-not $IsWindows) { throw 'WINDOWS_REQUIRED' }
    if (-not ('RmaLauncherNative' -as [type])) {
        Add-Type -TypeDefinition @'
using System;
using System.Text;
using System.Runtime.InteropServices;
using System.ComponentModel;
using System.Security.Principal;
using System.Collections.Generic;

public static class RmaLauncherNative {
 [StructLayout(LayoutKind.Sequential)] public struct Mapping {public uint Read,Write,Execute,All;}
 [DllImport("kernel32.dll")] static extern IntPtr GetCurrentProcess();
 [DllImport("kernel32.dll",SetLastError=true)] static extern bool CloseHandle(IntPtr h);
 [DllImport("advapi32.dll",SetLastError=true)] static extern bool OpenProcessToken(IntPtr p,uint a,out IntPtr t);
 [DllImport("advapi32.dll",SetLastError=true)] static extern bool DuplicateToken(IntPtr t,int level,out IntPtr copy);
 [DllImport("advapi32.dll",SetLastError=true)] static extern bool AccessCheck(byte[] sd,IntPtr t,uint desired,ref Mapping m,IntPtr privileges,ref uint size,out uint granted,out bool allowed);
 [DllImport("advapi32.dll",SetLastError=true)] static extern bool GetTokenInformation(IntPtr t,int kind,IntPtr b,uint n,out uint required);
 [DllImport("advapi32.dll",SetLastError=true)] static extern bool IsTokenRestricted(IntPtr t);
 [DllImport("kernel32.dll",CharSet=CharSet.Unicode)] public static extern uint GetDriveType(string root);
 [DllImport("kernel32.dll",CharSet=CharSet.Unicode,SetLastError=true)] public static extern bool GetVolumeInformation(string root,StringBuilder name,uint n,out uint serial,out uint max,out uint flags,StringBuilder fs,uint fsn);
 [DllImport("kernel32.dll",CharSet=CharSet.Unicode,SetLastError=true)] static extern IntPtr CreateFile(string path,uint access,uint share,IntPtr sec,uint disposition,uint flags,IntPtr template);
 [DllImport("kernel32.dll",CharSet=CharSet.Unicode,SetLastError=true)] static extern uint GetFinalPathNameByHandle(IntPtr h,StringBuilder name,uint n,uint flags);

 [DllImport("kernel32.dll",CharSet=CharSet.Unicode,SetLastError=true)] public static extern bool CreateDirectory(string path,IntPtr security);
 [DllImport("kernel32.dll")] public static extern uint GetOEMCP();
 [DllImport("kernel32.dll")] public static extern uint GetACP();
 [DllImport("kernel32.dll")] public static extern uint GetConsoleOutputCP();
 [StructLayout(LayoutKind.Sequential)] struct FileInfo {public uint attributes;public System.Runtime.InteropServices.ComTypes.FILETIME created,accessed,written;public uint volume,sizeHigh,sizeLow,links,indexHigh,indexLow;}
 [DllImport("kernel32.dll",SetLastError=true)] static extern bool GetFileInformationByHandle(IntPtr h,out FileInfo info);
 public static object FileIdentity(string path){IntPtr h=CreateFile(path,0x80,7,IntPtr.Zero,3,0x02200000,IntPtr.Zero);if(h==new IntPtr(-1))throw new Win32Exception(Marshal.GetLastWin32Error());try{FileInfo f;if(!GetFileInformationByHandle(h,out f))throw new Win32Exception(Marshal.GetLastWin32Error());return new {volume=f.volume.ToString("X8"),fileId=f.indexHigh.ToString("X8")+f.indexLow.ToString("X8")};}finally{CloseHandle(h);}}
 static IntPtr Token() {IntPtr t;if(!OpenProcessToken(GetCurrentProcess(),0xA,out t))throw new Win32Exception(Marshal.GetLastWin32Error());return t;}
 static IntPtr Info(IntPtr t,int kind) {uint size;GetTokenInformation(t,kind,IntPtr.Zero,0,out size);if(size==0)throw new Win32Exception(Marshal.GetLastWin32Error());IntPtr b=Marshal.AllocHGlobal((int)size);if(!GetTokenInformation(t,kind,b,size,out size)){Marshal.FreeHGlobal(b);throw new Win32Exception(Marshal.GetLastWin32Error());}return b;}
 static int Number(IntPtr t,int kind){IntPtr b=Info(t,kind);try{return Marshal.ReadInt32(b);}finally{Marshal.FreeHGlobal(b);}}
 static string Sid(IntPtr t,int kind){IntPtr b=Info(t,kind);try{return new SecurityIdentifier(Marshal.ReadIntPtr(b)).Value;}finally{Marshal.FreeHGlobal(b);}}
 static List<Dictionary<string,object>> Groups(IntPtr t,int kind){IntPtr b=Info(t,kind);try{int count=Marshal.ReadInt32(b);int offset=IntPtr.Size==8?8:4;int stride=IntPtr.Size==8?16:8;var list=new List<Dictionary<string,object>>();for(int i=0;i<count;i++){IntPtr row=IntPtr.Add(b,offset+i*stride);list.Add(new Dictionary<string,object>{{"sid",new SecurityIdentifier(Marshal.ReadIntPtr(row)).Value},{"attributes",Marshal.ReadInt32(row,IntPtr.Size)}});}return list;}finally{Marshal.FreeHGlobal(b);}}
 public static object Snapshot(){IntPtr t=Token();try{using(var id=new WindowsIdentity(t)){return new {name=id.Name,userSid=Sid(t,1),defaultOwnerSid=Sid(t,4),integritySid=Sid(t,25),tokenType=Number(t,8),elevationType=Number(t,18),elevated=Number(t,20)!=0,restricted=IsTokenRestricted(t),groups=Groups(t,2),restrictingSids=Groups(t,11)};}}finally{CloseHandle(t);}}
 public static object Check(byte[] sd,uint desired){IntPtr t=Token(),dup=IntPtr.Zero,b=Marshal.AllocHGlobal(4096);try{if(!DuplicateToken(t,2,out dup))throw new Win32Exception(Marshal.GetLastWin32Error());var map=new Mapping{Read=0x120089,Write=0x120116,Execute=0x1200A0,All=0x1F01FF};uint size=4096,granted;bool allowed;bool ok=AccessCheck(sd,dup,desired,ref map,b,ref size,out granted,out allowed);int error=(!ok || !allowed)?Marshal.GetLastWin32Error():0;return new {apiSucceeded=ok,allowed=allowed,desired=desired,granted=granted,win32Error=error};}finally{Marshal.FreeHGlobal(b);if(dup!=IntPtr.Zero)CloseHandle(dup);CloseHandle(t);}}
 public static string FinalPath(string path){IntPtr h=CreateFile(path,0x80,7,IntPtr.Zero,3,0x02200000,IntPtr.Zero);if(h==new IntPtr(-1))throw new Win32Exception(Marshal.GetLastWin32Error());try{var b=new StringBuilder(4096);uint n=GetFinalPathNameByHandle(h,b,4096,0);if(n==0||n>=4096)throw new Win32Exception(Marshal.GetLastWin32Error());return b.ToString();}finally{CloseHandle(h);}}
}
'@
    }
}

function Assert-RmaNoReparse {
    param([string]$Path)
    $full = [IO.Path]::GetFullPath($Path)
    $root = [IO.Path]::GetPathRoot($full)
    $walk = $root
    if (([IO.File]::GetAttributes($walk) -band [IO.FileAttributes]::ReparsePoint) -ne 0) { throw 'REPARSE_PATH' }
    foreach ($part in $full.Substring($root.Length).Split('\', [StringSplitOptions]::RemoveEmptyEntries)) {
        $walk = Join-Path $walk $part
        if (([IO.File]::GetAttributes($walk) -band [IO.FileAttributes]::ReparsePoint) -ne 0) { throw 'REPARSE_PATH' }
    }
}

function ConvertTo-RmaCanonical {
    param([string]$Path)
    Assert-RmaNoReparse $Path
    $final = [RmaLauncherNative]::FinalPath($Path)
    if (-not $final.StartsWith('\\?\') -or $final.StartsWith('\\?\UNC\')) { throw 'NONLOCAL_CANONICAL_PATH' }
    return $final.Substring(4).TrimEnd('\')
}

function Assert-RmaParentSyntax {
    param([string]$Path)
    if ($Path -notmatch '^[A-Za-z]:\\' -or $Path.StartsWith('\\') -or ($Path.IndexOf(':', 2) -ge 0)) { throw 'NONLOCAL_PARENT' }
    $full = [IO.Path]::GetFullPath($Path).TrimEnd('\')
    if ($full.Equals($script:RmaRepository, [StringComparison]::OrdinalIgnoreCase) -or
        $full.StartsWith($script:RmaRepository + '\', [StringComparison]::OrdinalIgnoreCase)) { throw 'REPOSITORY_PARENT' }
    if ($full -match '(?i)(^|\\)(mysql|mariadb|mysqldata|database|databases)(\\|$)') { throw 'DATABASE_PARENT' }
    # Exact OS temporary roots only. Arbitrary custom/application data roots fail closed.
    $known = @(
        (Join-Path ([Environment]::GetFolderPath('LocalApplicationData')) 'Temp'),
        (Join-Path ([Environment]::GetFolderPath('Windows')) 'Temp')
    ) | ForEach-Object { [IO.Path]::GetFullPath($_).TrimEnd('\') }
    if (-not @($known | Where-Object { $full.Equals($_, [StringComparison]::OrdinalIgnoreCase) }).Count) { throw 'UNAPPROVED_TEMP_PARENT' }
    return $full
}

function Get-RmaAclSnapshot {
    param([string]$Path)
    $acl = Get-Acl -LiteralPath $Path -ErrorAction Stop
    $rules = @($acl.GetAccessRules($true, $true, [Security.Principal.SecurityIdentifier]) | ForEach-Object {
        [ordered]@{ sid = $_.IdentityReference.Value; type = [string]$_.AccessControlType;
            mask = [int64]$_.FileSystemRights; inherited = $_.IsInherited;
            inheritance = [int]$_.InheritanceFlags; propagation = [int]$_.PropagationFlags }
    })
    $binary = $acl.GetSecurityDescriptorBinaryForm()
    return [ordered]@{
        ownerSid = $acl.GetOwner([Security.Principal.SecurityIdentifier]).Value
        protected = $acl.AreAccessRulesProtected
        canonical = $acl.AreAccessRulesCanonical
        sddl = $acl.GetSecurityDescriptorSddlForm(
            [Security.AccessControl.AccessControlSections]::Access -bor
            [Security.AccessControl.AccessControlSections]::Owner -bor
            [Security.AccessControl.AccessControlSections]::Group)
        rules = $rules
        createDirectory = [RmaLauncherNative]::Check($binary, 4)
        writeDac = [RmaLauncherNative]::Check($binary, 0x40000)
        delete = [RmaLauncherNative]::Check($binary, 0x10000)
        fullControl = [RmaLauncherNative]::Check($binary, 0x1F01FF)
    }
}

function Get-RmaTokenPolicy {
    $raw = [RmaLauncherNative]::Snapshot()
    if ([Security.Principal.WindowsIdentity]::GetCurrent().ImpersonationLevel.ToString() -ne 'None') { throw 'UNEXPECTED_THREAD_IMPERSONATION' }
    $admin = @($raw.groups | Where-Object { $_.sid -eq 'S-1-5-32-544' -and
        ($_.attributes -band 4) -ne 0 -and ($_.attributes -band 16) -eq 0 }).Count -gt 0
    $owners = @($raw.userSid)
    if ($admin -and $raw.defaultOwnerSid -eq 'S-1-5-32-544') { $owners += 'S-1-5-32-544' }
    if ($raw.defaultOwnerSid -notin $owners) { throw 'UNSUPPORTED_DEFAULT_OWNER' }
    return [ordered]@{
        userSid = $raw.userSid
        defaultOwnerSid = $raw.defaultOwnerSid
        allowedOwnerSids = $owners
        enabledAdministrator = $admin
        restricted = $raw.restricted
        elevated = $raw.elevated
        elevationType = $raw.elevationType
        integritySid = $raw.integritySid
        restrictingSids = @($raw.restrictingSids | ForEach-Object sid)
        # Privileged recovery principals are outside the ordinary-user threat model.
        permittedPrivilegedAclSids = @('S-1-5-18', 'S-1-5-32-544')
    }
}

function Get-RmaParentAssessment {
    param([string]$Path)
    $full = Assert-RmaParentSyntax $Path
    if (-not (Test-Path -LiteralPath $full -PathType Container)) { throw 'PARENT_MISSING' }
    $canonical = ConvertTo-RmaCanonical $full
    if (-not $full.Equals($canonical, [StringComparison]::OrdinalIgnoreCase)) { throw 'PARENT_REDIRECTION' }
    $root = [IO.Path]::GetPathRoot($canonical)
    $volume = [Text.StringBuilder]::new(260)
    $fs = [Text.StringBuilder]::new(260)
    [uint32]$serial = 0; [uint32]$max = 0; [uint32]$flags = 0
    if (-not [RmaLauncherNative]::GetVolumeInformation($root, $volume, 260, [ref]$serial, [ref]$max, [ref]$flags, $fs, 260)) { throw 'VOLUME_UNVERIFIED' }
    if ([RmaLauncherNative]::GetDriveType($root) -ne 3 -or $fs.ToString() -ne 'NTFS' -or ($flags -band 8) -eq 0) { throw 'LOCAL_NTFS_ACL_REQUIRED' }
    $acl = Get-RmaAclSnapshot $canonical
    if (-not $acl.createDirectory.apiSucceeded -or -not $acl.createDirectory.allowed) { throw 'PARENT_CREATE_DENIED_OR_UNVERIFIED' }
    return [ordered]@{ path = $canonical; filesystem = 'NTFS'; persistentAcls = $true; noReparse = $true; acl = $acl }
}

function Get-RmaGrantArguments {
    param([string]$Path, [string]$Sid)
    $parsed = [Security.Principal.SecurityIdentifier]::new($Sid)
    if ($parsed.Value -ne $Sid) { throw 'NONCANONICAL_SID' }
    # Numeric SID requires "*"; no username, machine name or account-name lookup.
    return @($Path, '/inheritance:r', '/grant:r', [string]::Concat('*', $Sid, ':(OI)(CI)F'))
}

function Assert-RmaFreshTarget {
    param([string]$Parent, [string]$Path)
    if (Test-Path -LiteralPath $Path) { throw 'EXISTING_SANDBOX' }
    Assert-RmaSandboxLocation $Parent $Path
}

function Assert-RmaSandboxLocation {
    param([string]$Parent, [string]$Path)
    $full = [IO.Path]::GetFullPath($Path)
    if (-not $full.Equals($Path, [StringComparison]::OrdinalIgnoreCase) -or
        -not [IO.Path]::GetDirectoryName($full).Equals($Parent, [StringComparison]::OrdinalIgnoreCase) -or
        [IO.Path]::GetFileName($full) -notmatch '^RmaBoundaryPoc-[a-f0-9]{32}$') { throw 'UNOWNED_SANDBOX_PATH' }
}

function Assert-RmaOwner {
    param($Snapshot, $Token)
    if ($Snapshot.ownerSid -notin $Token.allowedOwnerSids) { throw 'OWNER_POLICY_UNVERIFIED' }
}

function Assert-RmaPrivateDacl {
    param($Snapshot, $Token)
    Assert-RmaOwner $Snapshot $Token
    if (-not $Snapshot.protected -or -not $Snapshot.canonical) { throw 'DACL_NOT_PROTECTED_CANONICAL' }
    $userCount = 0
    $seen = @{}
    foreach ($ace in $Snapshot.rules) {
        if ($ace.inherited -or $ace.type -ne 'Allow' -or $ace.mask -ne 0x1F01FF -or
            $ace.inheritance -ne 3 -or $ace.propagation -ne 0) { throw 'UNEXPECTED_ACE_SHAPE' }
        if ($seen.ContainsKey($ace.sid)) { throw 'DUPLICATE_ACE' }
        $seen[$ace.sid] = $true
        if ($ace.sid -eq $Token.userSid) { $userCount++ }
        elseif ($ace.sid -notin $Token.permittedPrivilegedAclSids) { throw 'UNAUTHORIZED_ORDINARY_ACE' }
    }
    if ($userCount -ne 1) { throw 'EXPLICIT_OPERATOR_FULLCONTROL_REQUIRED' }
}

function Invoke-RmaAclCommand {
    param([string[]]$Arguments)
    $evidence = [ordered]@{ attempted = $true; executed = $false; executable = $null;
        arguments = $Arguments; exitCode = $null; stdout = $null; stderr = $null;
        stdoutBase64 = $null; stderrBase64 = $null; exception = $null; timedOut = $false }
    $process = [Diagnostics.Process]::new()
    $output = [IO.MemoryStream]::new()
    $errorOutput = [IO.MemoryStream]::new()
    $copyOut = $null; $copyErr = $null
    try {
        $psi = [Diagnostics.ProcessStartInfo]::new()
        $psi.FileName = Join-Path ([Environment]::GetFolderPath('System')) 'icacls.exe'
        if (-not (Test-Path -LiteralPath $psi.FileName -PathType Leaf)) { throw 'SYSTEM_ICACLS_MISSING' }
        $evidence.executable = $psi.FileName
        $psi.UseShellExecute = $false
        $psi.CreateNoWindow = $true
        $psi.RedirectStandardOutput = $true
        $psi.RedirectStandardError = $true
        foreach ($arg in $Arguments) { $psi.ArgumentList.Add($arg) }
        $process.StartInfo = $psi
        if (-not $process.Start()) { throw 'ICACLS_START_FAILED' }
        $evidence.executed = $true
        $copyOut = $process.StandardOutput.BaseStream.CopyToAsync($output)
        $copyErr = $process.StandardError.BaseStream.CopyToAsync($errorOutput)
        if (-not $process.WaitForExit(15000)) {
            $evidence.timedOut = $true
            $process.Kill()
            $process.WaitForExit()
        }
        # Capture the native child's exit immediately; never depend on LASTEXITCODE.
        $evidence.exitCode = $process.ExitCode
        $null = $copyOut.GetAwaiter().GetResult()
        $null = $copyErr.GetAwaiter().GetResult()
        if ($evidence.timedOut) { throw 'ICACLS_TIMEOUT' }
    } catch {
        $evidence.exception = [ordered]@{ type = $_.Exception.GetType().FullName;
            message = $_.Exception.Message; hresult = $_.Exception.HResult }
    } finally {
        $stdout = $output.ToArray(); $stderr = $errorOutput.ToArray()
        $evidence.stdoutBase64 = [Convert]::ToBase64String($stdout)
        $evidence.stderrBase64 = [Convert]::ToBase64String($stderr)
        try {
            [Text.Encoding]::RegisterProvider([Text.CodePagesEncodingProvider]::Instance)
            $encoding = [Text.Encoding]::GetEncoding([int][RmaLauncherNative]::GetOEMCP())
            $evidence.oemCodepage = $encoding.CodePage
            $evidence.stdout = $encoding.GetString($stdout)
            $evidence.stderr = $encoding.GetString($stderr)
        } catch { $evidence.decodeError = $_.Exception.Message }
        $process.Dispose()
        $output.Dispose()
        $errorOutput.Dispose()
    }
    return $evidence
}

function Assert-RmaOwnedDirectory {
    param($Context)
    if (-not $Context.created) { throw 'NO_CREATED_DIRECTORY_OWNERSHIP' }
    Assert-RmaSandboxLocation $Context.parent.path $Context.path
    Assert-RmaNoReparse $Context.path
    $canonical = ConvertTo-RmaCanonical $Context.path
    if (-not $canonical.Equals($Context.path, [StringComparison]::OrdinalIgnoreCase)) { throw 'CLEANUP_REDIRECTION' }
    $identity = [RmaLauncherNative]::FileIdentity($Context.path)
    if ($identity.volume -ne $Context.fileIdentity.volume -or $identity.fileId -ne $Context.fileIdentity.fileId) { throw 'DIRECTORY_IDENTITY_CHANGED' }
    $acl = Get-RmaAclSnapshot $Context.path
    Assert-RmaOwner $acl $Context.token
    if ($acl.ownerSid -ne $Context.initialAcl.ownerSid) { throw 'OWNER_CHANGED' }
    return $acl
}

function Remove-RmaOwnedSandbox {
    param($Context)
    if ($Context.server -and -not $Context.server.HasExited) { throw 'OWNED_SERVER_STILL_RUNNING' }
    $acl = Assert-RmaOwnedDirectory $Context
    if (-not $acl.delete.apiSucceeded -or -not $acl.delete.allowed) { throw 'CLEANUP_DELETE_UNVERIFIED' }
    if ($Context.phaseBStarted) {
        # Future PoC only: check every descendant before allowing recursive deletion.
        $pending = [Collections.Generic.Stack[string]]::new()
        $pending.Push($Context.path)
        while ($pending.Count -gt 0) {
            $directory = $pending.Pop()
            foreach ($entry in [IO.Directory]::EnumerateFileSystemEntries($directory)) {
                $attributes = [IO.File]::GetAttributes($entry)
                if (($attributes -band [IO.FileAttributes]::ReparsePoint) -ne 0) { throw 'CLEANUP_DESCENDANT_REPARSE' }
                if (($attributes -band [IO.FileAttributes]::Directory) -ne 0) { $pending.Push($entry) }
            }
        }
        $null = Assert-RmaOwnedDirectory $Context
        Remove-Item -LiteralPath $Context.path -Recurse -Force -ErrorAction Stop
    } else {
        if (@([IO.Directory]::EnumerateFileSystemEntries($Context.path)).Count -ne 0) { throw 'PREFLIGHT_NOT_EMPTY' }
        [IO.Directory]::Delete($Context.path, $false)
    }
    if (Test-Path -LiteralPath $Context.path) { throw 'CLEANUP_INCOMPLETE' }
}

function Invoke-RmaFilesystemPreflight {
    param([string]$Parent, $Context)
    Initialize-RmaNative
    $Context.token = Get-RmaTokenPolicy
    $Context.parent = Get-RmaParentAssessment $Parent
    $name = 'RmaBoundaryPoc-' + [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(16)).ToLowerInvariant()
    $Context.path = [IO.Path]::GetFullPath((Join-Path $Context.parent.path $name))
    Assert-RmaFreshTarget $Context.parent.path $Context.path
    Assert-RmaNoReparse $Context.parent.path
    if (-not [RmaLauncherNative]::CreateDirectory($Context.path, [IntPtr]::Zero)) {
        throw [ComponentModel.Win32Exception]::new([Runtime.InteropServices.Marshal]::GetLastWin32Error())
    }
    $Context.created = $true
    $Context.fileIdentity = [RmaLauncherNative]::FileIdentity($Context.path)
    $Context.createdUtc = (Get-Item -LiteralPath $Context.path -Force).CreationTimeUtc.ToString('o')
    $Context.initialAcl = Get-RmaAclSnapshot $Context.path
    Assert-RmaOwner $Context.initialAcl $Context.token
    $arguments = @(Get-RmaGrantArguments $Context.path $Context.token.userSid)
    $Context.aclCommand.arguments = $arguments
    if (-not $Context.initialAcl.writeDac.apiSucceeded -or -not $Context.initialAcl.writeDac.allowed) {
        throw 'WRITE_DAC_DENIED_OR_UNVERIFIED'
    }
    $Context.aclCommand = Invoke-RmaAclCommand $arguments
    if ($Context.aclCommand.exception) { throw 'ICACLS_INVOCATION_EXCEPTION' }
    $Context.finalAcl = Get-RmaAclSnapshot $Context.path
    if ($Context.aclCommand.exitCode -ne 0) { throw 'ICACLS_NONZERO_EXIT' }
    Assert-RmaPrivateDacl $Context.finalAcl $Context.token
    if (-not $Context.finalAcl.fullControl.apiSucceeded -or -not $Context.finalAcl.fullControl.allowed) { throw 'OPERATOR_EFFECTIVE_ACCESS_UNVERIFIED' }
    $Context.privateDaclVerified = $true
    # Ordinary tokens lacking operator/SYSTEM/enabled-admin SIDs have no matching allow ACE.
    # This is a DACL-bound threat model, NOT an alternate-account login or admin-compromise test.
    $Context.ordinaryAccessProof = 'Protected allowlist; no grants to ordinary unrelated SIDs. Alternate-token OS login test not performed.'
}

function Invoke-RmaTransactionPoC {
    param($Context, [string]$MySqlBinary, [string]$PhpBinary, [switch]$Confirmed)
    # Phase B is unreachable from default Preflight and has independent fail-closed gates.
    if (-not $Confirmed -or -not $Context.privateDaclVerified) { throw 'POC_AUTHORIZATION_AND_ISOLATION_REQUIRED' }
    $manifest = Join-Path $script:RmaPocDirectory 'evidence/results.json'
    if (Test-Path -LiteralPath $manifest) { throw 'HISTORICAL_EVIDENCE_MUST_BE_PRESERVED_BY_REVIEWED_FOLLOWUP' }
    if (Test-Path Env:RMA_POC_PRIVATE_CONFIG) { throw 'PREEXISTING_PRIVATE_CONFIG_REFUSED' }
    foreach ($binary in @($MySqlBinary, $PhpBinary)) {
        if ([string]::IsNullOrWhiteSpace($binary) -or $binary -notmatch '^[A-Za-z]:\\' -or -not [IO.Path]::IsPathFullyQualified($binary) -or
            -not (Test-Path -LiteralPath $binary -PathType Leaf)) { throw 'EXPLICIT_LOCAL_BINARY_REQUIRED' }
        Assert-RmaNoReparse $binary
    }
    $current = Assert-RmaOwnedDirectory $Context
    Assert-RmaPrivateDacl $current $Context.token
    if (-not $current.fullControl.allowed) { throw 'POC_RUNTIME_ACCESS_CHANGED' }
    $version = & $MySqlBinary --no-defaults --version
    $versionExit = $LASTEXITCODE
    if ($versionExit -ne 0 -or $version -notmatch 'Ver 8\.4\.') { throw 'MYSQL84_BINARY_REQUIRED' }
    $Context.phaseBStarted = $true
    $dataRoot = Join-Path $Context.path 'data'
    $listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, 0)
    try { $listener.Start(); $port = $listener.LocalEndpoint.Port } finally { $listener.Stop() }
    if ($port -lt 20000) { throw 'NONSTANDARD_ISOLATED_PORT_REQUIRED' }
    $adminSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
    $runtimeSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
    $definerSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
    $bootstrap = Join-Path $Context.path 'bootstrap.sql'
    [IO.File]::WriteAllText($bootstrap, "ALTER USER 'root'@'localhost' IDENTIFIED BY '$adminSecret';", [Text.UTF8Encoding]::new($false))
    $initArgs = @('--no-defaults', '--initialize-insecure', "--datadir=`"$dataRoot`"", '--skip-log-bin',
        "--log-error=`"$(Join-Path $Context.path 'initialize.log')`"")
    $initializer = Start-Process -FilePath $MySqlBinary -ArgumentList $initArgs -WindowStyle Hidden -PassThru
    try {
        if (-not $initializer.WaitForExit(60000)) { $initializer.Kill(); $initializer.WaitForExit(); throw 'MYSQL_INITIALIZATION_TIMEOUT' }
        if ($initializer.ExitCode -ne 0) { throw 'DISPOSABLE_INITIALIZATION_FAILED' }
    } finally { $initializer.Dispose() }
    $args = @('--no-defaults', "--datadir=`"$dataRoot`"", '--bind-address=127.0.0.1', "--port=$port", '--mysqlx=0',
        '--skip-log-bin', '--local-infile=0', '--secure-file-priv=NULL', '--innodb-buffer-pool-size=32M',
        "--init-file=`"$bootstrap`"", "--log-error=`"$(Join-Path $Context.path 'server.log')`"",
        "--pid-file=`"$(Join-Path $Context.path 'owned.pid')`"")
    $Context.server = Start-Process -FilePath $MySqlBinary -ArgumentList $args -WindowStyle Hidden -PassThru
    $owned = $false
    for ($i = 0; $i -lt 45; $i++) {
        if ($Context.server.HasExited) { throw 'DISPOSABLE_SERVER_EXITED' }
        $sockets = @(Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue)
        if (@($sockets | Where-Object { $_.OwningProcess -eq $Context.server.Id -and $_.LocalAddress -eq '127.0.0.1' }).Count) { $owned = $true; break }
        Start-Sleep -Milliseconds 500
    }
    if (-not $owned) { throw 'OWNED_LISTENER_UNVERIFIED' }
    $config = @{ host = '127.0.0.1'; port = [int]$port; database = 'rma_c302_disposable';
        admin_password = $adminSecret; runtime_password = $runtimeSecret; definer_password = $definerSecret;
        owned_data_root = $dataRoot; isolation_gate = 'owned-fresh-server-listener-verified' }
    try {
        $env:RMA_POC_PRIVATE_CONFIG = $config | ConvertTo-Json -Compress
        & $PhpBinary -d xdebug.mode=off (Join-Path $script:RmaPocDirectory 'run.php')
        $phpExit = $LASTEXITCODE
        if ($phpExit -ne 0) { throw 'POC_RUNNER_FAILED' }
    } finally {
        Remove-Item Env:RMA_POC_PRIVATE_CONFIG -ErrorAction SilentlyContinue
        $adminSecret = $null; $runtimeSecret = $null; $definerSecret = $null; $config = $null
    }
}

function Invoke-RmaLauncher {
    param([ValidateSet('Preflight', 'PoC')][string]$Mode = 'Preflight',
        [switch]$ConfirmAuthorizedPoC, [string]$TemporaryParent = [IO.Path]::GetTempPath(),
        [string]$MySqlBinary, [string]$PhpBinary)
    $previousErrorAction = $ErrorActionPreference
    $ErrorActionPreference = 'Stop'
    $context = [ordered]@{ mode = $Mode; utc = [DateTime]::UtcNow.ToString('o'); created = $false;
        privateDaclVerified = $false; phaseBStarted = $false; server = $null;
        aclCommand = [ordered]@{ attempted = $false; executed = $false; exitCode = $null; stdout = $null; stderr = $null; exception = $null } }
    $exitCode = 2
    $verdict = 'PREFLIGHT BLOCKED'
    $stage = 'mode-gate'
    try {
        if ($Mode -eq 'PoC' -and -not $ConfirmAuthorizedPoC) { throw 'EXPLICIT_POC_CONFIRMATION_REQUIRED' }
        if ($Mode -eq 'Preflight' -and $ConfirmAuthorizedPoC) { throw 'PREFLIGHT_CANNOT_CONFIRM_POC' }
        $stage = 'filesystem-preflight'
        Invoke-RmaFilesystemPreflight -Parent $TemporaryParent -Context $context
        if ($Mode -eq 'PoC') {
            $stage = 'phase-b'
            Invoke-RmaTransactionPoC -Context $context -MySqlBinary $MySqlBinary -PhpBinary $PhpBinary -Confirmed:$ConfirmAuthorizedPoC
        }
        $verdict = 'PREFLIGHT VERIFIED — LIMITED SCOPE'
        $exitCode = 0
    } catch {
        $context.error = [ordered]@{ stage = $stage; type = $_.Exception.GetType().FullName;
            message = $_.Exception.Message; hresult = $_.Exception.HResult }
        if ($context.created) {
            try { $context.finalAcl = Get-RmaAclSnapshot $context.path } catch { $context.finalAclUnavailable = $true }
        }
        if ($context.aclCommand.exception -or ($context.aclCommand.executed -and $context.aclCommand.exitCode -ne 0)) { $verdict = 'PREFLIGHT FAILED'; $exitCode = 3 }
    } finally {
        if ($context.server -and -not $context.server.HasExited) {
            # Never stop a service, global process name or any unowned PID.
            Stop-Process -Id $context.server.Id -ErrorAction SilentlyContinue
            if (-not $context.server.WaitForExit(15000)) { $context.serverExitUnverified = $true }
        }
        if ($context.created) {
            try {
                Remove-RmaOwnedSandbox $context
                $context.cleanup = [ordered]@{ removed = $true; canonicalPathAndFileIdentityVerified = $true }
            } catch {
                $context.cleanup = [ordered]@{ removed = $false; reason = $_.Exception.Message; retainedPath = $context.path }
                $verdict = 'PREFLIGHT BLOCKED'; $exitCode = 2
            }
        } else { $context.cleanup = [ordered]@{ removed = $false; notCreated = $true } }
        if ($context.Contains('parent')) {
            try {
                $parentAfter = Get-RmaAclSnapshot $context.parent.path
                $context.parentAclUnchanged = $parentAfter.sddl -ceq $context.parent.acl.sddl
                if (-not $context.parentAclUnchanged) { $verdict = 'PREFLIGHT BLOCKED'; $exitCode = 2 }
            } catch { $context.parentAclUnchanged = $null; $verdict = 'PREFLIGHT BLOCKED'; $exitCode = 2 }
        }
        # Process handles and secrets never appear in the emitted evidence.
        $context.Remove('server')
        $context.verdict = $verdict
        $context.launcherExitCode = $exitCode
        $ErrorActionPreference = $previousErrorAction
    }
    return [pscustomobject]$context
}

if ($MyInvocation.InvocationName -ne '.') {
    $result = Invoke-RmaLauncher -Mode $Mode -ConfirmAuthorizedPoC:$ConfirmAuthorizedPoC `
        -TemporaryParent $TemporaryParent -MySqlBinary $MySqlBinary -PhpBinary $PhpBinary
    $result | ConvertTo-Json -Depth 20
    exit $result.launcherExitCode
}
