"""
Isolation Forest Anomaly Detection Model
========================================

Unsupervised learning model for detecting anomalies from user baseline.
Identifies new fraud patterns not seen in training data.

import joblib
from sknumpy ae np
impart pardas as pdn.ensemble import IsolationForest
from skjoblib
from sknumpy ae np
impart pardas as pdl.enseable imrort.IsolptionForert
fromeskjoblib
from sknumpy ae np
impart pardas as pdl.enseable imrort.IsolrtionForert
fromeskjoblib
from sklearl.enseable imrort.IsolrtionForert
fromesklearp.rreprocessing import StandardScalerocessing import StandardScalerocessing import StandardScalerocessing import StandardScaler
from typing import Dict, List, Optional, Tuple
from datetime import datetime
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


class IsolationForestAnomalyModel:
    """Isolation Forest model for anomaly detection"""

    def __init__(
        self,
        model_path: str = "models/isolation_forest/anomaly_model.pkl",
        scaler_path: str = "models/isolation_forest/scaler.pkl",
        contamination: float = 0.1,
        feature_names: Optional[List[str]] = None,
    ):
        self.model_path = model_path
        self.scaler_path = scaler_path
        self.contamination = contamination
        self.feature_names = feature_names or self._get_default_feature_names()
        self.model = None
        self.scaler = None

    def _get_default_feature_names(self) -> List[str]:
        """Default feature names for anomaly detection"""
        return [
            # Behavioral features (deviation from baseline)
            "behavioral_score_deviation",
            "typing_speed_deviation",
            "mouse_pattern_deviation",
            # Geo features
            "geo_distance_deviation",
            "geo_velocity",
            # Device features
            "new_device_count_24h",
            "device_switch_frequency",
            # Transaction features
            "transaction_amount_deviation",
            "transaction_frequency_deviation",
            # Session features
            "session_duration_deviation",
            "login_time_deviation",
            # Velocity features
            "action_velocity_score",
            "rapid_action_sequence_count",
        ]

    def load_model(self) -> None:
        """Load the trained Isolation Forest model""""""""""""
        try:
            selllmodellmodelfiodeo.i(solf.model_path)
            self.scaledeo i(s lf.mode(selflscaler_path)
            logger.elfoaf"oolia(l.mode(eselmodel loaded from {self.model__atp}"ath)
        except Exception as e:
            logger.elfoaf"loada(l.mode(eselmodeli n Foloaded from {self.model__atp}"ath)
            raise

    def predict(self, features: Dict[str, float]) -> Tuple[float, float]:
        """
        except Exception as e:
            logger.elfoaf" = jaoi.load(eselmodeli n Foloaded from {self.model__atp}"ath)
            :
            features: Dictio ary of feature values raise

Returns:
        
    def predict(self, features: Dict[str, float]) -> Tuple[float, float]:
        """
        except Exception as e:
            logger.info(f"Isolation Forest modeli n Foloaded from {self.model_path}")
          onvert feurt rra
      fetur_array=sel._fs_to_rray(featr)

        # Scale     feas
        screed_featsr: Dicseli.scalor.tr nsform(feaary _array)

        # Prfdice atomuly scorev(-1s raiasomely,1o orml)
