from __future__ import annotations

import argparse
import sys
from datetime import date, timedelta
from pathlib import Path

import numpy as np
import pandas as pd


PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))


DATA_DIR = PROJECT_ROOT / "analytics" / "data"

VACCINE_PROTOCOL = {
    "canine": {
        "diseases": [
            "cinomose",
            "hepatite canina (adenovirus tipo 1)",
            "parvovirose canina",
            "coronavirus canino",
            "infeccoes respiratorias por adenovirus tipo 2",
        ],
        "puppy_doses": 3,
        "adult_new_animal_doses": 2,
        "resident_adult_annual_doses": 1,
    },
    "feline": {
        "diseases": ["vacina V5"],
        "puppy_doses": 3,
        "adult_new_animal_doses": 2,
        "resident_adult_annual_doses": 1,
    },
    "equine": {
        "diseases": [
            "influenza equina",
            "rinopneumonite equina (herpesvirus 1 e 4)",
            "encefalomielite equina (leste e oeste)",
            "tetano",
        ],
        "annual_doses": 1,
    },
}

DEFAULT_STOCK_LEVELS = {
    "feed_kg": 420.0,
    "vaccine_doses": 180.0,
    "bleach_liters": 75.0,
}

DEFAULT_REPLENISHMENT_DAYS = {
    "feed_kg": 5,
    "vaccine_doses": 10,
    "bleach_liters": 4,
}


def generate_inventory_consumption_data(
    start_date: date,
    periods: int,
    seed: int = 42,
    include_targets: bool = True,
) -> pd.DataFrame:
    """
    Generates synthetic daily kennel data for stock consumption forecasting.

    The targets are daily feed kg, vaccine doses, and bleach liters consumed.
    The vaccine target uses the protocol supplied by the team as a business
    rule, so the ML model learns consumption around that operational logic.
    """
    rng = np.random.default_rng(seed)
    rows: list[dict[str, float | int | str]] = []

    dogs_small = int(rng.integers(22, 38))
    dogs_medium = int(rng.integers(36, 58))
    dogs_large = int(rng.integers(18, 34))
    cats = int(rng.integers(12, 26))
    outbreak_days_remaining = 0

    for offset in range(periods):
        current_date = start_date + timedelta(days=offset)
        weekday = current_date.weekday()
        month = current_date.month

        if outbreak_days_remaining == 0 and rng.random() < 0.018:
            outbreak_days_remaining = int(rng.integers(4, 10))
        outbreak_alert = int(outbreak_days_remaining > 0)
        outbreak_days_remaining = max(0, outbreak_days_remaining - 1)

        weekday_multiplier = 0.75 if weekday >= 5 else 1.0
        seasonal_entry_multiplier = 1 + 0.14 * np.sin(2 * np.pi * (month - 1) / 12)

        new_dogs = int(rng.poisson(max(3.6 * weekday_multiplier * seasonal_entry_multiplier, 0.4)))
        new_cats = int(rng.poisson(max(1.5 * weekday_multiplier, 0.2)))
        equine_vaccine_visits = int(rng.poisson(0.12))
        if current_date.month in [4, 10] and weekday in [1, 3]:
            equine_vaccine_visits += int(rng.binomial(3, 0.35))

        exits_dogs = int(rng.poisson(2.8 if weekday < 5 else 1.4))
        exits_cats = int(rng.poisson(0.9 if weekday < 5 else 0.4))

        dogs_delta = new_dogs - exits_dogs
        dogs_small = int(np.clip(dogs_small + round(dogs_delta * 0.33), 10, 70))
        dogs_medium = int(np.clip(dogs_medium + round(dogs_delta * 0.45), 18, 90))
        dogs_large = int(np.clip(dogs_large + round(dogs_delta * 0.22), 8, 55))
        cats = int(np.clip(cats + new_cats - exits_cats, 4, 60))

        total_new_animals = new_dogs + new_cats
        puppies = int(rng.binomial(new_dogs + new_cats, 0.32 if month in [10, 11, 12, 1] else 0.22))
        adult_new_animals = max(0, total_new_animals - puppies)
        resident_adults = dogs_small + dogs_medium + dogs_large + cats - puppies
        quarantine_animals = int(
            max(0, rng.normal(8 + total_new_animals * 0.8 + outbreak_alert * 5, 2.0))
        )
        cleaning_runs = int(
            max(1, round((dogs_small + dogs_medium + dogs_large + cats) / 18))
            + outbreak_alert
            + int(quarantine_animals > 12)
        )

        feed_kg = (
            dogs_small * 0.16
            + dogs_medium * 0.28
            + dogs_large * 0.46
            + cats * 0.07
        )
        feed_kg *= rng.normal(1.0, 0.045)

        vaccine_doses = _estimate_daily_vaccine_doses(
            puppies=puppies,
            adult_new_animals=adult_new_animals,
            resident_adults=resident_adults,
            equine_vaccine_visits=equine_vaccine_visits,
            current_date=current_date,
            outbreak_alert=outbreak_alert,
            rng=rng,
        )

        bleach_liters = (
            cleaning_runs * 1.7
            + quarantine_animals * 0.12
            + outbreak_alert * 4.5
            + rng.normal(0, 0.8)
        )

        row = {
            "date": current_date.isoformat(),
            "dogs_small": dogs_small,
            "dogs_medium": dogs_medium,
            "dogs_large": dogs_large,
            "cats": cats,
            "equine_vaccine_visits": equine_vaccine_visits,
            "puppies": puppies,
            "adult_new_animals": adult_new_animals,
            "resident_adults": max(0, resident_adults),
            "quarantine_animals": quarantine_animals,
            "cleaning_runs": cleaning_runs,
            "outbreak_alert": outbreak_alert,
        }

        if include_targets:
            row.update(
                {
                    "feed_kg_consumed": round(max(feed_kg, 0), 2),
                    "vaccine_doses_used": int(max(0, round(vaccine_doses))),
                    "bleach_liters_used": round(max(bleach_liters, 0), 2),
                }
            )
        else:
            row.update(
                {
                    "feed_kg_consumed": np.nan,
                    "vaccine_doses_used": np.nan,
                    "bleach_liters_used": np.nan,
                }
            )

        rows.append(row)

    return pd.DataFrame(rows)


