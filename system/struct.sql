-- 1. SYSTEM USERS (Employees/Staff)
-- Keeps your original structure for system access.
CREATE TABLE "users" (
    "id" SERIAL PRIMARY KEY,
    "full_name" VARCHAR(255) NOT NULL,
    "username" VARCHAR(100) UNIQUE NOT NULL,
    "pass_hash" TEXT NOT NULL,
    "created" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "privileged" BOOLEAN DEFAULT FALSE
);

-- 2. TUTORS (Citizens / Munícipes / Adopters)
-- Captures "DADOS DO TUTOR RESPONSÁVEL" from the Ficha de Controle.
CREATE TABLE "tutors" (
    "id" SERIAL PRIMARY KEY,
    "cpf" VARCHAR(14) UNIQUE,
    "full_name" VARCHAR(255) NOT NULL,
    "phone_1" VARCHAR(20),
    "phone_2" VARCHAR(20),
    "email" VARCHAR(255),
    "address" VARCHAR(255),
    "address_number" VARCHAR(20),
    "zip_code" VARCHAR(10),
    "neighborhood" VARCHAR(100),
    "address_complement" VARCHAR(100),
    "city" VARCHAR(100),
    "state" VARCHAR(2),
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. PETS (Animal Master Data)
-- Expanded to include all "DADOS DO ANIMAL" from both forms.
CREATE TABLE "pets" (
    "id" SERIAL PRIMARY KEY,
    "name" VARCHAR(255),
    "microchip" VARCHAR(100) UNIQUE,
    "species" VARCHAR(50) NOT NULL, -- e.g., Canino, Felino, Equino, Bovino, Outro
    "breed" VARCHAR(100),
    "gender" VARCHAR(20),           -- Macho, Fêmea (Changed from BOOLEAN for clarity)
    "size" VARCHAR(20),             -- Pequeno, Médio, Grande
    "color" VARCHAR(50),
    "birth_date" DATE,
    "is_castrated" BOOLEAN DEFAULT FALSE,
    "euthanasia" BOOLEAN DEFAULT FALSE,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. VACCINES 
-- Captures the "Vacina Polivalente" and "Vacina Antirrábica" checkboxes and dates.
CREATE TABLE "pet_vaccines" (
    "id" SERIAL PRIMARY KEY,
    "pet_id" INTEGER REFERENCES "pets"("id") ON DELETE CASCADE,
    "vaccine_type" VARCHAR(50) NOT NULL, -- Polivalente, Antirrábica
    "is_administered" BOOLEAN DEFAULT FALSE,
    "administered_date" DATE,
    "notes" TEXT
);

-- 5. SERVICE & RESCUE RECORDS (Ficha de Atendimento ao Munícipe)
-- Captures the entire workflow of the first document (incident report + on-site investigation).
CREATE TABLE "service_records" (
    "id" SERIAL PRIMARY KEY,
    "record_number" VARCHAR(50),
    
    -- Request Data
    "request_date" DATE,
    "request_time" TIME,
    "requester_name" VARCHAR(255),
    "requester_phone" VARCHAR(20),
    "incident_address" VARCHAR(255),
    "incident_neighborhood" VARCHAR(100),
    "incident_landmark" VARCHAR(255),
    "incident_description" TEXT,
    "received_by_user_id" INTEGER REFERENCES "users"("id") ON DELETE SET NULL,
    
    -- Reported Animal Data (Before actual rescue)
    "reported_species" VARCHAR(50),
    "reported_gender" VARCHAR(20),
    "reported_size" VARCHAR(20),
    "reported_color" VARCHAR(50),
    
    -- On-Site Verification (Averiguação no Local)
    "rescue_user_id" INTEGER REFERENCES "users"("id") ON DELETE SET NULL,
    "investigation_date" DATE,
    "investigation_time" TIME,
    "animal_found" BOOLEAN,
    "procedures" TEXT,
    "animal_collected" BOOLEAN,
    "forwarded_to" VARCHAR(255),
    "arrival_date" DATE,
    "arrival_time" TIME,
    "pet_id" INTEGER REFERENCES "pets"("id") ON DELETE SET NULL
);

-- 6. CONTROL RECORDS (Ficha de Controle)
-- Replaces "adoption" to handle Adoção, Castração, Transferência, or Cadastramento.
CREATE TABLE "control_records" (
    "id" SERIAL PRIMARY KEY,
    "record_type" VARCHAR(50) NOT NULL, -- Adoção, Castração, Transferência, Cadastramento
    "tutor_id" INTEGER REFERENCES "tutors"("id") ON DELETE RESTRICT,
    "pet_id" INTEGER REFERENCES "pets"("id") ON DELETE RESTRICT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. RESPONSIBILITY TERMS (Termo de Responsabilidade de Tutela)
-- Captures the legal signatures and witnesses from the final page.
CREATE TABLE "responsibility_terms" (
    "id" SERIAL PRIMARY KEY,
    "control_record_id" INTEGER REFERENCES "control_records"("id") ON DELETE CASCADE,
    "city" VARCHAR(100) DEFAULT 'Rio Claro',
    "signed_date" DATE,
    "witness_1_name" VARCHAR(255),
    "witness_1_cpf" VARCHAR(14),
    "witness_2_name" VARCHAR(255),
    "witness_2_cpf" VARCHAR(14),
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);