# Aksara - Optimasi Windows Defender untuk Developer
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host '==========================================================' -ForegroundColor Yellow
    Write-Host ' [PERHATIAN] HAK AKSES ADMINISTRATOR DIPERLUKAN' -ForegroundColor Yellow
    Write-Host '==========================================================' -ForegroundColor Yellow
    Write-Host 'Pengaturan Windows Defender memerlukan izin Administrator.' -ForegroundColor Gray
    Write-Host ''
    Write-Host 'Pilihan cara menjalankan:' -ForegroundColor White
    Write-Host '1. Klik kanan pada file scripts\exclude-defender.bat -> Run as Administrator' -ForegroundColor Cyan
    Write-Host '2. Buka PowerShell sebagai Administrator, lalu jalankan skrip ini lagi.' -ForegroundColor Cyan
    Write-Host ''
    try {
        $scriptPath = $PSCommandPath
        if (-not $scriptPath) { $scriptPath = $MyInvocation.MyCommand.Definition }
        Start-Process powershell.exe -Verb RunAs -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `'$scriptPath`'"
    } catch {
        # Sesi non-interaktif
    }
    exit
}

Write-Host '==========================================================' -ForegroundColor Cyan
Write-Host '   AKSARA - OPTIMASI WINDOWS DEFENDER UNTUK DEVELOPER     ' -ForegroundColor Cyan
Write-Host '==========================================================' -ForegroundColor Cyan
Write-Host ''

# 1. Daftar Direktori Proyek & Tooling Cache
$pathsToExclude = @(
    'C:\Users\jejak\Documents\www\Bimtek\aksara',
    'C:\Users\jejak\Documents\www\Bimtek',
    'C:\Users\jejak\AppData\Local\ms-playwright',
    'C:\Users\jejak\AppData\Roaming\npm-cache',
    'C:\Users\jejak\AppData\Local\Composer',
    'C:\Users\jejak\.gemini\antigravity-ide'
)

# 2. Daftar Proses Pengembang
$processesToExclude = @(
    'php.exe',
    'node.exe',
    'git.exe',
    'chrome.exe',
    'headless_shell.exe'
)

Write-Host '[1/2] Menambahkan Pengecualian Folder (Paths)...' -ForegroundColor Green
foreach ($p in $pathsToExclude) {
    try {
        Add-MpPreference -ExclusionPath $p -ErrorAction Stop
        Write-Host "  [OK] Excluded Path: $p" -ForegroundColor Gray
    } catch {
        Write-Host "  [WARN] Gagal menambahkan $p : $_" -ForegroundColor Red
    }
}

Write-Host ''
Write-Host '[2/2] Menambahkan Pengecualian Proses (Processes)...' -ForegroundColor Green
foreach ($proc in $processesToExclude) {
    try {
        Add-MpPreference -ExclusionProcess $proc -ErrorAction Stop
        Write-Host "  [OK] Excluded Process: $proc" -ForegroundColor Gray
    } catch {
        Write-Host "  [WARN] Gagal menambahkan $proc : $_" -ForegroundColor Red
    }
}

Write-Host ''
Write-Host '==========================================================' -ForegroundColor Cyan
Write-Host '  SELESAI! Pengecualian Windows Defender berhasil dipasang' -ForegroundColor Green
Write-Host '  Proses scanning MsMpEng.exe tidak akan mengganggu kerja.' -ForegroundColor Green
Write-Host '==========================================================' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Pengecualian yang aktif:' -ForegroundColor Cyan
Get-MpPreference | Select-Object -ExpandProperty ExclusionPath
Write-Host ''
Write-Host 'Proses yang dikecualikan:' -ForegroundColor Cyan
Get-MpPreference | Select-Object -ExpandProperty ExclusionProcess
Write-Host ''
Read-Host -Prompt 'Tekan Enter untuk keluar'
