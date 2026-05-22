@echo off
REM Update Buffer Stock dengan ROP dari CSV
REM Script ini membaca ROP dari buffer_stock_per_produk.csv dan update ke database

echo ============================================================
echo UPDATE BUFFER STOCK DENGAN ROP (Reorder Point)
echo ============================================================
echo.
echo Script ini akan:
echo 1. Membaca ROP_Unit dari buffer_stock_per_produk.csv
echo 2. Mapping produk ke database
echo 3. Update kolom buffer_stock di master_items_stock dengan ROP
echo.

REM Check if .env exists
if not exist ".env" (
    echo ERROR: File .env tidak ditemukan!
    echo Silakan buat .env terlebih dahulu:
    echo   copy .env.example .env
    echo   Edit .env dengan kredensial database Anda
    pause
    exit /b 1
)

REM Activate virtual environment
if exist ".venv\Scripts\activate.bat" (
    call .venv\Scripts\activate.bat
    echo ✓ Virtual environment activated
) else (
    echo WARNING: Virtual environment tidak ditemukan
    echo Attempting to install requirements...
    pip install pymysql python-dotenv pandas openpyxl
)

REM Run the Python script
echo.
echo Menjalankan script...
echo.
python update_rop_to_buffer_stock.py

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================================
    echo ✓ SUKSES! Buffer stock berhasil di-update dengan ROP
    echo ============================================================
    pause
) else (
    echo.
    echo ============================================================
    echo ✗ ERROR! Terjadi kesalahan saat update buffer stock
    echo ============================================================
    pause
    exit /b 1
)
