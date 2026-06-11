import sqlite3
import pandas as pd
from system.condb import *

def extract_animal_data(conn: sqlite3.Connection) -> pd.DataFrame:
    """Extracts base animal profiles from the current pets/service_records schema."""
    query = """
        WITH total_pets AS (
            SELECT COUNT(*) AS total_count
            FROM pets
        ),
        last_arrivals AS (
            SELECT
                pet_id,
                MAX(arrival_date) AS last_arrival_date
            FROM service_records
            WHERE pet_id IS NOT NULL
            GROUP BY pet_id
        )
        SELECT 
            pets.id AS animal_id,
            CASE
                WHEN pets.birth_date IS NULL THEN 'adult'
                WHEN julianday('now') - julianday(pets.birth_date) < 365 THEN 'puppy'
                WHEN julianday('now') - julianday(pets.birth_date) > 2920 THEN 'senior'
                ELSE 'adult'
            END AS age_category,
            CASE
                WHEN pets.euthanasia = 1 THEN 'poor'
                ELSE 'fair'
            END AS health_on_arrival,
            CAST(
                MAX(
                    0,
                    julianday('now') - julianday(
                        COALESCE(last_arrivals.last_arrival_date, DATE(pets.created_at), DATE('now'))
                    )
                ) AS INTEGER
            ) AS days_in_shelter,
            MIN(1.0, total_pets.total_count / 500.0) AS current_block_occupancy_rate
        FROM pets
        CROSS JOIN total_pets
        LEFT JOIN last_arrivals ON last_arrivals.pet_id = pets.id
    """
    return pd.read_sql_query(query, conn)

def extract_exposure_data(conn: sqlite3.Connection) -> pd.DataFrame:
    """Builds exposure proxy features from the current service_records schema."""
    query = """
        SELECT 
            pets.id AS animal_id,
            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM service_records
                    WHERE service_records.pet_id = pets.id
                      AND LOWER(COALESCE(service_records.procedures, '')) LIKE '%isolamento%'
                ) THEN 1
                ELSE 0
            END AS exposed_to_sick_handler,
            CASE
                WHEN pets.euthanasia = 1 THEN 1
                ELSE 0
            END AS proximity_to_confirmed_case
        FROM pets
    """
    return pd.read_sql_query(query, conn)


def load_predictions_to_db(conn: sqlite3.Connection, predictions_df: pd.DataFrame) -> None:
    """Loads the newly calculated daily scores back into the database."""
    predictions_df.to_sql('daily_risk_predictions', conn, if_exists='append', index=False)
    conn.commit()


def extract_stock_levels(conn: sqlite3.Connection) -> dict[str, float]:
    """Queries current stock levels from deposit_items."""
    cursor = conn.cursor()
    cursor.execute("SELECT category, SUM(quantity) FROM deposit_items GROUP BY category")
    rows = cursor.fetchall()
    
    # Initialize with default values
    levels = {
        "feed_kg": 0.0,
        "vaccine_doses": 0.0,
        "bleach_liters": 0.0
    }
    
    # Map category names safely
    for cat, total in rows:
        if not cat:
            continue
        cat_lower = cat.lower()
        if "raç" in cat_lower or "rac" in cat_lower or "ra" in cat_lower: # Ração
            levels["feed_kg"] = float(total)
        elif "vac" in cat_lower: # Vacina
            levels["vaccine_doses"] = float(total)
        elif "limp" in cat_lower or "desinfetante" in cat_lower: # Limpeza
            levels["bleach_liters"] = float(total)
            
    return levels

