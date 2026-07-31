<?php
/**
 * AeroBook – Aviationstack Sync Service
 *
 * Orchestrates synchronization of Aviationstack data into AeroBook's database.
 * Each sync method fetches data via AviationStackClient, maps fields via
 * AviationMapper, and upserts records using MySQL INSERT ... ON DUPLICATE KEY UPDATE.
 *
 * All sync operations are logged to api_sync_logs and the application logger.
 * Passenger-facing features never call Aviationstack directly.
 */

require_once __DIR__ . '/AviationStackClient.php';
require_once __DIR__ . '/AviationMapper.php';

class AviationSyncService {
    private $client;
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $apiKey = defined('AVIATIONSTACK_API_KEY') ? AVIATIONSTACK_API_KEY : '';
        $this->client = new AviationStackClient($apiKey);
    }

    /**
     * Test API connectivity and return status.
     */
    public function testConnection() {
        $start = microtime(true);
        $result = $this->client->testConnection();
        $duration = round((microtime(true) - $start) * 1000);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'API connection successful (' . $duration . 'ms)',
                'duration_ms' => $duration,
            ];
        }

        return [
            'success' => false,
            'message' => $result['error'] ?? 'Connection failed',
            'duration_ms' => $duration,
        ];
    }

    /**
     * Sync airplanes from Aviationstack.
     * Free plan returns real registration_number/aircraft_type data.
     */
    public function syncAirplanes() {
        $report = $this->initReport('airplanes');

        try {
            $result = $this->client->get('airplanes', ['limit' => 100]);
            if (!$result['success']) {
                return $this->failReport($report, $result['error']);
            }

            $this->beginTransaction();
            foreach ($result['data'] as $row) {
                $mapped = AviationMapper::mapAirplane($row);
                if (empty($mapped['registration_number'])) {
                    $report['skipped']++;
                    continue;
                }
                if ($this->upsertAirplane($mapped)) {
                    $report['updated']++;
                } else {
                    $report['added']++;
                }
            }
            $this->commitTransaction();
            return $this->completeReport($report, $result);
        } catch (Exception $e) {
            $this->rollbackTransaction();
            return $this->failReport($report, $e->getMessage());
        }
    }

    /**
     * Sync airports from Aviationstack.
     */
    public function syncAirports() {
        $report = $this->initReport('airports');

        try {
            $result = $this->client->get('airports', ['limit' => 100]);
            if (!$result['success']) {
                return $this->failReport($report, $result['error']);
            }

            $this->beginTransaction();
            foreach ($result['data'] as $row) {
                $mapped = AviationMapper::mapAirport($row);
                if (empty($mapped['iata_code']) && empty($mapped['icao_code'])) {
                    $report['skipped']++;
                    continue;
                }
                if ($this->upsertAirport($mapped)) {
                    $report['updated']++;
                } else {
                    $report['added']++;
                }
            }
            $this->commitTransaction();
            return $this->completeReport($report, $result);
        } catch (Exception $e) {
            $this->rollbackTransaction();
            return $this->failReport($report, $e->getMessage());
        }
    }

    /**
     * Sync airlines from Aviationstack.
     */
    public function syncAirlines() {
        $report = $this->initReport('airlines');

        try {
            $result = $this->client->get('airlines', ['limit' => 100]);
            if (!$result['success']) {
                return $this->failReport($report, $result['error']);
            }

            $this->beginTransaction();
            foreach ($result['data'] as $row) {
                $mapped = AviationMapper::mapAirline($row);
                if (empty($mapped['iata_code']) && empty($mapped['icao_code'])) {
                    $report['skipped']++;
                    continue;
                }
                if ($this->upsertAirline($mapped)) {
                    $report['updated']++;
                } else {
                    $report['added']++;
                }
            }
            $this->commitTransaction();
            return $this->completeReport($report, $result);
        } catch (Exception $e) {
            $this->rollbackTransaction();
            return $this->failReport($report, $e->getMessage());
        }
    }

    /**
     * Sync aircraft types from Aviationstack.
     */
    public function syncAircraftTypes() {
        $report = $this->initReport('aircraft_types');

        try {
            $result = $this->client->get('aircraft_types', ['limit' => 100]);
            if (!$result['success']) {
                return $this->failReport($report, $result['error']);
            }

            $this->beginTransaction();
            foreach ($result['data'] as $row) {
                $mapped = AviationMapper::mapAircraftType($row);
                if (empty($mapped['iata_code'])) {
                    $report['skipped']++;
                    continue;
                }
                if ($this->upsertAircraftType($mapped)) {
                    $report['updated']++;
                } else {
                    $report['added']++;
                }
            }
            $this->commitTransaction();
            return $this->completeReport($report, $result);
        } catch (Exception $e) {
            $this->rollbackTransaction();
            return $this->failReport($report, $e->getMessage());
        }
    }

    /**
     * Sync countries from Aviationstack.
     */
    public function syncCountries() {
        $report = $this->initReport('countries');

        try {
            $result = $this->client->get('countries', ['limit' => 100]);
            if (!$result['success']) {
                return $this->failReport($report, $result['error']);
            }

            $this->beginTransaction();
            foreach ($result['data'] as $row) {
                $mapped = AviationMapper::mapCountry($row);
                if (empty($mapped['iso2'])) {
                    $report['skipped']++;
                    continue;
                }
                if ($this->upsertCountry($mapped)) {
                    $report['updated']++;
                } else {
                    $report['added']++;
                }
            }
            $this->commitTransaction();
            return $this->completeReport($report, $result);
        } catch (Exception $e) {
            $this->rollbackTransaction();
            return $this->failReport($report, $e->getMessage());
        }
    }

    /**
     * Sync flights from Aviationstack (limited on free plan).
     * Only enriches metadata — never overwrites bookings, seats, or pricing.
     */
    public function syncFlights() {
        $report = $this->initReport('flights');

        try {
            $result = $this->client->get('flights', ['limit' => 100]);
            if (!$result['success']) {
                return $this->failReport($report, $result['error']);
            }

            // Note: Free plan returns limited real-time data.
            // This sync stores flight metadata for reference.
            $this->beginTransaction();
            foreach ($result['data'] as $row) {
                $mapped = AviationMapper::mapFlight($row);
                if (empty($mapped['flight_iata'])) {
                    $report['skipped']++;
                    continue;
                }
                if ($this->upsertFlightMeta($mapped)) {
                    $report['updated']++;
                } else {
                    $report['added']++;
                }
            }
            $this->commitTransaction();
            return $this->completeReport($report, $result);
        } catch (Exception $e) {
            $this->rollbackTransaction();
            return $this->failReport($report, $e->getMessage());
        }
    }

    /**
     * Get the last sync log entry for a specific endpoint.
     * Returns null gracefully if api_sync_logs has not been migrated yet
     * (aviation tables live in database/aviationstack.sql).
     */
    public function getLastSync($endpoint) {
        try {
            $stmt = mysqli_prepare($this->conn,
                "SELECT * FROM api_sync_logs WHERE endpoint = ? ORDER BY created_at DESC LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, "s", $endpoint);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $row;
        } catch (mysqli_sql_exception $e) {
            return null; // table not migrated yet — treat as "never synced"
        }
    }

    /**
     * Get sync status summary for all endpoints.
     */
    public function getAllSyncStatus() {
        $endpoints = ['airports', 'airlines', 'aircraft_types', 'airplanes', 'countries', 'flights'];
        $statuses = [];
        foreach ($endpoints as $ep) {
            $last = $this->getLastSync($ep);
            $count = 0;
            $tableMap = [
                'airports' => 'aviation_airports',
                'airlines' => 'aviation_airlines',
                'aircraft_types' => 'aviation_aircraft_types',
                'airplanes' => 'aviation_airplanes',
                'countries' => 'aviation_countries',
                'flights' => 'aviation_flights',
            ];
            if (isset($tableMap[$ep])) {
                try {
                    $r = mysqli_query($this->conn, "SELECT COUNT(*) as c FROM {$tableMap[$ep]}");
                    if ($r) $count = (int)mysqli_fetch_assoc($r)['c'];
                } catch (mysqli_sql_exception $e) {
                    $count = 0; // table not migrated yet — show zero records
                }
            }
            $statuses[$ep] = [
                'last_sync' => $last,
                'record_count' => $count,
            ];
        }
        return $statuses;
    }

    // ─── Database UPSERT methods ───
    // Return true if updated, false if inserted.

    private function upsertAirport($data) {
        $sql = "INSERT INTO aviation_airports
                (iata_code, icao_code, airport_name, city_iata_code, country_iso2, country_name,
                 latitude, longitude, timezone, gmt_offset, phone_number, aviationstack_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                airport_name = VALUES(airport_name),
                country_iso2 = VALUES(country_iso2),
                country_name = VALUES(country_name),
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                timezone = VALUES(timezone),
                phone_number = VALUES(phone_number),
                aviationstack_id = VALUES(aviationstack_id)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssddssss",
            $data['iata_code'], $data['icao_code'], $data['airport_name'],
            $data['city_iata_code'], $data['country_iso2'], $data['country_name'],
            $data['latitude'], $data['longitude'], $data['timezone'],
            $data['gmt_offset'], $data['phone_number'], $data['aviationstack_id']
        );
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($this->conn);
        mysqli_stmt_close($stmt);
        return $affected === 2; // 2 = updated (MySQL-specific: 1 row matched + 1 row changed)
    }

    private function upsertAirline($data) {
        $sql = "INSERT INTO aviation_airlines
                (iata_code, icao_code, airline_name, country_iso2, country_name,
                 callsign, hub_code, fleet_size, fleet_average_age, status, type,
                 date_founded, aviationstack_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                airline_name = VALUES(airline_name),
                country_iso2 = VALUES(country_iso2),
                country_name = VALUES(country_name),
                callsign = VALUES(callsign),
                hub_code = VALUES(hub_code),
                fleet_size = VALUES(fleet_size),
                fleet_average_age = VALUES(fleet_average_age),
                status = VALUES(status),
                type = VALUES(type),
                aviationstack_id = VALUES(aviationstack_id)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssdsssss",
            $data['iata_code'], $data['icao_code'], $data['airline_name'],
            $data['country_iso2'], $data['country_name'],
            $data['callsign'], $data['hub_code'], $data['fleet_size'],
            $data['fleet_average_age'], $data['status'], $data['type'],
            $data['date_founded'], $data['aviationstack_id']
        );
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($this->conn);
        mysqli_stmt_close($stmt);
        return $affected === 2;
    }

    private function upsertAirplane($data) {
        $sql = "INSERT INTO aviation_airplanes
                (registration_number, production_line, iata_code, aircraft_type_name, model_name,
                 manufacturer, airline_iata, airline_name, plane_owner, plane_age,
                 seats, engines, plane_status, aviationstack_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                iata_code = VALUES(iata_code),
                aircraft_type_name = VALUES(aircraft_type_name),
                model_name = VALUES(model_name),
                manufacturer = VALUES(manufacturer),
                airline_iata = VALUES(airline_iata),
                airline_name = VALUES(airline_name),
                plane_owner = VALUES(plane_owner),
                plane_age = VALUES(plane_age),
                seats = VALUES(seats),
                engines = VALUES(engines),
                plane_status = VALUES(plane_status),
                aviationstack_id = VALUES(aviationstack_id)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssssdiiss",
            $data['registration_number'], $data['production_line'],
            $data['iata_code'], $data['aircraft_type_name'], $data['model_name'],
            $data['manufacturer'], $data['airline_iata'], $data['airline_name'],
            $data['plane_owner'], $data['plane_age'],
            $data['seats'], $data['engines'],
            $data['plane_status'], $data['aviationstack_id']
        );
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($this->conn);
        mysqli_stmt_close($stmt);
        return $affected === 2;
    }

    private function upsertAircraftType($data) {
        $sql = "INSERT INTO aviation_aircraft_types (iata_code, aircraft_name, aviationstack_id)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                aircraft_name = VALUES(aircraft_name),
                aviationstack_id = VALUES(aviationstack_id)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss",
            $data['iata_code'], $data['aircraft_name'], $data['aviationstack_id']
        );
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($this->conn);
        mysqli_stmt_close($stmt);
        return $affected === 2;
    }

    private function upsertCountry($data) {
        $sql = "INSERT INTO aviation_countries
                (iso2, iso3, country_name, capital, continent, currency_code,
                 currency_name, phone_prefix, population, iso_numeric, fips_code, aviationstack_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                country_name = VALUES(country_name),
                capital = VALUES(capital),
                continent = VALUES(continent),
                currency_code = VALUES(currency_code),
                currency_name = VALUES(currency_name),
                phone_prefix = VALUES(phone_prefix),
                population = VALUES(population),
                iso3 = VALUES(iso3),
                aviationstack_id = VALUES(aviationstack_id)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssssisss",
            $data['iso2'], $data['iso3'], $data['country_name'],
            $data['capital'], $data['continent'], $data['currency_code'],
            $data['currency_name'], $data['phone_prefix'], $data['population'],
            $data['iso_numeric'], $data['fips_code'], $data['aviationstack_id']
        );
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($this->conn);
        mysqli_stmt_close($stmt);
        return $affected === 2;
    }

    private function upsertFlightMeta($data) {
        $sql = "INSERT INTO aviation_flights
                (flight_date, flight_status, departure_airport, departure_iata, departure_icao,
                 departure_terminal, departure_gate, departure_scheduled, departure_estimated,
                 departure_actual, departure_delay, arrival_airport, arrival_iata, arrival_icao,
                 arrival_terminal, arrival_gate, arrival_scheduled, arrival_estimated,
                 arrival_actual, arrival_baggage, airline_name, airline_iata, airline_icao,
                 flight_number, flight_iata, flight_icao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                flight_status = VALUES(flight_status),
                departure_terminal = VALUES(departure_terminal),
                departure_gate = VALUES(departure_gate),
                departure_estimated = VALUES(departure_estimated),
                departure_actual = VALUES(departure_actual),
                departure_delay = VALUES(departure_delay),
                arrival_terminal = VALUES(arrival_terminal),
                arrival_gate = VALUES(arrival_gate),
                arrival_estimated = VALUES(arrival_estimated),
                arrival_actual = VALUES(arrival_actual),
                arrival_baggage = VALUES(arrival_baggage)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssssssisssssssssssssss",
            $data['flight_date'], $data['flight_status'],
            $data['departure_airport'], $data['departure_iata'], $data['departure_icao'],
            $data['departure_terminal'], $data['departure_gate'],
            $data['departure_scheduled'], $data['departure_estimated'],
            $data['departure_actual'], $data['departure_delay'],
            $data['arrival_airport'], $data['arrival_iata'], $data['arrival_icao'],
            $data['arrival_terminal'], $data['arrival_gate'],
            $data['arrival_scheduled'], $data['arrival_estimated'],
            $data['arrival_actual'], $data['arrival_baggage'],
            $data['airline_name'], $data['airline_iata'], $data['airline_icao'],
            $data['flight_number'], $data['flight_iata'], $data['flight_icao']
        );
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($this->conn);
        mysqli_stmt_close($stmt);
        return $affected === 2;
    }

    // ─── Report helpers ───

    private function initReport($endpoint) {
        return [
            'endpoint' => $endpoint,
            'success' => false,
            'added' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'total_available' => 0,
            'total_fetched' => 0,
            'error' => null,
            'duration_ms' => 0,
            'start_time' => microtime(true),
        ];
    }

    private function completeReport($report, $result) {
        $report['success'] = true;
        $report['total_available'] = $result['total'] ?? 0;
        $report['total_fetched'] = $result['fetched'] ?? count($result['data'] ?? []);
        $report['duration_ms'] = round((microtime(true) - $report['start_time']) * 1000);
        unset($report['start_time']);

        $this->logSync($report);
        $this->logInfo('Sync completed: ' . $report['endpoint'], $report);
        return $report;
    }

    private function failReport($report, $error) {
        $report['success'] = false;
        $report['error'] = $error;
        $report['duration_ms'] = round((microtime(true) - $report['start_time']) * 1000);
        unset($report['start_time']);

        $this->logSync($report);
        logError('Aviationstack sync failed: ' . $report['endpoint'], [
            'endpoint' => $report['endpoint'],
            'error' => $error,
        ]);
        return $report;
    }

    private function logSync($report) {
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO api_sync_logs
             (provider, endpoint, status, records_added, records_updated,
              records_skipped, records_failed, total_available, total_fetched,
              error_message, duration_ms)
             VALUES ('aviationstack', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $status = $report['success'] ? 'success' : 'failed';
        mysqli_stmt_bind_param($stmt, "ssiiiiiisi",
            $report['endpoint'], $status,
            $report['added'], $report['updated'],
            $report['skipped'], $report['failed'],
            $report['total_available'], $report['total_fetched'],
            $report['error'], $report['duration_ms']
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    private function logInfo($message, $context) {
        logInfo($message, [
            'endpoint' => $context['endpoint'] ?? '',
            'duration_ms' => $context['duration_ms'] ?? 0,
            'added' => $context['added'] ?? 0,
            'updated' => $context['updated'] ?? 0,
        ]);
    }

    // ─── Transaction helpers ───

    private function beginTransaction() {
        mysqli_begin_transaction($this->conn);
    }

    private function commitTransaction() {
        mysqli_commit($this->conn);
    }

    private function rollbackTransaction() {
        mysqli_rollback($this->conn);
    }
}
