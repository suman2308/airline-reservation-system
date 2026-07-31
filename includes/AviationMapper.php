<?php
/**
 * AeroBook – Aviationstack Field Mapper
 *
 * Maps Aviationstack API response fields to AeroBook database columns.
 * Each function normalizes and sanitizes API data before storage.
 *
 * Field mappings are verified against actual Aviationstack API responses.
 */

class AviationMapper {

    /**
     * Map airport API response to database row.
     * Aviationstack /v1/airports fields mapped to aviation_airports table.
     */
    public static function mapAirport($apiRow) {
        return [
            'iata_code' => self::str($apiRow['iata_code'] ?? ''),
            'icao_code' => self::str($apiRow['icao_code'] ?? ''),
            'airport_name' => self::str($apiRow['airport_name'] ?? ''),
            'city_iata_code' => self::str($apiRow['city_iata_code'] ?? ''),
            'country_iso2' => self::str($apiRow['country_iso2'] ?? ''),
            'country_name' => self::str($apiRow['country_name'] ?? ''),
            'latitude' => self::float($apiRow['latitude'] ?? null),
            'longitude' => self::float($apiRow['longitude'] ?? null),
            'timezone' => self::str($apiRow['timezone'] ?? ''),
            'gmt_offset' => self::str($apiRow['gmt'] ?? ''),
            'phone_number' => self::str($apiRow['phone_number'] ?? ''),
            'aviationstack_id' => self::str($apiRow['id'] ?? ''),
        ];
    }

    /**
     * Map airline API response to database row.
     * Aviationstack /v1/airlines fields mapped to aviation_airlines table.
     */
    public static function mapAirline($apiRow) {
        return [
            'iata_code' => self::str($apiRow['iata_code'] ?? ''),
            'icao_code' => self::str($apiRow['icao_code'] ?? ''),
            'airline_name' => self::str($apiRow['airline_name'] ?? ''),
            'country_iso2' => self::str($apiRow['country_iso2'] ?? ''),
            'country_name' => self::str($apiRow['country_name'] ?? ''),
            'callsign' => self::str($apiRow['callsign'] ?? ''),
            'hub_code' => self::str($apiRow['hub_code'] ?? ''),
            'fleet_size' => self::int($apiRow['fleet_size'] ?? 0),
            'fleet_average_age' => self::float($apiRow['fleet_average_age'] ?? 0),
            'status' => self::str($apiRow['status'] ?? ''),
            'type' => self::str($apiRow['type'] ?? ''),
            'date_founded' => self::str($apiRow['date_founded'] ?? ''),
            'aviationstack_id' => self::str($apiRow['id'] ?? ''),
        ];
    }

    /**
     * Map aircraft_type API response to database row.
     * Aviationstack /v1/aircraft_types fields mapped to aviation_aircraft_types table.
     */
    public static function mapAircraftType($apiRow) {
        return [
            'iata_code' => self::str($apiRow['iata_code'] ?? ''),
            'aircraft_name' => self::str($apiRow['aircraft_name'] ?? ''),
            'aviationstack_id' => self::str($apiRow['id'] ?? ''),
        ];
    }

    /**
     * Map airplane API response to database row.
     * Aviationstack /v1/airplanes fields mapped to aviation_airplanes table.
     */
    public static function mapAirplane($apiRow) {
        return [
            'registration_number' => self::str($apiRow['registration_number'] ?? ''),
            'production_line' => self::str($apiRow['production_line'] ?? ''),
            'iata_code' => self::str(($apiRow['aircraft_type'] ?? [])['iata_code'] ?? ($apiRow['iata_code'] ?? '')),
            'aircraft_type_name' => self::str(($apiRow['aircraft_type'] ?? [])['aircraft_name'] ?? ($apiRow['model_name'] ?? '')),
            'model_name' => self::str($apiRow['model_name'] ?? ($apiRow['model'] ?? '')),
            'manufacturer' => self::str($apiRow['manufacturer'] ?? ($apiRow['production_line'] ?? '')),
            'airline_iata' => self::str(($apiRow['airline'] ?? [])['iata_code'] ?? ($apiRow['airline_iata'] ?? '')),
            'airline_name' => self::str(($apiRow['airline'] ?? [])['name'] ?? ($apiRow['airline_name'] ?? '')),
            'plane_owner' => self::str($apiRow['plane_owner'] ?? ''),
            'plane_age' => self::float($apiRow['plane_age'] ?? 0),
            'seats' => self::int($apiRow['seats'] ?? 0),
            'engines' => self::int($apiRow['engines'] ?? 0),
            'plane_status' => self::str($apiRow['plane_status'] ?? ''),
            'aviationstack_id' => self::str($apiRow['id'] ?? ''),
        ];
    }

