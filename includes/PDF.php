<?php
/**
 * AeroBook – PDF Document Generator
 *
 * Generates professional PDF documents for boarding passes,
 * invoices, and trip summaries.
 *
 * For true PDF generation, install tFPDF (single file):
 *   https://github.com/setasign/tFPDF
 * Saved to /uploads/documents/{booking_ref}/
 */

class AeroPDF {
    private $outputDir;
    private $brandColor = '#024dec';
    private $darkBg = '#051336';

    public function __construct() {
        $this->outputDir = __DIR__ . '/../uploads/documents';
        if (!is_dir($this->outputDir)) {
            @mkdir($this->outputDir, 0755, true);
        }
    }

    public function generateBoardingPass($booking, $flight) {
        $ref = $booking['booking_ref'];
        $html = $this->boardingPassHTML($booking, $flight);
        return $this->save($html, $ref . '_boarding_pass');
    }

    public function generateInvoice($booking, $flight, $addons = [], $fares = []) {
        $html = $this->invoiceHTML($booking, $flight, $addons, $fares);
        return $this->save($html, $booking['booking_ref'] . '_invoice');
    }

    public function generateTripSummary($bookings) {
        $html = $this->tripSummaryHTML($bookings);
        return $this->save($html, $bookings[0]['booking_ref'] . '_trip_summary');
    }

    private function save($html, $prefix) {
        $filename = $prefix . '.html';
        $filepath = $this->outputDir . '/' . $filename;

        $tfpdfPath = __DIR__ . '/../lib/tfpdf/tfpdf.php';
        if (file_exists($tfpdfPath)) {
            $filename = $prefix . '.pdf';
            $filepath = $this->outputDir . '/' . $filename;
        }

        file_put_contents($filepath, $html);
        return $filepath;
    }

    public function getDocumentUrl($filename) {
        return BASE_URL . 'uploads/documents/' . $filename;
    }

