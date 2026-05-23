"""
Update Buffer Stock dengan ROP (Reorder Point)
Script untuk membaca ROP dari CSV dan update kolom buffer_stock di master_items_stock
"""

import pandas as pd
import pymysql
import os
import json
import logging
from dotenv import load_dotenv
from typing import Dict, Tuple

# Load environment variables
load_dotenv()

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(levelname)s | %(message)s",
)
logger = logging.getLogger(__name__)


class ROPBufferStockUpdater:
    """Class untuk update buffer_stock dengan ROP"""
    
    def __init__(
        self,
        csv_path: str = 'buffer_stock_per_produk.csv',
        mapping_file: str = 'product_mapping.json',
        db_host: str = None,
        db_user: str = None,
        db_password: str = None,
        db_name: str = None,
    ):
        """Initialize dengan path CSV dan config database"""
        self.csv_path = csv_path
        self.mapping_file = mapping_file
        
        # Database config
        self.db_host = db_host or os.getenv('DB_HOST', 'localhost')
        self.db_user = db_user or os.getenv('DB_USERNAME', 'root')
        self.db_password = db_password or os.getenv('DB_PASSWORD', '')
        self.db_name = db_name or os.getenv('DB_DATABASE', 'skripsi_forecasting')
        self.db_port = int(os.getenv('DB_PORT', 3306))
        
        # Load mappings
        self.product_mapping = self._load_product_mapping()
        self.db_items = {}
        self.connection = None
        
    def _load_product_mapping(self) -> Dict[str, str]:
        """Load product mapping dari JSON"""
        if not os.path.exists(self.mapping_file):
            logger.warning(f"⚠ Mapping file tidak ditemukan: {self.mapping_file}")
            return {}
        
        try:
            with open(self.mapping_file, 'r', encoding='utf-8') as f:
                raw_mapping = json.load(f)
            
            # Support 2 format mapping
            if isinstance(raw_mapping, dict) and 'products' in raw_mapping:
                # Format: {"products": {"SKU": {"mapped_to": "DB Name"}}}
                mapping = {
                    sku: data.get('mapped_to', sku)
                    for sku, data in raw_mapping.get('products', {}).items()
                }
            else:
                # Format flat: {"SKU": "DB Name"}
                mapping = raw_mapping
            
            logger.info(f"✓ Loaded {len(mapping)} product mappings")
            return mapping
        except Exception as e:
            logger.error(f"✗ Error loading mapping file: {e}")
            return {}
    
    def connect_to_database(self) -> bool:
        """Connect ke database"""
        try:
            self.connection = pymysql.connect(
                host=self.db_host,
                port=self.db_port,
                user=self.db_user,
                password=self.db_password,
                database=self.db_name,
                charset='utf8mb4',
                cursorclass=pymysql.cursors.DictCursor
            )
            logger.info(f"✓ Connected to database: {self.db_name}@{self.db_host}")
            return True
        except pymysql.Error as e:
            logger.error(f"✗ Database connection failed: {e}")
            return False
    
    def get_database_items(self) -> Dict[str, int]:
        """Get semua item dari database"""
        if not self.connection:
            return {}
        
        try:
            with self.connection.cursor() as cursor:
                query = """
                    SELECT item_id, name_item, code_item 
                    FROM master_items 
                    WHERE status_item = 'active'
                """
                cursor.execute(query)
                items = cursor.fetchall()
                
                # Build mapping: item_name -> item_id dan code_item -> item_id
                item_map = {}
                for item in items:
                    if item['name_item']:
                        item_map[item['name_item'].lower()] = item['item_id']
                    if item['code_item']:
                        item_map[item['code_item'].lower()] = item['item_id']
                
                logger.info(f"✓ Loaded {len(items)} items dari database")
                return item_map
        except Exception as e:
            logger.error(f"✗ Error getting database items: {e}")
            return {}
    
    def normalize_product_name(self, name: str) -> str:
        """Normalize nama produk untuk matching"""
        if not name:
            return ""
        # Convert to lowercase, remove extra spaces
        normalized = name.lower().strip()
        # Remove special characters except numbers and letters
        normalized = ''.join(c for c in normalized if c.isalnum() or c.isspace())
        return normalized
    
    def find_item_id(self, product_name: str) -> int:
        """Find item_id dari product name"""
        if not product_name:
            return None
        
        # 1. Try exact match dengan mapping
        mapped_name = self.product_mapping.get(product_name)
        if mapped_name and mapped_name.lower() in self.db_items:
            return self.db_items[mapped_name.lower()]
        
        # 2. Try direct match di database items
        normalized = self.normalize_product_name(product_name)
        for db_name, item_id in self.db_items.items():
            if normalized in db_name or db_name in normalized:
                return item_id
        
        return None
    
    def read_rop_from_csv(self) -> pd.DataFrame:
        """Read ROP values dari CSV"""
        try:
            df = pd.read_csv(self.csv_path, encoding='utf-8-sig')
            logger.info(f"✓ Loaded CSV dengan {len(df)} produk dari {self.csv_path}")
            
            # Validate required columns
            required_cols = ['Produk', 'Buffer_Stock_Unit']
            missing_cols = [col for col in required_cols if col not in df.columns]
            if missing_cols:
                if 'ROP_Unit' in df.columns:
                    logger.info("✓ Found 'ROP_Unit' column as fallback")
                else:
                    logger.error(f"✗ CSV missing columns: {missing_cols}")
                    return None
            
            return df
        except Exception as e:
            logger.error(f"✗ Error reading CSV: {e}")
            return None
    
    def update_buffer_stock(self, inventory_id: int = 1) -> Tuple[int, int, int]:
        """
        Update buffer_stock di master_items_stock dengan ROP values
        
        Args:
            inventory_id: ID inventori untuk di-update (default: 1 = Main Warehouse)
        
        Returns:
            Tuple: (updated_count, skipped_count, error_count)
        """
        if not self.connection:
            logger.error("✗ Database not connected")
            return 0, 0, 0
        
        # Read CSV
        df = self.read_rop_from_csv()
        if df is None:
            return 0, 0, 0
        
        # Get database items
        self.db_items = self.get_database_items()
        if not self.db_items:
            logger.error("✗ No items found in database")
            return 0, 0, 0
        
        updated_count = 0
        skipped_count = 0
        error_count = 0
        
        logger.info("\n" + "="*80)
        logger.info(f"UPDATING BUFFER STOCK DENGAN ROP VALUES")
        logger.info(f"Source: {self.csv_path}")
        logger.info(f"Target: master_items_stock (inventory_id={inventory_id})")
        logger.info("="*80)
        
        # Update each product
        for idx, row in df.iterrows():
            product_name = row['Produk']
            rop_value = row['Buffer_Stock_Unit'] if 'Buffer_Stock_Unit' in df.columns else row['ROP_Unit']
            
            try:
                # Find item_id
                item_id = self.find_item_id(product_name)
                if not item_id:
                    logger.warning(f"⊘ SKIP: Produk '{product_name}' tidak ditemukan di database")
                    skipped_count += 1
                    continue
                
                # Round ROP to integer
                rop_int = int(round(float(rop_value), 0))
                
                # Update database
                with self.connection.cursor() as cursor:
                    # Check if record exists
                    cursor.execute("""
                        SELECT item_stock_id FROM master_items_stock 
                        WHERE item_id = %s AND inventory_id = %s
                    """, (item_id, inventory_id))
                    
                    existing = cursor.fetchone()
                    
                    if existing:
                        # Update existing record
                        cursor.execute("""
                            UPDATE master_items_stock 
                            SET buffer_stock = %s
                            WHERE item_id = %s AND inventory_id = %s
                        """, (rop_int, item_id, inventory_id))
                    else:
                        # Insert new record if not exists
                        cursor.execute("""
                            INSERT INTO master_items_stock 
                            (item_id, inventory_id, stock, buffer_stock) 
                            VALUES (%s, %s, 0, %s)
                        """, (item_id, inventory_id, rop_int))
                
                logger.info(f"✓ {product_name}: buffer_stock = {rop_int} (ROP)")
                updated_count += 1
                
            except Exception as e:
                logger.error(f"✗ ERROR updating '{product_name}': {str(e)}")
                error_count += 1
        
        # Commit changes
        try:
            self.connection.commit()
            logger.info("\n" + "="*80)
            logger.info(f"✓ BERHASIL: {updated_count} produk di-update")
            logger.info(f"⊘ SKIP: {skipped_count} produk tidak ditemukan")
            logger.info(f"✗ ERROR: {error_count} produk gagal")
            logger.info("="*80)
            return updated_count, skipped_count, error_count
        except Exception as e:
            logger.error(f"✗ Commit failed: {e}")
            self.connection.rollback()
            return 0, 0, len(df)
    
    def close(self):
        """Close database connection"""
        if self.connection:
            self.connection.close()
            logger.info("✓ Database connection closed")


def main():
    """Main function"""
    import sys
    
    try:
        # Initialize updater
        updater = ROPBufferStockUpdater(
            csv_path='buffer_stock_per_produk.csv',
            mapping_file='product_mapping.json'
        )
        
        # Connect to database
        if not updater.connect_to_database():
            logger.error("✗ Failed to connect to database")
            sys.exit(1)
        
        # Update buffer stock
        updated, skipped, errors = updater.update_buffer_stock(inventory_id=1)
        
        # Close connection
        updater.close()
        
        # Exit code based on results
        sys.exit(0 if updated > 0 else 1)
        
    except Exception as e:
        logger.error(f"✗ FATAL ERROR: {e}")
        sys.exit(1)


if __name__ == '__main__':
    main()
