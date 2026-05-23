#!/usr/bin/env python
# coding: utf-8

# # Perhitungan Buffer Stock
# 
# Notebook ini menghitung buffer stock berdasarkan dataset penjualan dengan rumus:
# 
# **Buffer Stock = (Max Daily Sales – Avg Daily Sales) × Max Lead Time**
# 
# Dimana:
# - **Max Daily Sales**: Penjualan harian maksimum persentil ke-95 (tahan outlier)
# - **Avg Daily Sales**: Rata-rata penjualan harian
# - **Max Lead Time**: Lead time maksimum yang mungkin terjadi
# 
# Rumus ini memastikan: semakin tinggi actual sales, semakin tinggi buffer stock (korelasi positif).

# ## 1. Import Library

# In[2]:


import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns

# Set style untuk visualisasi
sns.set_style('whitegrid')
plt.rcParams['figure.figsize'] = (12, 6)


# ## 2. Load Dataset

# In[3]:


# Load dataset
file_path = 'Dataset_Forecasting_ARIMA_Lengkap.xlsx'
dataset = pd.read_excel(file_path)

# Tampilkan beberapa baris pertama
print("Dataset Info:")
print(dataset.head())
print("\nJumlah data:", len(dataset))
print("\nKolom dataset:", dataset.columns.tolist())


# ## 3. Preprocessing Data

# In[4]:


# Convert Date column ke datetime format (coerce invalid values to NaT)
dataset['Date'] = pd.to_datetime(dataset['Date'], errors='coerce')

# Drop rows where Date could not be parsed (e.g. summary/header rows)
invalid_dates = dataset['Date'].isna().sum()
if invalid_dates > 0:
    print(f"⚠️ Menghapus {invalid_dates} baris dengan nilai tanggal tidak valid")
    dataset = dataset.dropna(subset=['Date'])

# Sort berdasarkan tanggal
dataset = dataset.sort_values('Date')

# Reset index
dataset = dataset.reset_index(drop=True)

print("Data setelah preprocessing:")
print(dataset.head())
print("\nRentang Tanggal:")
print(f"Tanggal Awal: {dataset['Date'].min()}")
print(f"Tanggal Akhir: {dataset['Date'].max()}")


# ## 4. Identifikasi Kolom Produk

# In[5]:


# Identifikasi kolom produk (semua kolom kecuali Date dan Total_Sales)
product_columns = [col for col in dataset.columns if col not in ['Date', 'Total_Sales']]

print("=" * 60)
print("DAFTAR PRODUK YANG AKAN DIANALISIS")
print("=" * 60)
print(f"Jumlah produk: {len(product_columns)}")
print(f"\nProduk: {', '.join(product_columns)}")
print("=" * 60)


# ## 5. Hitung Statistik Pemakaian Harian per Produk

# In[12]:


# Hitung statistik untuk setiap produk
product_stats = pd.DataFrame()

for product in product_columns:
    stats = {
        'Produk': product,
        'Pemakaian_Maksimum': dataset[product].quantile(0.95),  # Persentil ke-95, tahan outlier
        'Pemakaian_Rata_rata': dataset[product].mean(),
        'Standar_Deviasi': dataset[product].std()
    }
    product_stats = pd.concat([product_stats, pd.DataFrame([stats])], ignore_index=True)

print("=" * 80)
print("STATISTIK PEMAKAIAN HARIAN PER PRODUK")
print("=" * 80)
print(product_stats.to_string(index=False))
print("=" * 80)

# Tampilkan top 5 produk berdasarkan pemakaian rata-rata
print("\nTOP 5 PRODUK BERDASARKAN PEMAKAIAN RATA-RATA:")
print(product_stats.nlargest(5, 'Pemakaian_Rata_rata')[['Produk', 'Pemakaian_Rata_rata', 'Pemakaian_Maksimum']].to_string(index=False))


# ## 6. Definisi Lead Time

# In[7]:


# Definisi Lead Time
avg_lead_time = 5.4   # hari (rata-rata)
max_lead_time = 7     # hari (maksimum / worst case)

print("=" * 60)
print("KOMPONEN LEAD TIME")
print("=" * 60)
print(f"  Avg Lead Time (Normal)      : {avg_lead_time} hari")
print(f"  Max Lead Time (Worst Case)  : {max_lead_time} hari")
print("=" * 60)


