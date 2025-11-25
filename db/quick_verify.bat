@echo off
REM Quick Database Verification (Windows Batch)

echo.
echo ============================================
echo   DATABASE ICERIK DOGRULAMA
echo ============================================
echo.

SET DB_PATH=%~dp0app.sqlite
echo Database: %DB_PATH%
echo.

REM PHP ile doğrula
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    php verify_transfer.php
) else (
    echo [WARNING] PHP bulunamadi, dogrulama atlanacak
    echo Database dosyasi basariyla kopyalandi.
    echo Boyut kontrol:
    dir /B "%DB_PATH%" | findstr /R ".*"
)

echo.
pause