    /**
     * Map country API response to database row.
     * Aviationstack /v1/countries fields mapped to aviation_countries table.
     */
    public static function mapCountry($apiRow) {
        return [
            'iso2' => self::str($apiRow['country_iso2'] ?? ''),
            'iso3' => self::str($apiRow['country_iso3'] ?? ''),
            'country_name' => self::str($apiRow['country_name'] ?? ''),
            'capital' => self::str($apiRow['capital'] ?? ''),
            'continent' => self::str($apiRow['continent'] ?? ''),
            'currency_code' => self::str($apiRow['currency_code'] ?? ''),
            'currency_name' => self::str($apiRow['currency_name'] ?? ''),
            'phone_prefix' => self::str($apiRow['phone_prefix'] ?? ''),
            'population' => self::int($apiRow['population'] ?? 0),
            'iso_numeric' => self::str($apiRow['country_iso_numeric'] ?? ''),
            'fips_code' => self::str($apiRow['fips_code'] ?? ''),
            'aviationstack_id' => self::str($apiRow['id'] ?? ''),
        ];
    }

    /**
     * Map flight API response to enriched flight metadata.
     * Aviationstack /v1/flights fields mapped for flight enrichment.
     * Note: aircraft and live fields may be null on free plan.
     */
    public static function mapFlight($apiRow) {
        $departure = $apiRow['departure'] ?? [];
        $arrival = $apiRow['arrival'] ?? [];
        $airline = $apiRow['airline'] ?? [];
        $flight = $apiRow['flight'] ?? [];

        return [
            'flight_date' => self::str($apiRow['flight_date'] ?? ''),
            'flight_status' => self::str($apiRow['flight_status'] ?? ''),
            'departure_airport' => self::str($departure['airport'] ?? ''),
            'departure_iata' => self::str($departure['iata'] ?? ''),
            'departure_icao' => self::str($departure['icao'] ?? ''),
            'departure_terminal' => self::str($departure['terminal'] ?? ''),
            'departure_gate' => self::str($departure['gate'] ?? ''),
            'departure_scheduled' => self::str($departure['scheduled'] ?? ''),
            'departure_estimated' => self::str($departure['estimated'] ?? ''),
            'departure_actual' => self::str($departure['actual'] ?? ''),
            'departure_delay' => self::int($departure['delay'] ?? 0),
            'arrival_airport' => self::str($arrival['airport'] ?? ''),
            'arrival_iata' => self::str($arrival['iata'] ?? ''),
            'arrival_icao' => self::str($arrival['icao'] ?? ''),
            'arrival_terminal' => self::str($arrival['terminal'] ?? ''),
            'arrival_gate' => self::str($arrival['gate'] ?? ''),
            'arrival_scheduled' => self::str($arrival['scheduled'] ?? ''),
            'arrival_estimated' => self::str($arrival['estimated'] ?? ''),
            'arrival_actual' => self::str($arrival['actual'] ?? ''),
            'arrival_baggage' => self::str($arrival['baggage'] ?? ''),
            'airline_name' => self::str($airline['name'] ?? ''),
            'airline_iata' => self::str($airline['iata'] ?? ''),
            'airline_icao' => self::str($airline['icao'] ?? ''),
            'flight_number' => self::str($flight['number'] ?? ''),
            'flight_iata' => self::str($flight['iata'] ?? ''),
            'flight_icao' => self::str($flight['icao'] ?? ''),
        ];
    }

    // ─── Sanitization helpers ───

    private static function str($value) {
        if ($value === null || $value === false) return '';
        return trim((string)$value);
    }

    private static function int($value) {
        if ($value === null || $value === false || $value === '') return 0;
        return (int)$value;
    }

    private static function float($value) {
        if ($value === null || $value === false || $value === '') return null;
        return (float)$value;
    }
}
