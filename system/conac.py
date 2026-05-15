from dataclasses import dataclass
from datetime import date

@dataclass
class CONAC:
    pass

@dataclass
class pet:
    id: int
    type: str
    breed: str
    sex: bool
    has_active_zoonosis: bool
    
@dataclass
class user:
    id: int
    full_name: str
    username: str
    created: date
    privileged: bool
    
@dataclass
class zoonoses:
    id: int
    illness: str
    details: str

