import sqlite3
import pandas as pd
from system.condb import *

def extract_animal_data(conn: sqlite3.Connection) -> pd.DataFrame:
    """Extracts base animal profiles, current occupancy, and shelter days."""
    query = """
        SELECT 
            animal_id, 
            age_category, 
            health_on_arrival, 
            days_in_shelter, 
            current_block_occupancy_rate 
        FROM active_animals
    """
    return pd.read_sql_query(query, conn)

def extract_exposure_data(conn: sqlite3.Connection) -> pd.DataFrame:
    """Extracts handler interaction and kennel proximity vectors."""
    query = """
        SELECT 
            animal_id, 
            exposed_to_sick_handler, 
            proximity_to_confirmed_case 
        FROM daily_exposure_logs
        WHERE log_date = DATE('now', '-1 day')
    """
    return pd.read_sql_query(query, conn)


def load_predictions_to_db(conn: sqlite3.Connection, predictions_df: pd.DataFrame) -> None:
    """Loads the newly calculated daily scores back into the database."""
    predictions_df.to_sql('daily_risk_predictions', conn, if_exists='append', index=False)
    conn.commit()