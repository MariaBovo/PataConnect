from __future__ import annotations

import argparse
import json
import sys
from dataclasses import dataclass
from datetime import date
from pathlib import Path
from typing import Any

import numpy as np
import pandas as pd


PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from analytics.models import InventoryConsumptionModel
from system.ipc import IPC
from analytics.synthetic_data import (
    DATA_DIR,
    DEFAULT_REPLENISHMENT_DAYS,
    DEFAULT_STOCK_LEVELS,
    VACCINE_PROTOCOL,
    generate_default_datasets,
)


@dataclass(frozen=True)
class InventoryItem:
    key: str
    label: str
    unit: str
    target_column: str


INVENTORY_ITEMS = (
    InventoryItem("feed_kg", "Racao", "kg", "feed_kg_consumed"),
    InventoryItem("vaccine_doses", "Vacinas", "doses", "vaccine_doses_used"),
    InventoryItem("bleach_liters", "Agua sanitaria", "litros", "bleach_liters_used"),
)


def load_or_generate_datasets(
    history_path: Path | None = None,
    future_path: Path | None = None,
    history_days: int = 420,
    forecast_days: int = 30,
    seed: int = 42,
) -> tuple[pd.DataFrame, pd.DataFrame]:
    history_df = _read_csv_if_exists(history_path)
    future_df = _read_csv_if_exists(future_path)

    if history_df is None or future_df is None:
        generated_history, generated_future = generate_default_datasets(
            history_days=history_days,
            forecast_days=forecast_days,
            seed=seed,
        )
        history_df = history_df if history_df is not None else generated_history
        future_df = future_df if future_df is not None else generated_future

    return history_df.copy(), future_df.head(forecast_days).copy()


def build_forecast_payload(
    history_df: pd.DataFrame,
    future_df: pd.DataFrame,
    stock_levels: dict[str, float] | None = None,
    replenishment_days: dict[str, int] | None = None,
    generated_at: date | None = None,
) -> dict[str, Any]:
    stock = stock_levels or DEFAULT_STOCK_LEVELS
    lead_time = replenishment_days or DEFAULT_REPLENISHMENT_DAYS

    stock_forecast: list[dict[str, Any]] = []
    daily_forecast: list[dict[str, Any]] = []

    for item in INVENTORY_ITEMS:
        forecast, daily_rows = _forecast_item(
            item=item,
            history_df=history_df,
            future_df=future_df,
            current_stock=float(stock[item.key]),
            replenishment_days=int(lead_time[item.key]),
        )
        stock_forecast.append(forecast)
        daily_forecast.extend(daily_rows)

    stock_forecast.sort(key=lambda item: item["days_remaining"])
    daily_forecast.sort(key=lambda row: (row["date"], row["item"]))

    return {
        "generated_at": (generated_at or date.today()).isoformat(),
        "horizon_days": int(len(future_df)),
        "model": _model_metadata(training_rows=len(history_df)),
        "summary": _summary(stock_forecast),
        "stock_forecast": stock_forecast,
        "daily_consumption_forecast": daily_forecast,
        "vaccine_protocol": _serialize_vaccine_protocol(),
    }


def run_pipeline(
    history_path: Path | None = None,
    future_path: Path | None = None,
    history_days: int = 420,
    forecast_days: int = 30,
    seed: int = 42,
    stock_levels: dict[str, float] | None = None,
) -> dict[str, Any]:
    history_df, future_df = load_or_generate_datasets(
        history_path=history_path,
        future_path=future_path,
        history_days=history_days,
        forecast_days=forecast_days,
        seed=seed,
    )
    return build_forecast_payload(history_df, future_df, stock_levels=stock_levels)


def write_stdout(payload: dict[str, Any], pretty: bool = False) -> None:
    indent = 2 if pretty else None
    sys.stdout.write(json.dumps(payload, ensure_ascii=False, indent=indent))
    sys.stdout.write("\n")


def _forecast_item(
    item: InventoryItem,
    history_df: pd.DataFrame,
    future_df: pd.DataFrame,
    current_stock: float,
    replenishment_days: int,
) -> tuple[dict[str, Any], list[dict[str, Any]]]:
    model = InventoryConsumptionModel(alpha=2.0)
    model.fit(history_df, target_column=item.target_column)

    predicted_daily = model.predict(future_df).to_numpy(dtype=float)
    predicted_daily = np.clip(predicted_daily, 0, None)

    days_remaining = _days_remaining(current_stock, predicted_daily)
    reorder_threshold_days = replenishment_days + 3

    forecast = {
        "item": item.key,
        "label": item.label,
        "unit": item.unit,
        "current_stock": round(current_stock, 2),
        "average_daily_consumption": _average_first_days(predicted_daily, days=7),
        "days_remaining": round(days_remaining, 1),
        "replenishment_days": replenishment_days,
        "reorder_threshold_days": reorder_threshold_days,
        "status": _status_for_days(days_remaining, reorder_threshold_days),
        "recommended_reorder_quantity": _recommended_reorder_quantity(
            predicted_daily=predicted_daily,
            current_stock=current_stock,
            lead_time_days=replenishment_days,
        ),
        "projected_stock_30d": _projected_stock(current_stock, predicted_daily),
        "mae_last_30_days": _round_optional(model.mae_last_window),
    }

    return forecast, _daily_rows(item, future_df, predicted_daily)


def _daily_rows(
    item: InventoryItem,
    future_df: pd.DataFrame,
    predicted_daily: np.ndarray,
) -> list[dict[str, Any]]:
    dates = _date_values(future_df)

    return [
        {
            "date": dates[index],
            "item": item.key,
            "label": item.label,
            "unit": item.unit,
            "predicted_consumption": round(float(consumption), 2),
        }
        for index, consumption in enumerate(predicted_daily)
    ]


