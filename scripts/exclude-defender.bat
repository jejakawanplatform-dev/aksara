@echo off
:: Aksara — Skrip Otomatis Pengecualian Windows Defender
:: Menjalankan exclude-defender.ps1 dengan hak Administrator (Elevated)

echo Meminta hak akses Administrator untuk Windows Defender...
powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process powershell.exe -Verb RunAs -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File \"%~dp0exclude-defender.ps1\"'"
