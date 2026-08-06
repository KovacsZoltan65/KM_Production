param(
    [Parameter(Mandatory = $true)]
    [string] $EncodedArguments,

    [Parameter(Mandatory = $true)]
    [ValidateRange(1, 86400)]
    [int] $TimeoutSeconds
)

$ErrorActionPreference = "Stop"
$json = [System.Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($EncodedArguments))
$commandArguments = @($json | ConvertFrom-Json)

if ($commandArguments.Count -eq 0) {
    Write-Error "No child command was provided."
    exit 64
}

function ConvertTo-WindowsCommandLineArgument {
    param([string] $Value)

    if ($Value -match '^[A-Za-z0-9_./:\\=-]+$') {
        return $Value
    }

    $escaped = $Value -replace '(\\*)"', '$1$1\"'
    $escaped = $escaped -replace '(\\+)$', '$1$1'

    return '"' + $escaped + '"'
}

$encodedChildArguments = [System.Collections.Generic.List[string]]::new()
foreach ($argument in $commandArguments) {
    $encodedChildArguments.Add((ConvertTo-WindowsCommandLineArgument ([string] $argument)))
}
$childCommandLine = $encodedChildArguments -join " "
$cmdCommandLine = $env:ComSpec + ' /D /S /C "' + $childCommandLine + '"'

$jobTypeSource = @'
using System;
using System.ComponentModel;
using System.Runtime.InteropServices;
using System.Text;

public static class QualityGateJob
{
    private const uint CREATE_SUSPENDED = 0x00000004;
    private const uint STARTF_USESTDHANDLES = 0x00000100;
    private const uint JOB_OBJECT_LIMIT_KILL_ON_JOB_CLOSE = 0x00002000;
    private const uint WAIT_TIMEOUT = 0x00000102;
    private const int JobObjectExtendedLimitInformation = 9;

    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    private struct STARTUPINFO
    {
        public int cb;
        public string lpReserved;
        public string lpDesktop;
        public string lpTitle;
        public uint dwX;
        public uint dwY;
        public uint dwXSize;
        public uint dwYSize;
        public uint dwXCountChars;
        public uint dwYCountChars;
        public uint dwFillAttribute;
        public uint dwFlags;
        public short wShowWindow;
        public short cbReserved2;
        public IntPtr lpReserved2;
        public IntPtr hStdInput;
        public IntPtr hStdOutput;
        public IntPtr hStdError;
    }

    [StructLayout(LayoutKind.Sequential)]
    private struct PROCESS_INFORMATION
    {
        public IntPtr hProcess;
        public IntPtr hThread;
        public uint dwProcessId;
        public uint dwThreadId;
    }

    [StructLayout(LayoutKind.Sequential)]
    private struct JOBOBJECT_BASIC_LIMIT_INFORMATION
    {
        public long PerProcessUserTimeLimit;
        public long PerJobUserTimeLimit;
        public uint LimitFlags;
        public UIntPtr MinimumWorkingSetSize;
        public UIntPtr MaximumWorkingSetSize;
        public uint ActiveProcessLimit;
        public UIntPtr Affinity;
        public uint PriorityClass;
        public uint SchedulingClass;
    }

    [StructLayout(LayoutKind.Sequential)]
    private struct IO_COUNTERS
    {
        public ulong ReadOperationCount;
        public ulong WriteOperationCount;
        public ulong OtherOperationCount;
        public ulong ReadTransferCount;
        public ulong WriteTransferCount;
        public ulong OtherTransferCount;
    }

    [StructLayout(LayoutKind.Sequential)]
    private struct JOBOBJECT_EXTENDED_LIMIT_INFORMATION
    {
        public JOBOBJECT_BASIC_LIMIT_INFORMATION BasicLimitInformation;
        public IO_COUNTERS IoInfo;
        public UIntPtr ProcessMemoryLimit;
        public UIntPtr JobMemoryLimit;
        public UIntPtr PeakProcessMemoryUsed;
        public UIntPtr PeakJobMemoryUsed;
    }

    [DllImport("kernel32.dll", CharSet = CharSet.Unicode, SetLastError = true)]
    private static extern bool CreateProcessW(
        string applicationName,
        StringBuilder commandLine,
        IntPtr processAttributes,
        IntPtr threadAttributes,
        bool inheritHandles,
        uint creationFlags,
        IntPtr environment,
        string currentDirectory,
        ref STARTUPINFO startupInfo,
        out PROCESS_INFORMATION processInformation
    );

