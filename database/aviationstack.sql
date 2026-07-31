-- =============================================
-- AeroBook – Aviationstack Integration Schema
-- =============================================
-- Run this file after aerobook.sql to add Aviationstack support.
-- All tables use UPSERT logic (INSERT ... ON DUPLICATE KEY UPDATE).
-- Does NOT modify existing AeroBook tables.

-- =============================================
-- Table: aviation_airports
-- Source: Aviationstack /v1/airports
-- Unique key: iata_code (international identifier)
-- =============================================
CREATE TABLE IF NOT EXISTS aviation_airports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iata_code VARCHAR(3) NOT NULL DEFAULT '',
    icao_code VARCHAR(4) NOT NULL DEFAULT '',
    airport_name VARCHAR(255) NOT NULL DEFAULT '',
    city_iata_code VARCHAR(3) NOT NULL DEFAULT '',
    country_iso2 VARCHAR(2) NOT NULL DEFAULT '',
    country_name VARCHAR(100) NOT NULL DEFAULT '',
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    timezone VARCHAR(50) NOT NULL DEFAULT '',
    gmt_offset VARCHAR(5) NOT NULL DEFAULT '',
    phone_number VARCHAR(50) NOT NULL DEFAULT '',
    aviationstack_id VARCHAR(20) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_iata (iata_code),
    INDEX idx_icao (icao_code),
    INDEX idx_country (country_iso2),
    INDEX idx_city_iata (city_iata_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: aviation_airlines
-- Source: Aviationstack /v1/airlines
-- Unique key: iata_code (international identifier)
-- =============================================
CREATE TABLE IF NOT EXISTS aviation_airlines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iata_code VARCHAR(2) NOT NULL DEFAULT '',
    icao_code VARCHAR(3) NOT NULL DEFAULT '',
    airline_name VARCHAR(255) NOT NULL DEFAULT '',
    country_iso2 VARCHAR(2) NOT NULL DEFAULT '',
    country_name VARCHAR(100) NOT NULL DEFAULT '',
    callsign VARCHAR(50) NOT NULL DEFAULT '',
    hub_code VARCHAR(3) NOT NULL DEFAULT '',
    fleet_size INT DEFAULT 0,
    fleet_average_age DECIMAL(5,1) DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT '',
    type VARCHAR(50) NOT NULL DEFAULT '',
    date_founded VARCHAR(4) NOT NULL DEFAULT '',
    aviationstack_id VARCHAR(20) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_iata (iata_code),
    INDEX idx_icao (icao_code),
    INDEX idx_country (country_iso2),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: aviation_aircraft_types
-- Source: Aviationstack /v1/aircraft_types
-- Unique key: iata_code
-- =============================================
CREATE TABLE IF NOT EXISTS aviation_aircraft_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iata_code VARCHAR(3) NOT NULL DEFAULT '',
    aircraft_name VARCHAR(255) NOT NULL DEFAULT '',
    aviationstack_id VARCHAR(20) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_iata (iata_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: aviation_countries
-- Source: Aviationstack /v1/countries
-- Unique key: iso2 (ISO 3166-1 alpha-2)
-- =============================================
CREATE TABLE IF NOT EXISTS aviation_countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iso2 VARCHAR(2) NOT NULL DEFAULT '',
    iso3 VARCHAR(3) NOT NULL DEFAULT '',
    country_name VARCHAR(100) NOT NULL DEFAULT '',
    capital VARCHAR(100) NOT NULL DEFAULT '',
    continent VARCHAR(2) NOT NULL DEFAULT '',
    currency_code VARCHAR(3) NOT NULL DEFAULT '',
    currency_name VARCHAR(50) NOT NULL DEFAULT '',
    phone_prefix VARCHAR(10) NOT NULL DEFAULT '',
    population INT DEFAULT 0,
    iso_numeric VARCHAR(3) NOT NULL DEFAULT '',
    fips_code VARCHAR(2) NOT NULL DEFAULT '',
    aviationstack_id VARCHAR(20) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_iso2 (iso2),
    INDEX idx_iso3 (iso3),
    INDEX idx_continent (continent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: aviation_flights
-- Source: Aviationstack /v1/flights
-- Stores enriched flight metadata from external API.
-- Unique key: flight_iata + flight_date
-- This table is READ-ONLY for enrichment purposes.
-- Existing AeroBook flights table remains the source of truth.
-- =============================================
CREATE TABLE IF NOT EXISTS aviation_flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_date DATE DEFAULT NULL,
    flight_status VARCHAR(20) NOT NULL DEFAULT '',
    departure_airport VARCHAR(255) NOT NULL DEFAULT '',
    departure_iata VARCHAR(3) NOT NULL DEFAULT '',
    departure_icao VARCHAR(4) NOT NULL DEFAULT '',
    departure_terminal VARCHAR(10) NOT NULL DEFAULT '',
    departure_gate VARCHAR(10) NOT NULL DEFAULT '',
    departure_scheduled DATETIME DEFAULT NULL,
    departure_estimated DATETIME DEFAULT NULL,
    departure_actual DATETIME DEFAULT NULL,
    departure_delay INT DEFAULT 0,
    arrival_airport VARCHAR(255) NOT NULL DEFAULT '',
    arrival_iata VARCHAR(3) NOT NULL DEFAULT '',
    arrival_icao VARCHAR(4) NOT NULL DEFAULT '',
    arrival_terminal VARCHAR(10) NOT NULL DEFAULT '',
    arrival_gate VARCHAR(10) NOT NULL DEFAULT '',
    arrival_scheduled DATETIME DEFAULT NULL,
    arrival_estimated DATETIME DEFAULT NULL,
    arrival_actual DATETIME DEFAULT NULL,
    arrival_baggage VARCHAR(10) NOT NULL DEFAULT '',
    airline_name VARCHAR(255) NOT NULL DEFAULT '',
    airline_iata VARCHAR(2) NOT NULL DEFAULT '',
    airline_icao VARCHAR(3) NOT NULL DEFAULT '',
    flight_number VARCHAR(10) NOT NULL DEFAULT '',
    flight_iata VARCHAR(10) NOT NULL DEFAULT '',
    flight_icao VARCHAR(10) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_flight_date (flight_iata, flight_date),
    INDEX idx_airline (airline_iata),
    INDEX idx_departure (departure_iata),
    INDEX idx_arrival (arrival_iata),
    INDEX idx_status (flight_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: aviation_airplanes
-- Source: Aviationstack /v1/airplanes
-- Unique key: registration_number
-- =============================================
CREATE TABLE IF NOT EXISTS aviation_airplanes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(20) NOT NULL DEFAULT '',
    production_line VARCHAR(100) NOT NULL DEFAULT '',
    iata_code VARCHAR(3) NOT NULL DEFAULT '',
    aircraft_type_name VARCHAR(255) NOT NULL DEFAULT '',
    model_name VARCHAR(255) NOT NULL DEFAULT '',
    manufacturer VARCHAR(255) NOT NULL DEFAULT '',
    airline_iata VARCHAR(2) NOT NULL DEFAULT '',
    airline_name VARCHAR(255) NOT NULL DEFAULT '',
    plane_owner VARCHAR(255) NOT NULL DEFAULT '',
    plane_age DECIMAL(5,1) DEFAULT 0,
    seats INT DEFAULT 0,
    engines INT DEFAULT 0,
    plane_status VARCHAR(50) NOT NULL DEFAULT '',
    aviationstack_id VARCHAR(20) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_registration (registration_number),
    INDEX idx_iata (iata_code),
    INDEX idx_airline (airline_iata),
    INDEX idx_model (model_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: api_sync_logs
-- Records every Aviationstack synchronization run.
-- =============================================
CREATE TABLE IF NOT EXISTS api_sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL DEFAULT 'aviationstack',
    endpoint VARCHAR(50) NOT NULL,
    status ENUM('success', 'failed') NOT NULL DEFAULT 'failed',
    records_added INT DEFAULT 0,
    records_updated INT DEFAULT 0,
    records_skipped INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    total_available INT DEFAULT 0,
    total_fetched INT DEFAULT 0,
    error_message TEXT DEFAULT NULL,
    duration_ms INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider (provider),
    INDEX idx_endpoint (endpoint),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