def generate_default_datasets(
    history_days: int = 420,
    forecast_days: int = 30,
    seed: int = 42,
    reference_date: date | None = None,
) -> tuple[pd.DataFrame, pd.DataFrame]:
    reference = reference_date or date.today()
    history_start = reference - timedelta(days=history_days)

    history_df = generate_inventory_consumption_data(
        start_date=history_start,
        periods=history_days,
        seed=seed,
        include_targets=True,
    )
    future_df = generate_inventory_consumption_data(
        start_date=reference,
        periods=forecast_days,
        seed=seed + 1000,
        include_targets=False,
    )

    return history_df, future_df


def save_default_datasets(
    history_path: Path = DATA_DIR / "synthetic_inventory_history.csv",
    future_path: Path = DATA_DIR / "synthetic_inventory_future.csv",
    history_days: int = 420,
    forecast_days: int = 30,
    seed: int = 42,
) -> tuple[Path, Path]:
    history_df, future_df = generate_default_datasets(
        history_days=history_days,
        forecast_days=forecast_days,
        seed=seed,
    )
    history_path.parent.mkdir(parents=True, exist_ok=True)
    future_path.parent.mkdir(parents=True, exist_ok=True)
    history_df.to_csv(history_path, index=False)
    future_df.to_csv(future_path, index=False)
    return history_path, future_path


def _estimate_daily_vaccine_doses(
    puppies: int,
    adult_new_animals: int,
    resident_adults: int,
    equine_vaccine_visits: int,
    current_date: date,
    outbreak_alert: int,
    rng: np.random.Generator,
) -> float:
    puppy_daily_share = VACCINE_PROTOCOL["canine"]["puppy_doses"] / 21
    adult_new_daily_share = VACCINE_PROTOCOL["canine"]["adult_new_animal_doses"] / 14
    resident_daily_share = VACCINE_PROTOCOL["canine"]["resident_adult_annual_doses"] / 365
    equine_visit_doses = VACCINE_PROTOCOL["equine"]["annual_doses"]

    campaign_boost = 0
    if current_date.month in [3, 9] and 5 <= current_date.day <= 12:
        campaign_boost = int(rng.integers(10, 24))

    expected = (
        puppies * puppy_daily_share
        + adult_new_animals * adult_new_daily_share
        + resident_adults * resident_daily_share
        + equine_vaccine_visits * equine_visit_doses
        + campaign_boost
        + outbreak_alert * 3
    )
    return expected + rng.normal(0, 1.2)


def main() -> None:
    parser = argparse.ArgumentParser(description="Generate synthetic inventory data.")
    parser.add_argument("--history-days", type=int, default=420)
    parser.add_argument("--forecast-days", type=int, default=30)
    parser.add_argument("--seed", type=int, default=42)
    parser.add_argument(
        "--history-output",
        type=Path,
        default=DATA_DIR / "synthetic_inventory_history.csv",
    )
    parser.add_argument(
        "--future-output",
        type=Path,
        default=DATA_DIR / "synthetic_inventory_future.csv",
    )
    args = parser.parse_args()

    history_path, future_path = save_default_datasets(
        history_path=args.history_output,
        future_path=args.future_output,
        history_days=args.history_days,
        forecast_days=args.forecast_days,
        seed=args.seed,
    )

    print(f"Synthetic inventory history saved to {history_path}")
    print(f"Synthetic inventory forecast inputs saved to {future_path}")


if __name__ == "__main__":
    main()