# ## 7. Perhitungan Buffer Stock per Produk
# 
# **Rumus:** `Buffer Stock = (Max Daily Sales × Max Lead Time) – (Avg Daily Sales × Avg Lead Time)`
# 

# In[13]:


# Hitung Buffer Stock & Safety Stock untuk setiap produk menggunakan rumus:
# Buffer Stock = Z × σ_demand × √Lead Time
# Z-score = 1.645 (95% Service Level)

z_score = 1.645
product_stats['Safety_Stock_95%'] = z_score * product_stats['Standar_Deviasi'] * np.sqrt(avg_lead_time)
product_stats['Buffer_Stock'] = product_stats['Safety_Stock_95%']

print("=" * 90)
print("PERHITUNGAN BUFFER STOCK PER PRODUK")
print("=" * 90)
print(f"Rumus: Z × σ_demand × √Lead Time")
print(f"       Z-score = {z_score} (95% Service Level)")
print(f"       Avg Lead Time = {avg_lead_time} hari")
print("=" * 90)
print(product_stats[['Produk', 'Standar_Deviasi', 'Safety_Stock_95%', 'Buffer_Stock']].to_string(index=False))
print("=" * 90)

# Tampilkan produk dengan buffer stock tertinggi
print("\n📦 TOP 10 PRODUK DENGAN BUFFER STOCK TERTINGGI:")
print(product_stats.nlargest(10, 'Buffer_Stock')[['Produk', 'Standar_Deviasi', 'Buffer_Stock']].to_string(index=False))


# ## 8. Safety Stock per Produk (Perhitungan Tambahan)
# 
# Sebagai perbandingan, kita juga dapat menghitung Safety Stock menggunakan metode standar deviasi:
# 
# **Safety Stock = Z-score × Standard Deviation × √Lead Time**
# 
# Dimana Z-score untuk service level 95% = 1.645

# In[16]:


print("=" * 80)
print("PERHITUNGAN SAFETY STOCK PER PRODUK (Service Level 95%)")
print("=" * 80)
print(f"Z-score: {z_score} | √Avg Lead Time: {np.sqrt(avg_lead_time):.4f}")
print("=" * 80)
print(product_stats[['Produk', 'Standar_Deviasi', 'Safety_Stock_95%']].to_string(index=False))
print("=" * 80)

# Perbandingan Buffer Stock vs Safety Stock
comparison_df = product_stats.nlargest(10, 'Buffer_Stock')[['Produk', 'Buffer_Stock', 'Safety_Stock_95%']]
print("\n📊 PERBANDINGAN BUFFER STOCK vs SAFETY STOCK (Top 10):")
print(comparison_df.to_string(index=False))


# ## 9. Perhitungan Reorder Point (ROP) per Produk
# 
# **Rumus:** `ROP = (d × L) + SS`
# 
# Dimana:
# - **d** = Rata-rata penggunaan harian (Pemakaian_Rata_rata)
# - **L** = Lead Time rata-rata dari vendor (dalam hari)
# - **SS** = Safety Stock/Buffer Stock
# 
# ROP adalah titik di mana pesanan baru harus dilakukan untuk memastikan stok tidak habis sebelum pemesanan baru tiba.

# In[ ]:


# Hitung Reorder Point (ROP) untuk setiap produk
# ROP = (d × L) + SS
# dimana d = rata-rata pemakaian harian, L = lead time, SS = safety stock

product_stats['ROP'] = (product_stats['Pemakaian_Rata_rata'] * avg_lead_time) + product_stats['Safety_Stock_95%']

print("=" * 100)
print("PERHITUNGAN REORDER POINT (ROP) PER PRODUK")
print("=" * 100)
print(f"Rumus: ROP = (d × L) + SS")
print(f"  d (Rata-rata Pemakaian Harian) = Pemakaian_Rata_rata")
print(f"  L (Lead Time Rata-rata)        = {avg_lead_time} hari")
print(f"  SS (Safety Stock)              = Safety_Stock_95%")
print("=" * 100)
print(product_stats[['Produk', 'Pemakaian_Rata_rata', 'Safety_Stock_95%', 'ROP']].to_string(index=False))
print("=" * 100)