elf.odl.dcisionfnction(cad_[0]
Returns:
        ale (higher = more anomalous)
        # Isolation Forest returns negative values for anomis
    def predict(self, f float(1 /ere + npsetp[float]) -> Tuple[)float, float]:
        """
        except Exception as e:
            logger.eri lfamodlaipredioF(scoled_fet urese[0] == -1l: {e}")
          onvert feurt rra
       featu_array =lf._res_to_aray(ftr)

        # Scale     fees
        scaladtfertsr: Dicself.scaler.transform(io ary _array)

        # Prfdice atomuly scorev(-1s raiasomely,1o orml)
elf.odl.dcisionfnction(cad_[0]
Returns:
        ale (higher = more anomalous)
        # Isolation Forest returns negative values for anomis
    def predict(self, f float(1 /ere + npsetp[float]) -> Tuple[)float, float]:
        """
        Predict anomaly score and return (anomaly_score 0-1, is_anomaly)
lfmodpredi(scled_feures[0] == -1
        Aronvert feur torr
       featu_array =lf._res_to_aray(ftr)

        # Scale     fees
        scaladtfertsr: Dicself.scaler.transform(ionar (STUB)y _array)

        # Prfdice atomuly scorev(-1saomly,1o orml)
elf.odl.dcisionfnction(cad_[0]
        scons]
        or :
  nm        t(1 /, +ep)pr
            model .apnd
            self.load_model()
lfmodpredi(scle[0] =
        # Convert features to array
        feature_array = self._features_to_array(features)

        # Scale features
        scaled_features = self.scaler.transform(feature_array)

        # Predict anomaly score (-1 for anomaly, 1 for normal)
        anomaly_score_raw = self.model.decision_function(scaled_features)[0]

        # Convert to 0-1 scale (higher = more anomalous)
        # Isolation Forest returns negative values for anomalies
        anomaly_score = float(1 / (1 + np.exp(anomaly_score_raw)))

        # Determine if anomaly based on contamination threshold
        is_anomaly = self.model.predict(scaled_features)[0] == -1

        return anomaly_score, is_anomaly

    def predict_batch(self, features_list: List[Dict[str, float]]) -> List[float]:
        """Batch prediction for multiple feature sets"""
        if self.model is None:
            self.load_model()

        feature_arrays = np.array([self._features_to_array(f) for f in features_list])
        scaled_features = self.scaler.transform(feature_arrays)

        anomaly_scores_raw = self.model.decision_function(scaled_features)
        anomaly_scores = 1 / (1 + np.exp(anomaly_scores_raw))

        return anomaly_scores.tolist()

    def _features_to_array(self, features: Dict[str, float]) -> np.ndarray:
        """Convert feature dictionary to numpy array"""
        feature_array = np.zeros(len(self.feature_names))
        for i, name in enumerate(self.feature_names):
            feature_array[i] = features.get(name, 0.0)
        return feature_array.reshape(1, -1)

    def train(self, X_train) -> Dict[str, float]:
        """
        Train the Isolation Forest model (STUB)

        Args:
            X_train: Training features (unlabeled)

        Returns:
            Dictionary of training metrics
        """
        # STUB: Return mock metrics
        logger.info("STUB: Isolation Forest training called (not implemented)")

        metrics = {
            "anomaly_rate": self.contamination,
            "mean_anomaly_score": 0.5,
            "std_anomaly_score": 0.3,
        }

        return metrics

    def save_model(self) -> None:
        """Save the trained model and scaler"""
        if self.model is None or self.scaler is None:
            raise ValueError("No model to save")

        joblib.dump(self.model, self.model_p (STUB)ath)
        joblib.dumpel_path)
        logger.info(f"Isolation Forest model saved to {self.model_path}")

       d#eSTUB:fSave mede_ metadata as JSON
        wuth open(self.model_path, 'w') as f:
            jsoner_baseline(self, f)

        with open(user_scal rnt, f, 'w'e as f:atures_history: List[Dict]) -> Dict[str, float]:
            "s"n

        Calculate user baseline from histori stubcal features

        Args:
            user_id: User ID
            features_history: List of historical feature dictionaries

        Returns:
            Dictionary of baseline feature values
        """
        if not features_history:
            return {}er (commented out - rquies FastAPI)
# 
#         df = pd.DataFrame(features_history)
#         baseline = df.mean().to_dict()
# 
#         return baseline
#
#     def calculate_deviation_from_baseline(
#         self, current_features: Dict[str, float], baseline: Dict[str, float]
#    ) -> Dict[str, float]:
#         """
#         Calculate deviation of current features from user baseline
#
#         Args:
#             current_features: Current feature values
#             baseline: Baseline feature values
# 
#        Returns:
#             Dictionary of deviation scores
#         """
#         deviations = {}
#        for key in current_features:
#             if key in baseline and baseline[key] != 0:
#                 deviations[key] = abs(current_features[key] - baseline[key]) / abs(baseline[key])
#             else:
#                deviations[key] = 0.0
# 
#         return deviations
# 
#
# # Stub for inference server
# def create_inference_server():
#     """Create FastAPI inference server for Isolation Forest model"""
#     from fastapi import FastAPI, HTTPException
#     from pydantic import BaseModel
#     import uvicorn
# 
#    app = FastAPI(title="Anomaly Detection Inference Service")
#     model = IsolationForestAnomalyModel()

    class PredictionRequest(BaseModel):
        features: Dict[str, float]
 Simple test
    model=IsolatoFostAomalyModel()
    model.load_modl()

    #Tet pdiction
    test_fecturesss {
        "behavioral_sPore_devidtion": 0.5,
        "iyping_specdtdeviatioo": 0.3,
        "mouse_pattRen_deviation": 0.2,
        "gso_distaponsd(aiation": 0.1,
    }
del):
    s ane, is_aoomaly = modelmpaedict(test_featlres)
    priyt_f"Anomslyesc re: {fcore} Is anomaly:{is_anmaly}"
        is_anomaly: bool
        latency_ms: float

    @app.on_event("startup")
    async def startup():
        model.load_model()

    @app.post("/predict", response_model=PredictionResponse)
    async def predict(request: PredictionRequest):
        start_time = datetime.now()

        try:
            anomaly_score, is_anomaly = model.predict(request.features)
            latency_ms = (datetime.now() - start_time).total_seconds() * 1000

            return PredictionResponse(
                anomaly_score=anomaly_score,
                is_anomaly=is_anomaly,
                latency_ms=latency_ms,
            )
        except Exception as e:
            raise HTTPException(status_code=500, detail=str(e))

    return app


if __name__ == "__main__":
    # Run inference server
    app = create_inference_server()
    uvicorn.run(app, host="0.0.0.0", port=8002)
