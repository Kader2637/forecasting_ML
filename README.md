# Inventory Management System & Demand Forecasting (ARIMA)

Sistem Manajemen Inventori dan Prediksi Permintaan berbasis Machine Learning (ARIMA) menggunakan kerangka kerja Laravel dan Python. Proyek ini memfasilitasi monitoring stok secara real-time, pengaturan Buffer Stock dan Bill of Materials (BOM), serta memprediksi kebutuhan stok bahan baku dan produk jadi untuk mengoptimalkan operasional bisnis.

## Fitur Utama

- **Dashboard Inventori**: Monitoring produk, status stok, total transaksi, dan ringkasan Buffer Stock.
- **Master Data Management**: Kelola Bahan Baku (Raw Materials), Kategori Produk, dan Produk Jadi beserta Bill of Materials (BOM) terkait.
- **Demand Forecasting (ARIMA)**:
  - Integrasi script Python (`statsmodels`) untuk melatih dan memprediksi kebutuhan berdasarkan riwayat transaksi.
  - Perhitungan metrik evaluasi model seperti MAE, RMSE, dan MAPE.
  - Visualisasi grafik prediksi yang interaktif di halaman antarmuka.
- **Buffer Stock & Lead Time Management**: Perhitungan pengamanan stok dengan metrik Service Level 95% (Z-score 1.645) serta ROP (Reorder Point).
- **Stock Opname & Adjustments**: Sinkronisasi stok fisik dengan sistem dan pelacakan historis setiap penyesuaian (adjustment).
- **Transaction History**: Pelacakan riwayat masuk (in) dan keluar (out) barang.
- **Notifikasi Interaktif**: SweetAlert2 untuk konfirmasi dan umpan balik aksi user secara *real-time*.

## Stack Teknologi

- **Backend**: Laravel 12.x, PHP 8.2+
- **Machine Learning**: Python 3.x (pandas, numpy, statsmodels, pmdarima)
- **Database**: MySQL 8.x
- **Frontend / UI**: Tailwind CSS, Bootstrap Icons, Chart.js, SweetAlert2, jQuery, Select2
- **Template Dasar**: Sneat Admin Dashboard

## Panduan Instalasi (Local Development)

### Prasyarat:
- PHP >= 8.2
- Composer
- Node.js & npm
- Python >= 3.9 (beserta modul: `pandas`, `statsmodels`, `scikit-learn`, `pmdarima`, `mysql-connector-python`)
- MySQL Server

### Langkah-langkah:

1. **Clone repositori:**
   ```bash
   git clone https://github.com/Kader2637/forecasting_ML.git
   cd forecasting_ML
   ```

2. **Instalasi *dependencies* PHP dan Frontend:**
   ```bash
   composer install
   npm install
   npm run build
   ```

3. **Konfigurasi Lingkungan (.env):**
   Salin `.env.example` ke `.env` dan konfigurasikan akses ke *database* MySQL.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migrasi dan Seeding Database:**
   Untuk menyemai struktur *database* yang dibutuhkan:
   ```bash
   php artisan migrate
   ```
   **[UNTUK DEMO SIDANG]** Jalankan *Seeder* khusus untuk memompa data transaksi fiktif beserta skenario *dummy* hasil prediksi ML (ARIMA) untuk produk Gentle Baby:
   ```bash
   php artisan db:seed --class=ThesisForecastingSeeder
   ```

5. **Menjalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
   Aplikasi dapat diakses melalui `http://localhost:8000`.

## Menjalankan Skrip ARIMA (Machine Learning)

Untuk menjalankan modul AI / *forecasting* aktual:
Skrip *Machine Learning* berada di folder `python/dynamic_arima_production.py`.
Fitur di aplikasi sudah mengintegrasikan pemanggilan skrip Python ini, atau Anda bisa menjalankannya secara manual dari terminal:
```bash
python python/dynamic_arima_production.py
```
*(Catatan: pastikan pengaturan kredensial database di dalam skrip `.py` sudah sama dengan `.env` Anda)*

## Hak Cipta
Aplikasi ini dikembangkan untuk penyelesaian Tugas Akhir / Skripsi. Seluruh skema *forecasting* diimplementasikan untuk simulasi operasional bisnis nyata.