# Tampilkan top 10 produk berdasarkan ROP
print("\n🎯 TOP 10 PRODUK BERDASARKAN REORDER POINT (ROP):")
print("=" * 100)
top_10_rop = product_stats.nlargest(10, 'ROP')[['Produk', 'Pemakaian_Rata_rata', 'Buffer_Stock', 'Safety_Stock_95%', 'ROP']]
top_10_rop_display = top_10_rop.copy()
top_10_rop_display.columns = ['Produk', 'Avg Daily Sales (d)', 'Buffer Stock', 'Safety Stock (SS)', 'ROP = (d×L) + SS']
print(top_10_rop_display.to_string(index=False))
print("=" * 100)

# Statistik ROP
total_rop = product_stats['ROP'].sum()
avg_rop = product_stats['ROP'].mean()
min_rop = product_stats['ROP'].min()
max_rop = product_stats['ROP'].max()

print(f"\n📈 STATISTIK ROP:")
print(f"  Total ROP Semua Produk : {total_rop:.2f} unit")
print(f"  Rata-rata ROP          : {avg_rop:.2f} unit")
print(f"  ROP Minimum            : {min_rop:.2f} unit")
print(f"  ROP Maximum            : {max_rop:.2f} unit")


# ## 9.1 Visualisasi Reorder Point (ROP)

# In[ ]:


# Visualisasi ROP
fig, axes = plt.subplots(2, 2, figsize=(16, 12))

# Plot 1: Top 15 Produk dengan ROP Tertinggi
top_15_rop = product_stats.nlargest(15, 'ROP').sort_values('ROP', ascending=True)
axes[0, 0].barh(top_15_rop['Produk'], top_15_rop['ROP'], color='steelblue', edgecolor='black')
axes[0, 0].set_title('Top 15 Produk dengan ROP tertinggi', fontsize=12, fontweight='bold')
axes[0, 0].set_xlabel('ROP (unit)')
axes[0, 0].grid(True, axis='x', alpha=0.3)

# Plot 2: Komposisi ROP = (d × L) + SS untuk Top 10 Produk
top_10_rop = product_stats.nlargest(10, 'ROP')
reorder_component = top_10_rop['Pemakaian_Rata_rata'] * avg_lead_time
safety_component = top_10_rop['Safety_Stock_95%']

x_pos = np.arange(len(top_10_rop))
width = 0.6

axes[0, 1].bar(x_pos, reorder_component, width, label='d × L (Reorder Qty)', color='lightcoral', edgecolor='black')
axes[0, 1].bar(x_pos, safety_component, width, bottom=reorder_component, label='SS (Safety Stock)', color='lightgreen', edgecolor='black')
axes[0, 1].set_title('Komposisi ROP = (d × L) + SS untuk Top 10 Produk', fontsize=12, fontweight='bold')
axes[0, 1].set_xlabel('Produk')
axes[0, 1].set_ylabel('ROP (unit)')
axes[0, 1].set_xticks(x_pos)
axes[0, 1].set_xticklabels(top_10_rop['Produk'], rotation=45, ha='right')
axes[0, 1].legend()
axes[0, 1].grid(True, axis='y', alpha=0.3)

# Plot 3: Perbandingan ROP vs Buffer Stock vs Safety Stock (Top 15)
top_15_comparison = product_stats.nlargest(15, 'ROP').sort_values('ROP', ascending=True)
y_pos = np.arange(len(top_15_comparison))
height = 0.25

axes[1, 0].barh(y_pos - height, top_15_comparison['ROP'], height, label='ROP', color='steelblue', edgecolor='black')
axes[1, 0].barh(y_pos, top_15_comparison['Buffer_Stock'], height, label='Buffer Stock', color='coral', edgecolor='black')
axes[1, 0].barh(y_pos + height, top_15_comparison['Safety_Stock_95%'], height, label='Safety Stock', color='lightgreen', edgecolor='black')
axes[1, 0].set_title('Perbandingan ROP vs Buffer Stock vs Safety Stock (Top 15)', fontsize=12, fontweight='bold')
axes[1, 0].set_xlabel('Jumlah (unit)')
axes[1, 0].set_yticks(y_pos)
axes[1, 0].set_yticklabels(top_15_comparison['Produk'])
axes[1, 0].legend()
axes[1, 0].grid(True, axis='x', alpha=0.3)

# Plot 4: Scatter Plot - Pemakaian Rata-rata vs ROP
axes[1, 1].scatter(product_stats['Pemakaian_Rata_rata'], product_stats['ROP'], 
                   s=100, alpha=0.6, c='steelblue', edgecolor='black')
