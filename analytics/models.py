from __future__ import annotations

from dataclasses import dataclass
from datetime import date
from typing import Any, Callable

import numpy as np
import pandas as pd
from sklearn.linear_model import Ridge
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler


def predict_general_disease_risk(features: pd.DataFrame) -> pd.Series:
    """
    Mock for a general disease risk model.

    In production this can be replaced by a trained XGBoost/LightGBM model,
    keeping the same function signature for the batch pipeline.
    """
    risk = features["days_in_shelter"] * 0.005
    risk += features["current_block_occupancy_rate"] * 0.2
    risk += features["age_category"].isin(["puppy", "senior"]).astype(float) * 0.15
    risk += (features["health_on_arrival"] == "poor").astype(float) * 0.2
    return risk.clip(0, 1.0)


def predict_severe_zoonotic_risk(features: pd.DataFrame) -> pd.Series:
    """Mock for a severe zoonotic transmission risk model."""
    risk = features["exposed_to_sick_handler"].astype(bool).astype(float) * 0.6
    risk += features["proximity_to_confirmed_case"].astype(bool).astype(float) * 0.4
    return risk.clip(0, 1.0)


def apply_scoring_models(
    animal_df: pd.DataFrame,
    exposure_df: pd.DataFrame,
    calc_general_risk: Callable[[pd.DataFrame], pd.Series],
    calc_severe_risk: Callable[[pd.DataFrame], pd.Series],
) -> pd.DataFrame:
    """
    Merges animal/exposure data and applies the risk scoring functions.
    """
    combined_df = pd.merge(animal_df, exposure_df, on="animal_id", how="left").fillna(0)

    scored_df = combined_df.copy()
    scored_df["general_risk_score"] = calc_general_risk(scored_df)
    scored_df["severe_risk_score"] = calc_severe_risk(scored_df)
    scored_df["action_required"] = (
        (scored_df["severe_risk_score"] > 0.75)
        | (scored_df["general_risk_score"] > 0.85)
    )
    scored_df["prediction_date"] = date.today().isoformat()

    return scored_df[
        [
            "animal_id",
            "prediction_date",
            "general_risk_score",
            "severe_risk_score",
            "action_required",
        ]
    ]


DEFAULT_INVENTORY_FEATURES = (
    "dogs_small",
    "dogs_medium",
    "dogs_large",
    "cats",
    "equine_vaccine_visits",
    "puppies",
    "adult_new_animals",
    "resident_adults",
    "quarantine_animals",
    "cleaning_runs",
    "outbreak_alert",
    "weekday_sin",
    "weekday_cos",
    "month_sin",
    "month_cos",
)


@dataclass
class InventoryConsumptionModel:
    """
    Daily inventory consumption forecaster.

    It predicts consumption for one stock item at a time, for example feed kg,
    vaccine doses, or bleach liters. The estimator is a scikit-learn pipeline
    with StandardScaler and Ridge regression.
    """

    alpha: float = 2.0
    feature_columns: tuple[str, ...] = DEFAULT_INVENTORY_FEATURES
    estimator: Any | None = None
    backend: str = "not_fitted"
    residual_std: float = 0.0
    training_rows: int = 0
    target_column: str = ""
    mae_last_window: float | None = None

    def fit(
        self,
        training_df: pd.DataFrame,
        target_column: str,
        validation_window: int = 30,
    ) -> "InventoryConsumptionModel":
        if target_column not in training_df.columns:
            raise ValueError(f"Missing target column: {target_column}")
        if training_df.empty:
            raise ValueError("InventoryConsumptionModel needs at least one training row.")

        x = self._feature_matrix(training_df)
        y = _numeric_series(training_df, target_column, default=0.0).to_numpy(dtype=float)

        self.estimator, self.backend = self._build_estimator()
        self.estimator.fit(x, y)
        self.training_rows = len(training_df)
        self.target_column = target_column

        fitted = self._predict_raw(x)
        residuals = y - fitted
        self.residual_std = float(np.std(residuals)) if len(residuals) else 0.0
        self.mae_last_window = _last_window_mae(y, fitted, validation_window)

        return self

    def predict(self, feature_df: pd.DataFrame) -> pd.Series:
        if self.estimator is None:
            raise RuntimeError("InventoryConsumptionModel must be fitted before prediction.")

        predictions = self._predict_raw(self._feature_matrix(feature_df))
        return pd.Series(
            np.clip(predictions, 0, None),
            index=feature_df.index,
            name=f"predicted_{self.target_column}",
        )

    def predict_interval(
        self,
        feature_df: pd.DataFrame,
        z_value: float = 1.28,
    ) -> pd.DataFrame:
        predictions = self.predict(feature_df)
        margin = max(2.0, self.residual_std * z_value)
        return pd.DataFrame(
            {
                "prediction": predictions,
                "lower_bound": np.floor(np.clip(predictions - margin, 0, None)).astype(int),
                "upper_bound": np.ceil(predictions + margin).astype(int),
            },
            index=feature_df.index,
        )

    def prepare_features(self, source_df: pd.DataFrame) -> pd.DataFrame:
        prepared = source_df.copy()
        self._add_calendar_features(prepared)

        for column in self.feature_columns:
            prepared[column] = _numeric_series(prepared, column, default=0.0)

        return prepared

    def _add_calendar_features(self, prepared: pd.DataFrame) -> None:
        if "date" in prepared.columns:
            dates = pd.to_datetime(prepared["date"], errors="coerce")
            prepared["weekday"] = dates.dt.weekday.fillna(0)
            prepared["month"] = dates.dt.month.fillna(1)
        else:
            prepared["weekday"] = _numeric_series(prepared, "weekday", default=0.0)
            prepared["month"] = _numeric_series(prepared, "month", default=1.0)

        prepared["weekday_sin"] = np.sin(2 * np.pi * prepared["weekday"] / 7)
        prepared["weekday_cos"] = np.cos(2 * np.pi * prepared["weekday"] / 7)
        prepared["month_sin"] = np.sin(2 * np.pi * prepared["month"] / 12)
        prepared["month_cos"] = np.cos(2 * np.pi * prepared["month"] / 12)

    def _feature_matrix(self, source_df: pd.DataFrame) -> np.ndarray:
        prepared = self.prepare_features(source_df)
        return prepared.loc[:, self.feature_columns].to_numpy(dtype=float)

    def _build_estimator(self) -> tuple[Any, str]:
        return make_pipeline(StandardScaler(), Ridge(alpha=self.alpha)), "sklearn_ridge"

    def _predict_raw(self, x: np.ndarray) -> np.ndarray:
        if self.estimator is None:
            raise RuntimeError("InventoryConsumptionModel must be fitted before prediction.")

        return np.asarray(self.estimator.predict(x), dtype=float).reshape(-1)


def _last_window_mae(
    actual: np.ndarray,
    predicted: np.ndarray,
    validation_window: int,
) -> float | None:
    if len(actual) < validation_window:
        return None

    return float(np.mean(np.abs(actual[-validation_window:] - predicted[-validation_window:])))


def _numeric_series(
    source_df: pd.DataFrame,
    column: str,
    default: float,
) -> pd.Series:
    if column in source_df.columns:
        values = source_df[column]
    else:
        values = pd.Series(default, index=source_df.index)

    return pd.to_numeric(values, errors="coerce").fillna(default)
