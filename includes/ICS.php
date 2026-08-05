<?php
/**
 * AeroBook – Calendar Export (ICS)
 *
 * Generates downloadable ICS calendar files compatible with
 * Google Calendar, Apple Calendar, and Microsoft Outlook.
 *
 * Usage:
 *   $ics = new AeroICS();
 *   $ics->download($booking, $flight); // Triggers file download
 *
 * Or get Google Calendar link:
 *   $url = AeroICS::googleCalLink($booking, $flight);
 */

class AeroICS {

    /**
     * Generate ICS content for a booking.
     */
    public function generate($booking, $flight) {
        $uid = 'aerobook-' . $booking['booking_ref'] . '@aerobook.in';
        $dtStart = date('Ymd\THis', strtotime($flight['departure_time']));
        $dtEnd = date('Ymd\THis', strtotime($flight['arrival_time']));
        $created = date('Ymd\THis\Z', strtotime($booking['booking_date']));
        $summary = 'Flight ' . $flight['airline_name'] . ' ' . $flight['flight_number'] . ': ' . $flight['source'] . ' → ' . $flight['destination'];
        $location = $flight['source'] . ' Airport → ' . $flight['destination'] . ' Airport';
        $desc = "Booking Reference: AB-" . $booking['booking_ref'] . "\n"
              . "Airline: " . $flight['airline_name'] . " (" . $flight['flight_number'] . ")\n"
              . "Route: " . $flight['source'] . " → " . $flight['destination'] . "\n"
              . "Date: " . date('d M Y', strtotime($flight['departure_time'])) . "\n"
              . "Departure: " . date('h:i A', strtotime($flight['departure_time'])) . "\n"
              . "Arrival: " . date('h:i A', strtotime($flight['arrival_time'])) . "\n"
              . "Seat: " . $booking['seat_number'] . "\n"
              . "Passenger: " . $booking['passenger_name'];

        $ics = "BEGIN:VCALENDAR\r\n"
             . "VERSION:2.0\r\n"
             . "PRODID:-//AeroBook//Flight Booking//EN\r\n"
             . "CALSCALE:GREGORIAN\r\n"
             . "METHOD:PUBLISH\r\n"
             . "BEGIN:VEVENT\r\n"
             . "UID:{$uid}\r\n"
             . "DTSTART:{$dtStart}\r\n"
             . "DTEND:{$dtEnd}\r\n"
             . "DTSTAMP:{$created}\r\n"
             . "CREATED:{$created}\r\n"
             . "SUMMARY:" . $this->escape($summary) . "\r\n"
             . "DESCRIPTION:" . $this->escape($desc) . "\r\n"
             . "LOCATION:" . $this->escape($location) . "\r\n"
             . "STATUS:CONFIRMED\r\n"
             . "PRIORITY:5\r\n"
             . "BEGIN:VALARM\r\n"
             . "TRIGGER:-PT24H\r\n"
             . "ACTION:DISPLAY\r\n"
             . "DESCRIPTION:Reminder: Flight tomorrow\r\n"
             . "END:VALARM\r\n"
             . "END:VEVENT\r\n"
             . "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Force download of ICS file.
     */
    public function download($booking, $flight) {
        $ics = $this->generate($booking, $flight);
        $filename = 'flight-' . $booking['booking_ref'] . '.ics';

        if (ob_get_length()) ob_clean();
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($ics));
        echo $ics;
        exit;
    }

    /**
     * Generate a Google Calendar link for the booking.
     */
    public static function googleCalLink($booking, $flight) {
        $dtStart = date('Ymd\THis', strtotime($flight['departure_time']));
        $dtEnd = date('Ymd\THis', strtotime($flight['arrival_time']));
        $text = 'Flight ' . $flight['airline_name'] . ' ' . $flight['flight_number'] . ': ' . $flight['source'] . ' → ' . $flight['destination'];
        // booking_ref already includes the 'AB-' prefix; don't duplicate it
        $details = "Booking Ref: " . $booking['booking_ref'] . "%0A"
                 . "Seat: " . $booking['seat_number'] . "%0A"
                 . "Passenger: " . $booking['passenger_name'];
        $location = $flight['source'] . ' Airport';

        return 'https://calendar.google.com/calendar/render?action=TEMPLATE'
             . '&text=' . urlencode($text)
             . '&dates=' . $dtStart . '/' . $dtEnd
             . '&details=' . $details
             . '&location=' . urlencode($location)
             . '&sf=true&output=xml';
    }

    /**
     * Escape special characters for ICS format.
     */
    private function escape($str) {
        $str = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $str);
        // Fold long lines (max 75 chars per line)
        $lines = str_split($str, 70);
        return implode("\r\n ", $lines);
    }
}
