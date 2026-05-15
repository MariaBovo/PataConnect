import sqlite3

def create_connection(db_path: str) -> sqlite3.Connection:
    """Creates and returns a database connection."""
    return sqlite3.connect(db_path)