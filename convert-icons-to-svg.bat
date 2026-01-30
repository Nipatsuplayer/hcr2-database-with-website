@echo off
REM PNG to SVG Converter - Batch Script for Windows
REM This script provides quick access to conversion tools and methods

setlocal enabledelayedexpansion

color 0A
title PNG to SVG Icon Converter

echo.
echo ========================================
echo PNG to SVG Icon Converter
echo ========================================
echo.

REM Check if we're in the right directory
if not exist "img\map_icons" (
    color 0C
    echo ERROR: map_icons directory not found!
    echo This script must be run from the project root directory.
    pause
    exit /b 1
)

if not exist "img\vehicle_icons" (
    color 0C
    echo ERROR: vehicle_icons directory not found!
    echo This script must be run from the project root directory.
    pause
    exit /b 1
)

if not exist "img\tuning_parts_icons" (
    color 0C
    echo ERROR: tuning_parts_icons directory not found!
    echo This script must be run from the project root directory.
    pause
    exit /b 1
)

echo Directories found:
echo - img\map_icons
echo - img\vehicle_icons
echo - img\tuning_parts_icons
echo.

REM Count PNG files
setlocal enabledelayedexpansion
set "map_count=0"
set "vehicle_count=0"
set "tuning_count=0"

for /F %%A in ('dir /b "img\map_icons\*.png" 2^>nul ^| find /c /v ""') do set "map_count=%%A"
for /F %%A in ('dir /b "img\vehicle_icons\*.png" 2^>nul ^| find /c /v ""') do set "vehicle_count=%%A"
for /F %%A in ('dir /b "img\tuning_parts_icons\*.png" 2^>nul ^| find /c /v ""') do set "tuning_count=%%A"

if "!map_count!"=="" set "map_count=0"
if "!vehicle_count!"=="" set "vehicle_count=0"
if "!tuning_count!"=="" set "tuning_count=0"

set /a "total_count=map_count+vehicle_count+tuning_count"

echo Found !map_count! map icon PNG files
echo Found !vehicle_count! vehicle icon PNG files
echo Found !tuning_count! tuning parts icon PNG files
echo Total: !total_count! files to convert
echo.

if !total_count! equ 0 (
    color 0E
    echo No PNG files found to convert!
    pause
    exit /b 0
)

echo.
echo Choose a conversion method:
echo.
echo [1] Use PowerShell converter (Recommended - requires ImageMagick/Potrace)
echo [2] Online conversion (CloudConvert - browser-based)
echo [3] Manual installation instructions
echo [4] Exit
echo.

set /p "choice=Enter your choice (1-4): "

if "!choice!"=="1" (
    cls
    echo Running PowerShell converter...
    echo.
    powershell -NoProfile -ExecutionPolicy Bypass -File "convert-icons-to-svg.ps1"
    pause
) else if "!choice!"=="2" (
    cls
    echo.
    echo ============================================
    echo Online SVG Conversion (CloudConvert)
    echo ============================================
    echo.
    echo Steps:
    echo 1. Go to: https://cloudconvert.com/png-to-svg
    echo 2. Upload PNG files from:
    echo    - img\map_icons\*.png
    echo    - img\vehicle_icons\*.png
    echo    - img\tuning_parts_icons\*.png
    echo 3. Download the converted SVG files
    echo 4. Save them to the same directories (overwrite is OK)
    echo 5. The code will automatically use SVG files if available
    echo.
    echo Opening CloudConvert in browser...
    start https://cloudconvert.com/png-to-svg
    pause
) else if "!choice!"=="3" (
    cls
    echo.
    echo ============================================
    echo Installation Instructions
    echo ============================================
    echo.
    echo For best results, install both tools:
    echo.
    echo STEP 1: Install ImageMagick
    echo   1. Visit: https://imagemagick.org/download.php
    echo   2. Download the Windows installer
    echo   3. Run installer
    echo   4. Check "Install legacy utilities" and "Add to PATH"
    echo   5. Click Install
    echo.
    echo STEP 2: Install Potrace (optional but recommended)
    echo   1. Visit: http://potrace.sourceforge.net/
    echo   2. Download Windows version
    echo   3. Extract and add to PATH or use full path
    echo.
    echo STEP 3: Run this script again and choose option 1
    echo.
    echo.
    echo After installation, the converter will:
    echo - Convert PNGs to high-quality vector SVGs
    echo - Reduce file sizes significantly
    echo - Keep PNG files as fallback
    echo.
    pause
) else (
    color 0C
    echo Invalid choice!
    pause
    exit /b 1
)

endlocal
