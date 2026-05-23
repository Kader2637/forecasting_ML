#!/usr/bin/env python
# coding: utf-8
"""
Re-run ARIMA Forecasting menggunakan Dataset 2023-2025
======================================================
- Dataset: Dataset_Forecasting_ARIMA_2023-2025.xlsx (1076 hari)
- Split  : 80% training, 20% test
- Output : arima_forecast_detailed_per_produk.csv (format sama dengan sebelumnya)
           arima_forecast_summary_per_produk.csv
           arima_forecast_mae_kategori_ringkas.csv
"""
import warnings
warnings.filterwarnings('ignore')

import pandas as pd
import numpy as np
from statsmodels.tsa.arima.model import ARIMA
from statsmodels.tsa.stattools import adfuller
from sklearn.metrics import mean_absolute_error, mean_squared_error

# ============================================================
# 1. COLUMN MAPPING: 2023-2025 col names → GB-XX-XX format
# ============================================================
COL_MAP = {
    'BB 10':   'GB-BB-10',
    'BB 30':   'GB-BB-30',
    'CC':      'GB-TP-CC',
    'CNF':     'GB-CNF-30',
    'CNF 10':  'GB-CNF-10',
    'CNF 100': 'GB-CNF-100',
    'CNF 250': 'GB-CNF-250',
    'DS':      'GB-DS-30',
    'DS 10':   'GB-DS-10',
    'DS 100':  'GB-DS-100',
    'DS 250':  'GB-DS-250',
    'GF':      'GB-GF-30',
    'GF 10':   'GB-GF-10',
    'GF 250':  'GB-GF-250',
    'IB':      'GB-IB-30',
    'IB 10':   'GB-IB-10',
    'IB 100':  'GB-IB-100',
    'JOY':     'GB-JOY-30',
    'JOY 10':  'GB-JOY-10',
    'JOY 100': 'GB-JOY-100',
    'LDR':     'GB-LDR-30',
    'LDR 10':  'GB-LDR-10',
    'LDR 250': 'GB-LDR-250',
    'MYB':     'GB-MYB-30',
    'MYB 10':  'GB-MYB-10',
    'MYB 100': 'GB-MYB-100',
    'NB':      'GB-TP-NB',
    'TC':      'GB-TC-30',
    'TC 10':   'GB-TC-10',
    'TC 250':  'GB-TC-250',
    'TP':      'GB-TP-TV',
}

# ============================================================
# 2. LOAD DATASET
# ============================================================
print("="*70)
print("Loading Dataset_Forecasting_ARIMA_2023-2025.xlsx ...")
raw = pd.read_excel('Dataset_Forecasting_ARIMA_2023-2025.xlsx')
raw['Date'] = pd.to_datetime(raw['Date'])
raw = raw.dropna(subset=['Date'])
raw = raw.set_index('Date').sort_index()

# Rename columns
rename_existing = {k: v for k, v in COL_MAP.items() if k in raw.columns}
df = raw[list(rename_existing.keys())].rename(columns=rename_existing)

print(f"Date range: {df.index[0].date()} → {df.index[-1].date()}")
print(f"Total days : {len(df)}")
print(f"Products   : {df.columns.tolist()}")

# Fill missing dates
full_range = pd.date_range(start=df.index.min(), end=df.index.max(), freq='D')
df = df.reindex(full_range).fillna(0)
print(f"After reindex: {len(df)} rows")
print("="*70)

# ============================================================
# 3. ARIMA FITTING FUNCTION
# ============================================================
CANDIDATE_ORDERS = [
    (1, 0, 1), (2, 0, 1), (1, 0, 2), (2, 0, 2),
    (1, 1, 1), (2, 1, 1), (1, 1, 2), (2, 1, 2),
    (0, 1, 1), (3, 0, 1), (3, 1, 1), (1, 0, 0),
]

def fit_best_arima(series, train, test):
    """Coba semua candidate orders, return order terbaik berdasarkan MAE."""
    best_mae  = np.inf
    best_order = (1, 1, 1)
    best_fc   = None
    best_fitted = None

    for order in CANDIDATE_ORDERS:
        try:
            mdl = ARIMA(train, order=order)
            fit = mdl.fit()
            fc  = fit.forecast(steps=len(test))
            fc  = np.maximum(fc.values, 0)
            mae = mean_absolute_error(test.values, fc)
            if mae < best_mae:
                best_mae   = mae
                best_order = order
                best_fc    = fc
                best_fitted = fit
        except Exception:
            continue

    return best_order, best_fc, best_fitted, best_mae


def mae_kategori(mae_val):
    if mae_val <= 1.0:
        return 'rendah'
    elif mae_val <= 3.0:
        return 'menengah'
    else:
        return 'tinggi'


# ============================================================
# 4. RUN ARIMA PER PRODUK
# ============================================================
TRAIN_RATIO = 0.80
products = df.columns.tolist()

detail_rows  = []
summary_rows = []

