import pandas as pd
from typing import Callable
from datetime import date

def predict_general_disease_risk(features: pd.DataFrame) -> pd.Series:
    """
    Mocks the XGBoost/LightGBM model for general diseases.
    In a real scenario, this would be: return xgb_model.predict_proba(features)[:, 1]
    """
    risk = (features['days_in_shelter'] * 0.005) + \
           (features['current_block_occupancy_rate'] * 0.2)
    
    risk += features['age_category'].apply(lambda x: 0.15 if x in ['puppy', 'senior'] else 0.0)
    risk += features['health_on_arrival'].apply(lambda x: 0.2 if x == 'poor' else 0.0)
    
    return risk.clip(0, 1.0) 

def predict_severe_zoonotic_risk(features: pd.DataFrame) -> pd.Series:
    """
    Mocks the Markov Chain / Graph Network model for transmission.
    """
    risk = features['exposed_to_sick_handler'].apply(lambda x: 0.6 if x else 0.0) + \
           features['proximity_to_confirmed_case'].apply(lambda x: 0.4 if x else 0.0)
    
    return risk.clip(0, 1.0)

def apply_scoring_models(
    animal_df: pd.DataFrame, 
    exposure_df: pd.DataFrame,
    calc_general_risk: Callable,
    calc_severe_risk: Callable
) -> pd.DataFrame:
    """
    Merges data and applies the dual risk scoring system without mutating original inputs.
    """
    combined_df = pd.merge(animal_df, exposure_df, on='animal_id', how='left').fillna(0)
    
    scored_df = combined_df.copy()
    scored_df['general_risk_score'] = calc_general_risk(scored_df)
    scored_df['severe_risk_score'] = calc_severe_risk(scored_df)
    
    scored_df['action_required'] = (scored_df['severe_risk_score'] > 0.75) | (scored_df['general_risk_score'] > 0.85)
    scored_df['prediction_date'] = date.today().isoformat()
    
    return scored_df[['animal_id', 'prediction_date', 'general_risk_score', 'severe_risk_score', 'action_required']]