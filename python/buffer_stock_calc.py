"""
Buffer Stock Calculation Module
Menghitung buffer stock berdasarkan data penjualan dengan rumus:
Buffer Stock = (Max Daily Sales – Avg Daily Sales) × Max Lead Time

Rumus ini memastikan korelasi POSITIF: semakin tinggi actual sales → semakin tinggi buffer stock.
"""

import pandas as pd
import numpy as np
from typing import Dict, List, Optional
import os


class BufferStockCalculator:
    """Kelas untuk menghitung buffer stock per produk"""
    
    def __init__(self, excel_path: str, avg_lead_time: float = 5.4, max_lead_time: float = 7):
        """
        Initialize calculator dengan dataset
        
        Args:
            excel_path: Path ke file Excel yang berisi data penjualan
            avg_lead_time: Average lead time dalam hari (default: 5.4)
            max_lead_time: Maximum lead time dalam hari (default: 7)
        """
        self.excel_path = excel_path
        self.avg_lead_time = avg_lead_time
        self.max_lead_time = max_lead_time
        self.dataset = None
        self.product_stats = None
        self.load_and_process_data()
    
    def load_and_process_data(self):
        """Load dan preprocess data dari Excel"""
        try:
            # Load dataset
            self.dataset = pd.read_excel(self.excel_path)
            
            # Convert Date column ke datetime
            self.dataset['Date'] = pd.to_datetime(self.dataset['Date'], errors='coerce')
            
            # Drop rows dengan date invalid
            invalid_dates = self.dataset['Date'].isna().sum()
            if invalid_dates > 0:
                self.dataset = self.dataset.dropna(subset=['Date'])
            
            # Sort by date
            self.dataset = self.dataset.sort_values('Date')
            self.dataset = self.dataset.reset_index(drop=True)
            
            # Compute statistics
            self._compute_statistics()
            
        except Exception as e:
            raise Exception(f"Error loading data: {str(e)}")
    
    def _compute_statistics(self):
        """Compute statistics untuk setiap produk"""
        # Identify product columns (semua kecuali Date dan Total_Sales)
        product_columns = [col for col in self.dataset.columns 
                          if col not in ['Date', 'Total_Sales']]
        
        product_stats = []
        
        for product in product_columns:
            try:
                # Gunakan persentil 95% sebagai max_daily_sales (tahan outlier)
                max_daily_sales = self.dataset[product].quantile(0.95)
                avg_daily_sales = self.dataset[product].mean()
                
                # Pastikan max >= avg
                max_daily_sales = max(max_daily_sales, avg_daily_sales)
                
                # Calculate safety stock (metode standar deviasi, service level 95%)
                z_score = 1.645
                std_dev = self.dataset[product].std()
                safety_stock = z_score * std_dev * np.sqrt(self.avg_lead_time)
                
                # Alinyan buffer stock ke safety stock: Z * std_dev * sqrt(LeadTime)
                buffer_stock = safety_stock
                
                stats = {
                    'Produk': product,
                    'Pemakaian_Maksimum': max_daily_sales,
                    'Pemakaian_Rata_rata': avg_daily_sales,
                    'Standar_Deviasi': std_dev,
                    'Buffer_Stock': buffer_stock,
                    'Safety_Stock_95%': safety_stock
                }
                
                product_stats.append(stats)
                
            except Exception as e:
                # Skip product jika ada error
                print(f"Warning: Error calculating stats for {product}: {str(e)}")
                continue
        
        self.product_stats = pd.DataFrame(product_stats)
    
    def get_all_buffer_stocks(self) -> List[Dict]:
        """Get buffer stock untuk semua produk"""
        if self.product_stats is None:
            return []
        
        return self.product_stats.to_dict('records')
    
    def get_buffer_stock_by_product(self, product_name: str) -> Optional[Dict]:
        """Get buffer stock untuk produk spesifik"""
        if self.product_stats is None:
            return None
        
        result = self.product_stats[self.product_stats['product_name'] == product_name]
        
        if result.empty:
            return None
        
        return result.iloc[0].to_dict()
    
    def get_summary(self) -> Dict:
        """Get summary dari buffer stock calculation"""
        if self.product_stats is None:
            return {}
        
        return {
            'total_products': len(self.product_stats),
            'total_buffer_stock': self.product_stats['Buffer_Stock'].sum(),
            'avg_buffer_stock': self.product_stats['Buffer_Stock'].mean(),
            'total_safety_stock': self.product_stats['Safety_Stock_95%'].sum(),
            'max_buffer_stock': self.product_stats['Buffer_Stock'].max(),
            'min_buffer_stock': self.product_stats['Buffer_Stock'].min(),
            'avg_lead_time': self.avg_lead_time,
            'max_lead_time': self.max_lead_time,
            'calculation_formula': '(Max Daily Sales – Avg Daily Sales) × Max Lead Time'
        }
    
    def get_top_products(self, n: int = 10) -> List[Dict]:
        """Get top N products berdasarkan buffer stock"""
        if self.product_stats is None:
            return []
        
        top = self.product_stats.nlargest(n, 'Buffer_Stock')
        return top.to_dict('records')
    
    def export_to_csv(self, output_path: str):
        """Export hasil calculation ke CSV sesuai format notebook"""
        if self.product_stats is None:
            raise Exception("No data to export")
        
        export_df = self.product_stats.copy()
        # Rename columns to match notebook format
        export_df = export_df[['Produk', 'Pemakaian_Maksimum', 'Pemakaian_Rata_rata', 
                               'Standar_Deviasi', 'Buffer_Stock', 'Safety_Stock_95%']]
        export_df.columns = [
            'Produk',
            'Max_Daily_Sales',
            'Avg_Daily_Sales',
            'Standar_Deviasi',
            'Buffer_Stock_Unit',
            'Safety_Stock_95percent_Unit'
        ]
        
        # Tambahkan informasi lead time
        export_df['Avg_Lead_Time_Hari'] = self.avg_lead_time
        export_df['Max_Lead_Time_Hari'] = self.max_lead_time
        
        # Tambahkan kolom keterangan rumus
        export_df['Rumus'] = f'(Max Daily Sales - Avg Daily Sales) x {self.max_lead_time}'
        
        # Urutkan berdasarkan buffer stock tertinggi
        export_df = export_df.sort_values('Buffer_Stock_Unit', ascending=False)
        
        # Round semua angka ke 2 desimal
        numeric_columns = export_df.select_dtypes(include=[np.number]).columns
        export_df[numeric_columns] = export_df[numeric_columns].round(2)
        
        # Save to CSV
        export_df.to_csv(output_path, index=False, encoding='utf-8-sig')
        print(f"✅ Hasil analisis buffer stock per produk berhasil disimpan ke: {output_path}")
        print(f"📊 Total: {len(export_df)} produk")