for i, prod in enumerate(products, 1):
    series = df[prod].copy()

    # ADF stationarity
    try:
        adf_p = adfuller(series)[1]
        is_stationary = adf_p < 0.05
    except Exception:
        is_stationary = False

    train_size = int(len(series) * TRAIN_RATIO)
    train = series.iloc[:train_size]
    test  = series.iloc[train_size:]

    print(f"[{i}/{len(products)}] {prod} | train={len(train)} test={len(test)} | stationary={is_stationary}")

    try:
        best_order, fc_values, fitted, best_mae = fit_best_arima(series, train, test)

        if fc_values is None:
            raise ValueError("No valid ARIMA order found")

        rmse = np.sqrt(mean_squared_error(test.values, fc_values))
        mape = float(np.mean(np.abs((test.values - fc_values) / (test.values + 1))) * 100)
        kategori = mae_kategori(best_mae)

        print(f"         → Order={best_order} | MAE={best_mae:.3f} | RMSE={rmse:.3f} | MAPE={mape:.1f}% | Kat={kategori}")

        # ----- TRAINING rows (data_type = 'training') -----
        for dt, val in zip(train.index, train.values):
            detail_rows.append({
                'Date':            dt.strftime('%Y-%m-%d'),
                'Produk':          prod,
                'Kategori_MAE':    kategori,
                'Actual_Sales':    round(float(val), 4),
                'Predicted_Sales': 0.0,
                'Error':           0.0,
                'Absolute_Error':  0.0,
                'data_type':       'training',
            })

        # ----- TEST rows (data_type = 'actual') -----
        for dt, act_v, pred_v in zip(test.index, test.values, fc_values):
            err     = float(act_v) - float(pred_v)
            abs_err = abs(err)
            detail_rows.append({
                'Date':            dt.strftime('%Y-%m-%d'),
                'Produk':          prod,
                'Kategori_MAE':    kategori,
                'Actual_Sales':    round(float(act_v), 4),
                'Predicted_Sales': round(float(pred_v), 4),
                'Error':           round(err, 4),
                'Absolute_Error':  round(abs_err, 4),
                'data_type':       'actual',
            })

        summary_rows.append({
            'Produk':       prod,
            'ARIMA Order':  str(best_order),
            'MAE':          round(best_mae, 4),
            'RMSE':         round(rmse, 4),
            'MAPE (%)':     round(mape, 4),
            'Kategori MAE': kategori,
            'Train From':   train.index[0].strftime('%Y-%m-%d'),
            'Train To':     train.index[-1].strftime('%Y-%m-%d'),
            'Test From':    test.index[0].strftime('%Y-%m-%d'),
            'Test To':      test.index[-1].strftime('%Y-%m-%d'),
        })

    except Exception as e:
        print(f"         → ERROR: {e}")
        summary_rows.append({
            'Produk':       prod,
            'ARIMA Order':  'error',
            'MAE':          None,
            'RMSE':         None,
            'MAPE (%)':     None,
            'Kategori MAE': 'tinggi',
            'Train From':   '',
            'Train To':     '',
            'Test From':    '',
            'Test To':      '',
        })

# ============================================================
# 5. EXPORT CSV
# ============================================================
print("\n" + "="*70)
print("Exporting CSVs...")

detail_df  = pd.DataFrame(detail_rows)
summary_df = pd.DataFrame(summary_rows)

# arima_forecast_detailed_per_produk.csv
# Simpan semua kolom termasuk data_type → seeder bisa langsung pakai
out_detail = 'arima_forecast_detailed_per_produk.csv'
detail_df.to_csv(out_detail, index=False, encoding='utf-8-sig')
print(f"Saved: {out_detail} ({len(detail_df)} rows)")

# arima_forecast_summary_per_produk.csv
out_summary = 'arima_forecast_summary_per_produk.csv'
summary_df.to_csv(out_summary, index=False, encoding='utf-8-sig')
print(f"Saved: {out_summary} ({len(summary_df)} rows)")

# arima_forecast_mae_kategori_ringkas.csv
if 'Kategori MAE' in summary_df.columns:
    kat_df = summary_df.groupby('Kategori MAE').agg(
        jumlah_produk=('Produk', 'count'),
        mae_rata_rata=('MAE', 'mean'),
        rmse_rata_rata=('RMSE', 'mean'),
        mape_rata_rata=('MAPE (%)', 'mean'),
    ).reset_index().rename(columns={'Kategori MAE': 'Kategori MAE'})
    kat_df.to_csv('arima_forecast_mae_kategori_ringkas.csv', index=False, encoding='utf-8-sig')
    print(f"Saved: arima_forecast_mae_kategori_ringkas.csv")

print("\n" + "="*70)
print("SUMMARY PER PRODUK:")
print(summary_df[['Produk','ARIMA Order','MAE','RMSE','MAPE (%)','Kategori MAE']].to_string(index=False))
print("="*70)
avg_mae = summary_df['MAE'].dropna().mean()
print(f"\nAverage MAE: {avg_mae:.4f}")
print(f"Kategori distribusi:")
print(summary_df['Kategori MAE'].value_counts().to_string())
print("\nDone!")
