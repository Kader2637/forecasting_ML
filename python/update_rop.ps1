# Update Buffer Stock dengan ROP dari CSV
# PowerShell version

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "UPDATE BUFFER STOCK DENGAN ROP (Reorder Point)" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Script ini akan:" -ForegroundColor Yellow
Write-Host "1. Membaca ROP_Unit dari buffer_stock_per_produk.csv"
Write-Host "2. Mapping produk ke database"
Write-Host "3. Update kolom buffer_stock di master_items_stock dengan ROP"
Write-Host ""

# Check if .env exists
if (-not (Test-Path ".env")) {
    Write-Host "ERROR: File .env tidak ditemukan!" -ForegroundColor Red
    Write-Host "Silakan buat .env terlebih dahulu:" -ForegroundColor Yellow
    Write-Host "  copy .env.example .env"
    Write-Host "  Edit .env dengan kredensial database Anda"
    Read-Host "Press Enter to exit"
    exit 1
}

# Activate virtual environment
if (Test-Path ".venv\Scripts\Activate.ps1") {
    & .venv\Scripts\Activate.ps1
    Write-Host "✓ Virtual environment activated" -ForegroundColor Green
} else {
    Write-Host "WARNING: Virtual environment tidak ditemukan" -ForegroundColor Yellow
    Write-Host "Attempting to install requirements..." -ForegroundColor Yellow
    python -m pip install pymysql python-dotenv pandas openpyxl
}

# Run the Python script
Write-Host ""
Write-Host "Menjalankan script..." -ForegroundColor Yellow
Write-Host ""
python update_rop_to_buffer_stock.py

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host "✓ SUKSES! Buffer stock berhasil di-update dengan ROP" -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Green
    Read-Host "Press Enter to exit"
} else {
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Red
    Write-Host "✗ ERROR! Terjadi kesalahan saat update buffer stock" -ForegroundColor Red
    Write-Host "============================================================" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}
