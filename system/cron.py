from datetime import date
from analytics.dbutils import *
from analytics.models import *
from system.ipc import IPC

def run_daily_batch_process(db_path: str) -> None:
    """
    Main functional pipeline orchestration.
    Data flows from extraction -> transformation -> loading.
    """
    print(f"Starting batch process for {date.today()}...")
    
    with create_connection(db_path) as conn:
        animal_data = extract_animal_data(conn)
        exposure_data = extract_exposure_data(conn)
        
        if animal_data.empty:
            print("No active animals found. Exiting.")
            return

        daily_predictions = apply_scoring_models(
            animal_df=animal_data,
            exposure_df=exposure_data,
            calc_general_risk=predict_general_disease_risk,
            calc_severe_risk=predict_severe_zoonotic_risk
        )
        
        load_predictions_to_db(conn, daily_predictions)
        
    print(f"Batch process complete. {len(daily_predictions)} animal profiles updated.")


@IPC.publish
def run_daily_batch(db_path: str = None) -> dict[str, Any]:
    if db_path is None:
        import os
        from pathlib import Path
        PROJECT_ROOT = Path(__file__).resolve().parents[1]
        db_path = str(PROJECT_ROOT / "storage" / "database.sqlite")
        
    try:
        run_daily_batch_process(db_path)
        return {"status": "success", "message": "Batch process completed successfully."}
    except Exception as e:
        return {"status": "error", "message": str(e)}


if __name__ == "__main__":
    # run_daily_batch_process('canil.db')
    pass