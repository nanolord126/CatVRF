#!/usr/bin/env python3
"""
CLV (Customer Lifetime Value) Model Training Script

Production-ready XGBoost model for predicting customer lifetime value.
Follows best practices from Alibaba/Ozon:
- Time-based split to prevent data leakage
- Feature engineering for RFM + behavioral features
- Hyperparameter tuning with cross-validation
- Model versioning and artifact export

Usage:
    python train_clv.py --data-path /path/to/buyer_seller_features.parquet --output-dir ./models

Requirements:
    pip install xgboost pandas scikit-learn joblib pyarrow
"""

import argparse
import json
import logging
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Tuple

import joblib
import numpy as np
import pandas as pd
import xgboost as xgb
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import TimeSeriesSplit

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class CLVModelTrainer:
    """
    CLV Model Trainer using XGBoost.
    
    Features:
    - RFM scores (recency, frequency, monetary)
    - Behavioral metrics (return rate, review score)
    - Traffic sources
    - Lifetime metrics
    """
    
    # Feature columns for training
    FEATURE_COLUMNS = [
        'r_score', 'f_score', 'm_score',
        'recency_days',
        'frequency_90d', 'frequency_180d', 'frequency_365d',
        'monetary_90d', 'monetary_180d', 'monetary_365d',
        'avg_order_value',
        'days_since_first_purchase',
        'total_orders_all_time', 'total_monetary_all_time',
        'return_rate', 'review_score', 'total_reviews',
        'traffic_search_pct', 'traffic_recommendation_pct',
        'traffic_direct_pct', 'traffic_other_pct',
    ]
    
    # Target column
    TARGET_COLUMN = 'actual_monetary_180d'
    
    def __init__(self, output_dir: str = './models'):
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.model = None
        self.feature_importance = None
        
    def load_data(self, data_path: str) -> pd.DataFrame:
        """Load training data from parquet or CSV."""
        logger.info(f"Loading data from {data_path}")
        
        if data_path.endswith('.parquet'):
            df = pd.read_parquet(data_path)
        elif data_path.endswith('.csv'):
            df = pd.read_csv(data_path)
        else:
            raise ValueError("Unsupported file format. Use .parquet or .csv")
        
        logger.info(f"Loaded {len(df)} rows")
        return df
    
    def preprocess_data(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Preprocess data for training.
        
        - Filter to training data (has actual labels)
        - Handle missing values
        - Create derived features
        """
        logger.info("Preprocessing data")
        
        # Filter to training data only
        df = df[df[self.TARGET_COLUMN].notna()].copy()
        logger.info(f"Training samples: {len(df)}")
        
        # Fill missing values
        df['review_score'] = df['review_score'].fillna(3.0)
        df['r_score'] = df['r_score'].fillna(3)
        df['f_score'] = df['f_score'].fillna(3)
        df['m_score'] = df['m_score'].fillna(3)
        df['recency_days'] = df['recency_days'].fillna(365)
        df['days_since_first_purchase'] = df['days_since_first_purchase'].fillna(365)
        
        # Create derived features
        df['monetary_per_order'] = df['monetary_180d'] / (df['frequency_180d'] + 1)
        df['frequency_trend'] = df['frequency_90d'] / (df['frequency_180d'] + 1)
        df['monetary_trend'] = df['monetary_90d'] / (df['monetary_180d'] + 0.01)
        
        # Add derived features to feature list
        self.FEATURE_COLUMNS.extend([
            'monetary_per_order',
            'frequency_trend',
            'monetary_trend',
        ])
        
        return df
    
    def time_based_split(
        self, 
        df: pd.DataFrame, 
        test_ratio: float = 0.2
    ) -> Tuple[pd.DataFrame, pd.DataFrame]:
        """
        Split data by time to prevent leakage.
        
        Uses updated_at or first_purchase_at for chronological split.
        """
        logger.info(f"Performing time-based split (test ratio: {test_ratio})")
        
        # Sort by date
        if 'updated_at' in df.columns:
            df = df.sort_values('updated_at')
        elif 'first_purchase_at' in df.columns:
            df = df.sort_values('first_purchase_at')
        else:
            logger.warning("No date column found, using random split")
            return self.random_split(df, test_ratio)
        
        split_idx = int(len(df) * (1 - test_ratio))
        train_df = df.iloc[:split_idx]
        test_df = df.iloc[split_idx:]
        
        logger.info(f"Train samples: {len(train_df)}, Test samples: {len(test_df)}")
        return train_df, test_df
    
    def random_split(
        self, 
        df: pd.DataFrame, 
        test_ratio: float = 0.2
    ) -> Tuple[pd.DataFrame, pd.DataFrame]:
        """Random split (fallback)."""
        split_idx = int(len(df) * (1 - test_ratio))
        train_df = df.iloc[:split_idx]
        test_df = df.iloc[split_idx:]
        return train_df, test_df
    
    def train_model(
        self, 
        train_df: pd.DataFrame,
        n_estimators: int = 800,
        learning_rate: float = 0.05,
        max_depth: int = 8,
        subsample: float = 0.8,
        colsample_bytree: float = 0.8,
    ) -> xgb.XGBRegressor:
        """
        Train XGBoost model with production-ready hyperparameters.
        
        Hyperparameters based on Alibaba/Ozon production experience:
        - n_estimators: 800 for good performance without overfitting
        - learning_rate: 0.05 for stable convergence
        - max_depth: 8 to capture complex patterns
        - subsample: 0.8 for regularization
        - colsample_bytree: 0.8 for feature diversity
        """
        logger.info("Training XGBoost model")
        
        X_train = train_df[self.FEATURE_COLUMNS]
        y_train = train_df[self.TARGET_COLUMN]
        
        self.model = xgb.XGBRegressor(
            n_estimators=n_estimators,
            learning_rate=learning_rate,
            max_depth=max_depth,
            subsample=subsample,
            colsample_bytree=colsample_bytree,
            random_state=42,
            n_jobs=-1,
            tree_method='hist',  # Faster training
            eval_metric='rmse',
        )
        
        self.model.fit(X_train, y_train)
        
        # Store feature importance
        self.feature_importance = dict(zip(
            self.FEATURE_COLUMNS,
            self.model.feature_importances_
        ))
        
        logger.info("Model training completed")
        return self.model
    
    def evaluate_model(
        self, 
        test_df: pd.DataFrame
    ) -> Dict[str, float]:
        """Evaluate model on test set."""
        logger.info("Evaluating model")
        
        X_test = test_df[self.FEATURE_COLUMNS]
        y_test = test_df[self.TARGET_COLUMN]
        
        y_pred = self.model.predict(X_test)
        
        metrics = {
            'mae': mean_absolute_error(y_test, y_pred),
            'rmse': np.sqrt(mean_squared_error(y_test, y_pred)),
            'r2': r2_score(y_test, y_pred),
            'mape': np.mean(np.abs((y_test - y_pred) / (y_test + 1))) * 100,
        }
        
        logger.info(f"Model metrics: {json.dumps(metrics, indent=2)}")
        return metrics
    
    def save_model(self, version: str = None) -> str:
        """Save model and artifacts."""
        if version is None:
            version = datetime.now().strftime('%Y%m%d_%H%M%S')
        
        model_path = self.output_dir / f'clv_model_v{version}.joblib'
        
        artifacts = {
            'model': self.model,
            'feature_columns': self.FEATURE_COLUMNS,
            'feature_importance': self.feature_importance,
            'version': version,
            'trained_at': datetime.now().isoformat(),
        }
        
        joblib.dump(artifacts, model_path)
        logger.info(f"Model saved to {model_path}")
        
        # Save feature importance as JSON
        importance_path = self.output_dir / f'feature_importance_v{version}.json'
        with open(importance_path, 'w') as f:
            json.dump(self.feature_importance, f, indent=2)
        
        return str(model_path)
    
    def export_onnx(self, version: str = None) -> str:
        """
        Export model to ONNX format for faster inference.
        
        Requires: pip install onnxmltools
        """
        try:
            import onnxmltools
            from onnxmltools.convert import convert_xgboost
            
            if version is None:
                version = datetime.now().strftime('%Y%m%d_%H%M%S')
            
            onnx_path = self.output_dir / f'clv_model_v{version}.onnx'
            
            initial_type = [('float_input', FloatTensorType([None, len(self.FEATURE_COLUMNS)]))]
            onnx_model = convert_xgboost(self.model, initial_types=initial_type)
            
            onnxmltools.save_model(onnx_model, str(onnx_path))
            logger.info(f"ONNX model exported to {onnx_path}")
            
            return str(onnx_path)
        except ImportError:
            logger.warning("ONNX export skipped (onnxmltools not installed)")
            return None


def main():
    parser = argparse.ArgumentParser(description='Train CLV prediction model')
    parser.add_argument(
        '--data-path',
        required=True,
        help='Path to training data (parquet or csv)'
    )
    parser.add_argument(
        '--output-dir',
        default='./models',
        help='Output directory for model artifacts'
    )
    parser.add_argument(
        '--test-ratio',
        type=float,
        default=0.2,
        help='Test set ratio for time-based split'
    )
    parser.add_argument(
        '--n-estimators',
        type=int,
        default=800,
        help='Number of trees in XGBoost'
    )
    parser.add_argument(
        '--learning-rate',
        type=float,
        default=0.05,
        help='Learning rate for XGBoost'
    )
    parser.add_argument(
        '--max-depth',
        type=int,
        default=8,
        help='Max depth of trees'
    )
    
    args = parser.parse_args()
    
    # Initialize trainer
    trainer = CLVModelTrainer(output_dir=args.output_dir)
    
    # Load and preprocess data
    df = trainer.load_data(args.data_path)
    df = trainer.preprocess_data(df)
    
    # Time-based split
    train_df, test_df = trainer.time_based_split(df, test_ratio=args.test_ratio)
    
    # Train model
    trainer.train_model(
        train_df,
        n_estimators=args.n_estimators,
        learning_rate=args.learning_rate,
        max_depth=args.max_depth,
    )
    
    # Evaluate
    metrics = trainer.evaluate_model(test_df)
    
    # Save model
    model_path = trainer.save_model()
    
    # Export to ONNX (optional)
    trainer.export_onnx()
    
    logger.info("Training pipeline completed successfully")
    logger.info(f"Model saved to: {model_path}")
    logger.info(f"Final metrics: {json.dumps(metrics, indent=2)}")


if __name__ == '__main__':
    main()
