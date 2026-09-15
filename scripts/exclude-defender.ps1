<#
.SYNOPSIS
  Skrip optimasi performa pengembang: menambahkan pengecualian Windows Defender
  untuk proyek Aksara dan ekosistem pengembangan PHP / Node.js / Playwright.

.DESCRIPTION
  Windows Defender Real-time Protection sering menyebabkan lonjakan CPU (MsMpEng.exe)
  dan latency tinggi saat scanning berkas ribuan dependensi (node_modules, vendor, storage,
  dan binary Playwright). Skrip ini mengecualikan direktori proyek dan proses pengembang utama.
  
.NOTES
  Memerlukan hak akses Administrator (Elevated PowerShell).
#>

# Cek apakah sesi saat ini memiliki hak akses Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "Hak akses Administrator diperlukan. Meminta izin UAC..." -ForegroundColor Yellow
    Start-Process powershell.exe -Verb RunAs -ArgumentList "-NoProfile", "-ExecutionPolicy Bypass", "-File `"$PSCommandPath`""
    exit
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "   AKSARA — OPTIMASI WINDOWS DEFENDER UNTUK DEVELOPER     " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Daftar Direktori Proyek & Tooling Cache
$pathsToExclude = @(
    "C:\Users\jejak\Documents\www\Bimtek\aksara",
    "C:\Users\jejak\Documents\www\Bimtek",
    "C:\Users\jejak\AppData\Local\ms-playwright",
    "C:\Users\jejak\AppData\Roaming\npm-cache",
    "C:\Users\jejak\AppData\Local\Composer",
    "C:\Users\jejak\.gemini\antigravity-ide"
)

# 2. Daftar Proses Pengembang
$processesToExclude = @(
    "php.exe",
    "node.exe",
    "git.exe",
    "chrome.exe",
    "headless_shell.exe"
)

Write-Host "[1/2] Menambahkan Pengecualian Folder (Paths)..." -ForegroundColor Green
foreach ($path in $pathsToExclude) {
    try {
        Add-MpPreference -ExclusionPath $path -ErrorAction Stop
        Write-Host "  [OK] Excluded Path: $path" -ForegroundColor Gray
    } catch {
        Write-Host "  [WARN] Gagal menambahkan $path : $_" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "[2/2] Menambahkan Pengecualian Proses (Processes)..." -ForegroundColor Green
foreach ($proc in $processesToExclude) {
    try {
        Add-MpPreference -ExclusionProcess $proc -ErrorAction Stop
        Write-Host "  [OK] Excluded Process: $proc" -ForegroundColor Gray
    } catch {
        Write-Host "  [WARN] Gagal menambahkan $proc : $_" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  SELESAI! Pengecualian Windows Defender berhasil dipasang" -ForegroundColor Green
Write-Host "  Proses scanning MsMpEng.exe tidak akan mengganggu kerja." -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""
Read-Host -Prompt "Tekan Enter untuk menutup jendela ini"