def _date_values(future_df: pd.DataFrame) -> list[str]:
    if "date" in future_df.columns:
        return future_df["date"].astype(str).tolist()

    return [str(index + 1) for index in range(len(future_df))]


def _read_csv_if_exists(path: Path | None) -> pd.DataFrame | None:
    if path is None or not path.exists():
        return None

    return pd.read_csv(path)


def _model_metadata(training_rows: int) -> dict[str, Any]:
    return {
        "name": "InventoryConsumptionModel",
        "type": "ridge_regression_baseline",
        "backend": "sklearn_ridge",
        "target": "daily_consumption_to_days_remaining",
        "training_rows": int(training_rows),
    }


def _summary(stock_forecast: list[dict[str, Any]]) -> dict[str, Any]:
    return {
        "critical_items": sum(1 for item in stock_forecast if item["status"] == "critical"),
        "warning_items": sum(1 for item in stock_forecast if item["status"] == "warning"),
        "next_purchase_priority": (
            stock_forecast[0]["label"] if stock_forecast else "-"
        ),
    }


def _days_remaining(current_stock: float, predicted_daily: np.ndarray) -> float:
    if len(predicted_daily) == 0 or float(np.sum(predicted_daily)) <= 0:
        return 999.0

    remaining = current_stock
    for index, consumption in enumerate(predicted_daily, start=1):
        remaining -= float(consumption)
        if remaining <= 0:
            return float(index)

    tail_average = float(np.mean(predicted_daily[-7:]))
    if tail_average <= 0:
        return 999.0

    return float(len(predicted_daily) + max(0.0, remaining) / tail_average)


def _recommended_reorder_quantity(
    predicted_daily: np.ndarray,
    current_stock: float,
    lead_time_days: int,
    safety_days: int = 7,
) -> float:
    target_days = max(1, lead_time_days + safety_days)
    window = predicted_daily[: min(len(predicted_daily), 14)]
    target_stock = float(np.mean(window)) * target_days if len(window) else 0.0
    return round(max(0.0, target_stock - current_stock), 2)


def _average_first_days(predicted_daily: np.ndarray, days: int) -> float:
    window = predicted_daily[: min(len(predicted_daily), days)]
    return round(float(np.mean(window)), 2) if len(window) else 0.0


def _projected_stock(current_stock: float, predicted_daily: np.ndarray) -> float:
    return round(max(0.0, current_stock - float(np.sum(predicted_daily))), 2)


def _status_for_days(days_remaining: float, threshold_days: int) -> str:
    if days_remaining <= threshold_days:
        return "critical"
    if days_remaining <= threshold_days + 7:
        return "warning"
    return "ok"


def _round_optional(value: float | None) -> float | None:
    return round(float(value), 2) if value is not None else None


def _serialize_vaccine_protocol() -> list[dict[str, Any]]:
    return [
        {
            "species": "Caninos",
            "vaccines": VACCINE_PROTOCOL["canine"]["diseases"],
            "rule": "Filhotes: 3 doses; adultos novos: 2 doses; adultos residentes: 1 dose anual.",
        },
        {
            "species": "Felinos",
            "vaccines": VACCINE_PROTOCOL["feline"]["diseases"],
            "rule": "Filhotes: 3 doses; adultos novos: 2 doses; adultos residentes: 1 dose anual.",
        },
        {
            "species": "Equinos",
            "vaccines": VACCINE_PROTOCOL["equine"]["diseases"],
            "rule": "Atendimento externo: 1 dose anual; nao conta para lotacao, racao ou limpeza do canil.",
        },
    ]


def main() -> None:
    parser = argparse.ArgumentParser(description="Print stock depletion forecast to stdout.")
    parser.add_argument(
        "--history-path",
        type=Path,
        default=DATA_DIR / "synthetic_inventory_history.csv",
    )
    parser.add_argument(
        "--future-path",
        type=Path,
        default=DATA_DIR / "synthetic_inventory_future.csv",
    )
    parser.add_argument("--history-days", type=int, default=420)
    parser.add_argument("--forecast-days", type=int, default=30)
    parser.add_argument("--seed", type=int, default=42)
    parser.add_argument("--pretty", action="store_true")
    args = parser.parse_args()

    payload = run_pipeline(
        history_path=args.history_path,
        future_path=args.future_path,
        history_days=args.history_days,
        forecast_days=args.forecast_days,
        seed=args.seed,
    )
    write_stdout(payload, pretty=args.pretty)


if __name__ == "__main__":
    main()


@IPC.publish
def generate_forecast(db_path: str = None) -> dict[str, Any]:
    from system.condb import create_connection
    from analytics.dbutils import extract_stock_levels
    import json
    
    if db_path is None:
        db_path = str(PROJECT_ROOT / "storage" / "database.sqlite")
        
    try:
        with create_connection(db_path) as conn:
            stock_levels = extract_stock_levels(conn)
    except Exception as e:
        sys.stderr.write(f"DB Error: {str(e)}\n")
        stock_levels = None
        
    history_path = DATA_DIR / "synthetic_inventory_history.csv"
    future_path = DATA_DIR / "synthetic_inventory_future.csv"
    
    payload = run_pipeline(
        history_path=history_path,
        future_path=future_path,
        stock_levels=stock_levels
    )
    
    artifacts_dir = PROJECT_ROOT / "analytics" / "artifacts"
    artifacts_dir.mkdir(parents=True, exist_ok=True)
    forecast_path = artifacts_dir / "stock_forecast.json"
    
    with open(forecast_path, "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=2)
        
    return {"status": "success", "file": str(forecast_path)}