    [DllImport("kernel32.dll", CharSet = CharSet.Unicode, SetLastError = true)]
    private static extern IntPtr CreateJobObjectW(IntPtr jobAttributes, string name);

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern bool SetInformationJobObject(
        IntPtr job,
        int informationClass,
        ref JOBOBJECT_EXTENDED_LIMIT_INFORMATION information,
        uint informationLength
    );

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern bool AssignProcessToJobObject(IntPtr job, IntPtr process);

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern uint ResumeThread(IntPtr thread);

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern uint WaitForSingleObject(IntPtr handle, uint milliseconds);

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern bool GetExitCodeProcess(IntPtr process, out uint exitCode);

    [DllImport("kernel32.dll", SetLastError = true)]
    private static extern bool TerminateProcess(IntPtr process, uint exitCode);

    [DllImport("kernel32.dll")]
    private static extern IntPtr GetStdHandle(int standardHandle);

    [DllImport("kernel32.dll")]
    private static extern bool CloseHandle(IntPtr handle);

    public static int Run(string commandLine, int timeoutSeconds)
    {
        var startupInfo = new STARTUPINFO {
            cb = Marshal.SizeOf<STARTUPINFO>(),
            dwFlags = STARTF_USESTDHANDLES,
            hStdInput = GetStdHandle(-10),
            hStdOutput = GetStdHandle(-11),
            hStdError = GetStdHandle(-12),
        };
        PROCESS_INFORMATION processInformation;
        IntPtr job = IntPtr.Zero;

        if (!CreateProcessW(
            null,
            new StringBuilder(commandLine),
            IntPtr.Zero,
            IntPtr.Zero,
            true,
            CREATE_SUSPENDED,
            IntPtr.Zero,
            Environment.CurrentDirectory,
            ref startupInfo,
            out processInformation
        )) {
            throw new Win32Exception(Marshal.GetLastWin32Error(), "Unable to create quality gate child process.");
        }

        try {
            job = CreateJobObjectW(IntPtr.Zero, null);
            if (job == IntPtr.Zero) {
                throw new Win32Exception(Marshal.GetLastWin32Error(), "Unable to create quality gate job object.");
            }

            var limits = new JOBOBJECT_EXTENDED_LIMIT_INFORMATION();
            limits.BasicLimitInformation.LimitFlags = JOB_OBJECT_LIMIT_KILL_ON_JOB_CLOSE;
            if (!SetInformationJobObject(
                job,
                JobObjectExtendedLimitInformation,
                ref limits,
                (uint)Marshal.SizeOf<JOBOBJECT_EXTENDED_LIMIT_INFORMATION>()
            )) {
                throw new Win32Exception(Marshal.GetLastWin32Error(), "Unable to configure quality gate job object.");
            }

            if (!AssignProcessToJobObject(job, processInformation.hProcess)) {
                throw new Win32Exception(Marshal.GetLastWin32Error(), "Unable to assign quality gate child to its job object.");
            }

            if (ResumeThread(processInformation.hThread) == 0xFFFFFFFF) {
                throw new Win32Exception(Marshal.GetLastWin32Error(), "Unable to resume quality gate child process.");
            }

            uint waitResult = WaitForSingleObject(processInformation.hProcess, checked((uint)timeoutSeconds * 1000));
            if (waitResult == WAIT_TIMEOUT) {
                CloseHandle(job);
                job = IntPtr.Zero;
                WaitForSingleObject(processInformation.hProcess, 5000);
                return 124;
            }

            uint exitCode;
            if (!GetExitCodeProcess(processInformation.hProcess, out exitCode)) {
                throw new Win32Exception(Marshal.GetLastWin32Error(), "Unable to read quality gate child exit code.");
            }

            return unchecked((int)exitCode);
        }
        catch {
            TerminateProcess(processInformation.hProcess, 125);
            throw;
        }
        finally {
            if (job != IntPtr.Zero) {
                CloseHandle(job);
            }
            CloseHandle(processInformation.hThread);
            CloseHandle(processInformation.hProcess);
        }
    }
}
'@

$sourceBytes = [System.Text.Encoding]::UTF8.GetBytes($jobTypeSource)
$hashBytes = [System.Security.Cryptography.SHA256]::HashData($sourceBytes)
$sourceHash = [Convert]::ToHexString($hashBytes).ToLowerInvariant()
$assemblyPath = Join-Path ([System.IO.Path]::GetTempPath()) ("km-quality-gate-job-" + $sourceHash + ".dll")

if (-not (Test-Path -LiteralPath $assemblyPath)) {
    Add-Type -TypeDefinition $jobTypeSource -OutputAssembly $assemblyPath
}

if (-not ("QualityGateJob" -as [type])) {
    Add-Type -Path $assemblyPath
}

try {
    exit [QualityGateJob]::Run($cmdCommandLine, $TimeoutSeconds)
} catch {
    Write-Error $_
    exit 125
}