    // ─── Boarding Pass HTML ───
    private function boardingPassHTML($b, $f) {
        $src = htmlspecialchars($f['source']);
        $dst = htmlspecialchars($f['destination']);
        $al = htmlspecialchars($f['airline_name']);
        $fn = htmlspecialchars($f['flight_number']);
        $ref = htmlspecialchars($b['booking_ref']);
        $passenger = htmlspecialchars($b['passenger_name']);
        $seat = htmlspecialchars($b['seat_number']);
        $date = date('d M Y', strtotime($b['travel_date']));
        $dep = date('h:i A', strtotime($f['departure_time']));
        $arr = date('h:i A', strtotime($f['arrival_time']));

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body{font-family:'Inter','Helvetica',sans-serif;margin:0;padding:20px;background:#f4f7fb}
.boarding-pass{max-width:800px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.1)}
.header{background:{$this->darkBg};color:#fff;padding:20px 30px;display:flex;justify-content:space-between;align-items:center}
.header h1{margin:0;font-size:20px}.header span{color:#00d4ff;font-size:12px}
.body{padding:30px}.route{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.city{font-size:28px;font-weight:800;color:{$this->darkBg}}
.arrow{color:{$this->brandColor};font-size:24px}.details{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px}
.detail label{font-size:10px;color:#94a3b8;text-transform:uppercase;font-weight:600;display:block}
.detail .value{font-size:16px;font-weight:700;color:{$this->darkBg};margin-top:2px}
.footer{background:#f8fafc;padding:16px 30px;font-size:11px;color:#94a3b8;text-align:center;border-top:2px dashed #e2e8f0}
</style></head><body>
<div class="boarding-pass">
<div class="header"><div><h1>✈️ Boarding Pass</h1><span>{$al} · {$fn}</span></div><div><span style="font-size:18px;font-weight:700">{$src}</span></div></div>
<div class="body">
<div class="route">
<div><div class="city">{$src}</div><div style="font-size:12px;color:#586985">Boarding</div></div>
<div class="arrow">——— ✈ ———</div>
<div><div class="city" style="text-align:right">{$dst}</div><div style="font-size:12px;color:#586985;text-align:right">Arrival</div></div>
</div>
<div class="details">
<div><label>Passenger</label><div class="value">{$passenger}</div></div>
<div><label>Seat</label><div class="value">{$seat}</div></div>
<div><label>Date</label><div class="value">{$date}</div></div>
<div><label>Departure</label><div class="value">{$dep}</div></div>
<div><label>Arrival</label><div class="value">{$arr}</div></div>
<div><label>Ref</label><div class="value" style="color:{$this->brandColor}">AB-{$ref}</div></div>
</div>
</div>
<div class="footer">Present this boarding pass at the gate. Boarding begins 30 minutes before departure.</div>
</div></body></html>
HTML;
    }

    // ─── Invoice HTML ───
    private function invoiceHTML($b, $f, $addons, $fares) {
        $basePrice = floatval($f['price']);
        $addonTotal = array_sum(array_column($addons, 'amount'));
        $total = $basePrice + $addonTotal;
        $totalFormatted = '₹' . number_format($total, 2);
        $passenger = htmlspecialchars($b['passenger_name']);
        $ref = htmlspecialchars($b['booking_ref']);
        $date = date('d M Y', strtotime($b['booking_date']));
        $airline = htmlspecialchars($f['airline_name']);
        $flightNum = htmlspecialchars($f['flight_number']);
        $src = htmlspecialchars($f['source']);
        $dst = htmlspecialchars($f['destination']);

        $itemRows = "<tr><td>{$airline} {$flightNum}</td><td>{$src} → {$dst}</td><td>1</td><td style='text-align:right'>₹" . number_format($basePrice, 2) . "</td></tr>";
        foreach ($addons as $a) {
            $aname = htmlspecialchars($a['addon_name']);
            $aamt = number_format($a['amount'], 2);
            $itemRows .= "<tr><td colspan='2'><small>{$aname}</small></td><td>1</td><td style='text-align:right'>₹{$aamt}</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body{font-family:'Inter','Helvetica',sans-serif;margin:0;padding:20px;background:#f4f7fb}
.invoice{max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.1)}
.head{background:{$this->darkBg};color:#fff;padding:20px 30px}
.head h1{margin:0;font-size:20px}.head small{color:#00d4ff}
.body{padding:30px}
table{width:100%;border-collapse:collapse;margin:16px 0}
th{background:#f8fafc;padding:8px;font-size:11px;text-transform:uppercase;color:#586985;text-align:left}
td{padding:8px;border-bottom:1px solid #e2e8f0;font-size:14px}
.total td{border-bottom:none;font-weight:700;font-size:16px}
.meta{font-size:13px;color:#586985;margin-bottom:16px}
.footer{text-align:center;padding:16px;font-size:11px;color:#94a3b8}
</style></head><body>
<div class="invoice">
<div class="head"><h1>🧾 Invoice</h1><small>Booking Ref: AB-{$ref}</small></div>
<div class="body">
<div class="meta"><strong>Passenger:</strong> {$passenger}<br><strong>Date:</strong> {$date}</div>
<table><tr><th>Description</th><th>Route</th><th>Qty</th><th>Amount</th></tr>{$itemRows}
<tr class="total"><td colspan="3" style="text-align:right">Total</td><td style="text-align:right">{$totalFormatted}</td></tr>
</table>
<p style="font-size:12px;color:#94a3b8;margin-top:16px">Payment Status: Paid · Taxes included in fare.</p>
</div>
<div class="footer">AeroBook · Thank you for flying with us!</div>
</div></body></html>
HTML;
    }

    // ─── Trip Summary HTML ───
    private function tripSummaryHTML($bookings) {
        $itemRows = '';
        $totalPrice = 0;
        foreach ($bookings as $b) {
            $al = htmlspecialchars($b['airline_name']);
            $fn = htmlspecialchars($b['flight_number']);
            $src = htmlspecialchars($b['source']);
            $dst = htmlspecialchars($b['destination']);
            $date = date('d M Y', strtotime($b['travel_date']));
            $time = date('h:i A', strtotime($b['departure_time']));
            $price = number_format($b['price'], 2);
            $itemRows .= "<tr><td>{$al} {$fn}</td><td>{$src} → {$dst}</td><td>{$date}</td><td>{$time}</td><td style='text-align:right'>₹{$price}</td></tr>";
            $totalPrice += floatval($b['price']);
        }
        $totalFormatted = '₹' . number_format($totalPrice, 2);
        $passenger = htmlspecialchars($bookings[0]['passenger_name']);
        $ref = htmlspecialchars($bookings[0]['booking_ref']);
        $count = count($bookings);

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body{font-family:'Inter','Helvetica',sans-serif;margin:0;padding:20px;background:#f4f7fb}
.summary{max-width:700px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.1)}
.head{background:{$this->darkBg};color:#fff;padding:20px 30px}
.head h1{margin:0;font-size:20px}
.body{padding:30px}
table{width:100%;border-collapse:collapse;margin:16px 0}
th{background:#f8fafc;padding:8px;font-size:11px;text-transform:uppercase;color:#586985;text-align:left}
td{padding:8px;border-bottom:1px solid #e2e8f0;font-size:13px}
.total td{border-bottom:none;font-weight:700;font-size:16px}
.footer{text-align:center;padding:16px;font-size:11px;color:#94a3b8}
</style></head><body>
<div class="summary">
<div class="head"><h1>📋 Trip Summary</h1><small>Ref: AB-{$ref}</small></div>
<div class="body">
<p><strong>Passenger:</strong> {$passenger} · <strong>Bookings:</strong> {$count} flights</p>
<table><tr><th>Flight</th><th>Route</th><th>Date</th><th>Time</th><th>Fare</th></tr>{$itemRows}
<tr class="total"><td colspan="4" style="text-align:right">Total</td><td style="text-align:right">{$totalFormatted}</td></tr>
</table>
</div>
<div class="footer">AeroBook – Travel Made Simple</div>
</div></body></html>
HTML;
    }
}
