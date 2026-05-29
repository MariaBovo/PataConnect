from dataclasses import dataclass
from datetime import date, datetime, time
from typing import Optional

@dataclass
class CONAC:
    pass

@dataclass
class user:
    id: int
    full_name: str
    username: str
    created: datetime
    privileged: bool

@dataclass
class tutor:
    id: int
    cpf: Optional[str]
    full_name: str
    phone_1: Optional[str]
    phone_2: Optional[str]
    email: Optional[str]
    address: Optional[str]
    address_number: Optional[str]
    zip_code: Optional[str]
    neighborhood: Optional[str]
    address_complement: Optional[str]
    city: Optional[str]
    state: Optional[str]
    created_at: datetime

@dataclass
class pet:
    id: int
    name: Optional[str]
    microchip: Optional[str]
    species: str
    breed: Optional[str]
    gender: Optional[str]
    size: Optional[str]
    color: Optional[str]
    birth_date: Optional[date]
    is_castrated: bool
    euthanasia: bool
    created_at: datetime

@dataclass
class pet_vaccine:
    id: int
    pet_id: int
    vaccine_type: str
    is_administered: bool
    administered_date: Optional[date]
    notes: Optional[str]

@dataclass
class service_record:
    id: int
    record_number: Optional[str]
    request_date: Optional[date]
    request_time: Optional[time]
    requester_name: Optional[str]
    requester_phone: Optional[str]
    incident_address: Optional[str]
    incident_neighborhood: Optional[str]
    incident_landmark: Optional[str]
    incident_description: Optional[str]
    received_by_user_id: Optional[int]
    reported_species: Optional[str]
    reported_gender: Optional[str]
    reported_size: Optional[str]
    reported_color: Optional[str]
    rescue_user_id: Optional[int]
    investigation_date: Optional[date]
    investigation_time: Optional[time]
    animal_found: Optional[bool]
    procedures: Optional[str]
    animal_collected: Optional[bool]
    forwarded_to: Optional[str]
    arrival_date: Optional[date]
    arrival_time: Optional[time]
    pet_id: Optional[int]

@dataclass
class control_record:
    id: int
    record_type: str
    tutor_id: int
    pet_id: int
    created_at: datetime

@dataclass
class responsibility_term:
    id: int
    control_record_id: int
    city: str
    signed_date: Optional[date]
    witness_1_name: Optional[str]
    witness_1_cpf: Optional[str]
    witness_2_name: Optional[str]
    witness_2_cpf: Optional[str]
    created_at: datetime

