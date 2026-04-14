-- 1. Base tables

CREATE TABLE "users" (
    "id" SERIAL PRIMARY KEY,
    "full_name" VARCHAR(255) NOT NULL,
    "username" VARCHAR(100) UNIQUE NOT NULL,
    "pass_hash" TEXT NOT NULL,
    "created" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "privileged" BOOLEAN DEFAULT FALSE
);

CREATE TABLE "pet" (
    "id" SERIAL PRIMARY KEY,
    "type" VARCHAR(50) NOT NULL,
    "breed" VARCHAR(100),
    "sex" BOOLEAN NOT NULL,
    "has_active_zoonosis" BOOLEAN DEFAULT FALSE
);

CREATE TABLE "zoonoses" (
    "id" SERIAL PRIMARY KEY,
    "illness" VARCHAR(255) NOT NULL,
    "details" TEXT
);

-- 2. Relationship tables

CREATE TABLE "pet_checkinout" (
    "id" SERIAL PRIMARY KEY,
    "pet_id" INTEGER REFERENCES "pet"("id") ON DELETE CASCADE,
    "user_id" INTEGER REFERENCES "users"("id") ON DELETE SET NULL,
    "created" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "is_checkout" BOOLEAN DEFAULT FALSE
);

CREATE TABLE "pet_zoonoses" (
    "id" SERIAL PRIMARY KEY,
    "zoonoses_id" INTEGER REFERENCES "zoonoses"("id") ON DELETE CASCADE,
    "pet_id" INTEGER REFERENCES "pet"("id") ON DELETE CASCADE,
    "user_id" INTEGER REFERENCES "users"("id") ON DELETE SET NULL,
    "created" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. FK indexes
CREATE INDEX idxfk_pet_checkin ON pet_checkinout(pet_id);
CREATE INDEX idxfk_pet_zoonoses ON pet_zoonoses(pet_id);