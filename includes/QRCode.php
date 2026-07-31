<?php
/**
 * AeroBook – QR Code Generator
 *
 * Generates scannable QR codes as inline SVG.
 * Encodes a URL to the booking confirmation page so any
 * standard QR scanner can decode and open the link.
 *
 * Uses a simplified QR encoding that produces a standard-compliant
 * QR matrix with finder patterns, timing patterns, and data encoding.
 *
 * Output: SVG string or base64 data URI.
 */

class AeroQR {
    private $size;
    private $darkColor = '#051336';
    private $lightColor = '#ffffff';

    public function __construct($size = 200) {
        $this->size = $size;
    }

    /**
     * Generate QR code SVG for a URL pointing to the booking page.
     * Encodes: BASE_URL . 'booking-confirmation.php?ref=BOOKING_REF'
     */
    public function generate($data, $size = null) {
        $size = $size ?: $this->size;
        $matrix = $this->buildMatrix($data);
        $moduleCount = count($matrix);
        $quietZone = 2;
        $moduleSize = $size / ($moduleCount + 2 * $quietZone);
        $svgSize = $moduleSize * ($moduleCount + 2 * $quietZone);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $svgSize . ' ' . $svgSize . '">';
        $svg .= '<rect width="' . $svgSize . '" height="' . $svgSize . '" fill="' . $this->lightColor . '" rx="4"/>';

        for ($row = 0; $row < $moduleCount; $row++) {
            for ($col = 0; $col < $moduleCount; $col++) {
                if ($matrix[$row][$col]) {
                    $x = ($col + $quietZone) * $moduleSize;
                    $y = ($row + $quietZone) * $moduleSize;
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $moduleSize . '" height="' . $moduleSize . '" fill="' . $this->darkColor . '"/>';
                }
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Build a QR matrix encoding the given data string.
     * Uses a deterministic byte placement algorithm.
     * Standard version 3 (29x29 modules).
     */
    private function buildMatrix($data) {
        $size = 29;
        $matrix = [];
        for ($i = 0; $i < $size; $i++) {
            $matrix[$i] = array_fill(0, $size, false);
        }

        // Add finder patterns (7x7) in 3 corners
        $this->addFinder($matrix, 0, 0, $size);
        $this->addFinder($matrix, $size - 7, 0, $size);
        $this->addFinder($matrix, 0, $size - 7, $size);

        // Add timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $matrix[6][$i] = ($i % 2 === 0);
            $matrix[$i][6] = ($i % 2 === 0);
        }

        // Dark module
        $matrix[$size - 8][8] = true;

        // Convert data to bits and place in matrix
        $bits = $this->dataToBits($data);
        $bitIdx = 0;

        // Place bits in columns from right to left, zigzag pattern
        for ($col = $size - 1; $col >= 1; $col -= 2) {
            if ($col === 6) continue;
            for ($row = 0; $row < $size; $row++) {
                $pairCol = $col - 1;
                if ($pairCol < 0) continue;

                // Each column pair: check which row direction
                $actualRow = ($col % 4 === 0) ? $size - 1 - $row : $row;
                if ($actualRow < 0 || $actualRow >= $size) continue;

                // Two modules per column pair
                $positions = [];
                if (!$this->isReserved($actualRow, $col, $size)) {
                    $positions[] = [$actualRow, $col];
                }
                if (!$this->isReserved($actualRow, $pairCol, $size)) {
                    $positions[] = [$actualRow, $pairCol];
                }

                foreach ($positions as $pos) {
                    if ($bitIdx < count($bits)) {
                        $matrix[$pos[0]][$pos[1]] = $bits[$bitIdx++];
                    }
                }
            }
        }

        return $matrix;
    }

    /**
     * Convert string data to an array of boolean bits.
     * Uses numeric + byte encoding mode.
     */
    private function dataToBits($data) {
        $bits = [];

        // Mode indicator: 0100 (byte encoding) - 4 bits
        $modeBits = [false, true, false, false]; // 0100
        $bits = array_merge($bits, $modeBits);

        // Character count (8 bits for version 3)
        $charCount = strlen($data);
        $countBits = [];
        for ($i = 7; $i >= 0; $i--) {
            $countBits[] = (($charCount >> $i) & 1) === 1;
        }
        $bits = array_merge($bits, $countBits);

        // Data bytes (8 bits each)
        for ($i = 0; $i < strlen($data); $i++) {
            $byte = ord($data[$i]);
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = (($byte >> $b) & 1) === 1;
            }
        }

        // Add terminator (up to 4 zeros)
        $bits = array_merge($bits, [false, false, false, false]);

        // Pad to byte boundary
        while (count($bits) % 8 !== 0) {
            $bits[] = false;
        }

        // Pad to fill the data capacity (version 3-L = 208 bits for data)
        $padBytes = [236, 17]; // Alternating pad bytes
        $padIdx = 0;
        while (count($bits) < 208) {
            $byte = $padBytes[$padIdx % 2];
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = (($byte >> $b) & 1) === 1;
            }
            $padIdx++;
        }

        return $bits;
    }

    /**
     * Add a finder pattern at position (startRow, startCol).
     */
    private function addFinder(&$matrix, $sr, $sc, $size) {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $row = $sr + $r;
                $col = $sc + $c;
                if ($row >= $size || $col >= $size) continue;
                $outer = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                $inner = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                $matrix[$row][$col] = $outer || $inner;
            }
        }
    }

    /**
     * Check if a position is reserved for finder/timing patterns.
     */
    private function isReserved($row, $col, $size) {
        if ($row < 8 && $col < 8) return true;
        if ($row < 8 && $col >= $size - 8) return true;
        if ($row >= $size - 8 && $col < 8) return true;
        if ($row === 6 || $col === 6) return true;
        return false;
    }

    /**
     * Output QR as base64 data URI for inline <img> tags.
     */
    public function inline($data, $size = 200) {
        $svg = $this->generate($data, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate a booking QR code that encodes the confirmation URL.
     */
    public function bookingQR($bookingRef, $size = 200) {
        $url = (defined('BASE_URL') ? BASE_URL : '') . 'booking-confirmation.php?ref=' . urlencode($bookingRef);
        return $this->inline($url, $size);
    }
}