axes[1, 1].set_title('Hubungan Pemakaian Rata-rata vs ROP', fontsize=12, fontweight='bold')
axes[1, 1].set_xlabel('Pemakaian Rata-rata (unit/hari)')
axes[1, 1].set_ylabel('ROP (unit)')
axes[1, 1].grid(True, alpha=0.3)

# Tambahkan label untuk produk dengan ROP tertinggi
top_5_rop_label = product_stats.nlargest(5, 'ROP')
for idx, row in top_5_rop_label.iterrows():
    axes[1, 1].annotate(row['Produk'], 
                       (row['Pemakaian_Rata_rata'], row['ROP']),
                       xytext=(5, 5), textcoords='offset points', 
                       fontsize=8, alpha=0.7)

plt.tight_layout()
plt.show()


# ## 9. Visualisasi Buffer Stock per Produk

# In[17]:


# Visualisasi Buffer Stock per Produk
fig, axes = plt.subplots(2, 2, figsize=(16, 12))

# Plot 1: Top 15 Produk dengan Buffer Stock Tertinggi
top_15_buffer = product_stats.nlargest(15, 'Buffer_Stock').sort_values('Buffer_Stock', ascending=True)
axes[0, 0].barh(top_15_buffer['Produk'], top_15_buffer['Buffer_Stock'], color='coral', edgecolor='black')
axes[0, 0].set_title('Top 15 Produk dengan Buffer Stock Tertinggi', fontsize=12, fontweight='bold')
axes[0, 0].set_xlabel('Buffer Stock (unit)')
axes[0, 0].grid(True, axis='x', alpha=0.3)

# Plot 2: Perbandingan Buffer Stock vs Safety Stock (Top 10)
top_10_comparison = product_stats.nlargest(10, 'Buffer_Stock')
x_pos = np.arange(len(top_10_comparison))
width = 0.35

axes[0, 1].bar(x_pos - width/2, top_10_comparison['Buffer_Stock'], width, label='Buffer Stock', color='coral', edgecolor='black')
axes[0, 1].bar(x_pos + width/2, top_10_comparison['Safety_Stock_95%'], width, label='Safety Stock', color='lightgreen', edgecolor='black')
axes[0, 1].set_title('Perbandingan Buffer Stock vs Safety Stock (Top 10)', fontsize=12, fontweight='bold')
axes[0, 1].set_xlabel('Produk')
axes[0, 1].set_ylabel('Jumlah (unit)')
axes[0, 1].set_xticks(x_pos)
axes[0, 1].set_xticklabels(top_10_comparison['Produk'], rotation=45, ha='right')
axes[0, 1].legend()
axes[0, 1].grid(True, axis='y', alpha=0.3)

# Plot 3: Distribusi Pemakaian Rata-rata per Produk
axes[1, 0].hist(product_stats['Pemakaian_Rata_rata'], bins=20, color='skyblue', edgecolor='black', alpha=0.7)
axes[1, 0].set_title('Distribusi Pemakaian Rata-rata per Produk', fontsize=12, fontweight='bold')
axes[1, 0].set_xlabel('Pemakaian Rata-rata (unit/hari)')
axes[1, 0].set_ylabel('Jumlah Produk')
axes[1, 0].grid(True, alpha=0.3)

# Plot 4: Scatter Plot - Pemakaian Rata-rata vs Buffer Stock
axes[1, 1].scatter(product_stats['Pemakaian_Rata_rata'], product_stats['Buffer_Stock'], 
                   s=100, alpha=0.6, c='steelblue', edgecolor='black')
axes[1, 1].set_title('Hubungan Pemakaian Rata-rata vs Buffer Stock', fontsize=12, fontweight='bold')
axes[1, 1].set_xlabel('Pemakaian Rata-rata (unit/hari)')
axes[1, 1].set_ylabel('Buffer Stock (unit)')
axes[1, 1].grid(True, alpha=0.3)

# Tambahkan label untuk produk dengan buffer stock tertinggi
top_5_label = product_stats.nlargest(5, 'Buffer_Stock')
for idx, row in top_5_label.iterrows():
    axes[1, 1].annotate(row['Produk'], 
                       (row['Pemakaian_Rata_rata'], row['Buffer_Stock']),
                       xytext=(5, 5), textcoords='offset points', 
                       fontsize=8, alpha=0.7)

plt.tight_layout()
plt.show()


# ## 10. Ringkasan Hasil & Rekomendasi

# In[18]:


