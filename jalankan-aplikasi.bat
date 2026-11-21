@echo off
title Seka Yasa POS - Server Lokal
echo ===================================================
echo   Menjalankan Server Aplikasi POS (Port 8000)
echo ===================================================
echo.
echo Pastikan MySQL (Laragon / XAMPP) sudah dalam kondisi Start/Running.
echo.
echo Akses di Laptop/PC : http://localhost:8000
echo Akses dari HP Android: http://192.168.1.10:8000/pos
echo.
echo Tekan Ctrl+C untuk menghentikan server.
echo ===================================================
echo.
cd /d "%~dp0app-pos"
php artisan serve --host=0.0.0.0 --port=8000
pause