# Ringkasan Statistik Keseluruhan
total_buffer_stock = product_stats['Buffer_Stock'].sum()
total_safety_stock = product_stats['Safety_Stock_95%'].sum()
avg_buffer_stock = product_stats['Buffer_Stock'].mean()
total_rop = product_stats['ROP'].sum()
avg_rop = product_stats['ROP'].mean()

print("\n" + "=" * 80)
print("RINGKASAN HASIL PERHITUNGAN BUFFER STOCK & ROP")
print("=" * 80)
print(f"Rumus        : (Max Daily Sales – Avg Daily Sales) × Max Lead Time")
print(f"Jumlah Produk Dianalisis        : {len(product_stats)} produk")
print(f"Avg Lead Time                   : {avg_lead_time} hari")
print(f"Max Lead Time                   : {max_lead_time} hari")
print(f"Total Buffer Stock (Semua Produk): {total_buffer_stock:.2f} unit")
print(f"Total Safety Stock (Semua Produk): {total_safety_stock:.2f} unit")
print(f"Rata-rata Buffer Stock per Produk: {avg_buffer_stock:.2f} unit")
print(f"Total ROP (Semua Produk)         : {total_rop:.2f} unit")
print(f"Rata-rata ROP per Produk         : {avg_rop:.2f} unit")
print("=" * 80)

print("\n📊 TOP 10 PRODUK PRIORITAS UNTUK BUFFER STOCK & ROP:")
print("=" * 80)
top_10 = product_stats.nlargest(10, 'Buffer_Stock')[['Produk', 'Pemakaian_Rata_rata', 'Pemakaian_Maksimum', 'Buffer_Stock', 'Safety_Stock_95%', 'ROP']]
top_10_display = top_10.copy()
top_10_display.columns = ['Produk', 'Avg Daily Sales', 'Max Daily Sales', 'Buffer Stock', 'Safety Stock', 'ROP']
print(top_10_display.to_string(index=False))
print("=" * 80)

print("\n💡 REKOMENDASI:")
print("-" * 80)
print(f"1. Prioritaskan produk dengan buffer stock tertinggi untuk dijaga ketersediaannya")
print(f"2. Total buffer stock yang diperlukan untuk semua produk: {total_buffer_stock:.2f} unit")
print(f"3. Total ROP yang diperlukan untuk semua produk: {total_rop:.2f} unit")
print(f"4. Produk dengan buffer stock > {avg_buffer_stock:.0f} unit memerlukan perhatian khusus")
print(f"5. Tinjau ulang buffer stock dan ROP secara berkala sesuai perubahan pola permintaan")
print("=" * 80)


# ## 11. Export Hasil ke CSV

# In[ ]:


# Siapkan dataframe untuk export dengan informasi lengkap
export_df = product_stats[['Produk', 'Pemakaian_Maksimum', 'Pemakaian_Rata_rata', 
                           'Standar_Deviasi', 'Buffer_Stock', 'Safety_Stock_95%']].copy()
export_df.columns = [
    'Produk',
    'Max_Daily_Sales',
    'Avg_Daily_Sales',
    'Standar_Deviasi',
    'Buffer_Stock_Unit',
    'Safety_Stock_95percent_Unit'
]

# Tambahkan informasi lead time ke dalam dataframe export
export_df['Avg_Lead_Time_Hari'] = avg_lead_time
export_df['Max_Lead_Time_Hari'] = max_lead_time

# Tambahkan kolom keterangan rumus
export_df['Rumus'] = f'Z x Standar_Deviasi x akar({avg_lead_time})'

# Urutkan berdasarkan buffer stock tertinggi
export_df = export_df.sort_values('Buffer_Stock_Unit', ascending=False)

# Round semua angka ke 2 desimal
numeric_columns = export_df.select_dtypes(include=[np.number]).columns
export_df[numeric_columns] = export_df[numeric_columns].round(2)

# Save to CSV
output_file = 'buffer_stock_per_produk.csv'
export_df.to_csv(output_file, index=False, encoding='utf-8-sig')

print(f"✅ Hasil analisis buffer stock per produk berhasil disimpan ke:")
print(f"   {output_file}")
print(f"\n📊 Total: {len(export_df)} produk")
print(f"\nPreview 10 produk teratas:")
print(export_df[['Produk', 'Avg_Daily_Sales', 'Max_Daily_Sales', 'Buffer_Stock_Unit']].head(10).to_string(index=False))

