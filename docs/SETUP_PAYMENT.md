# Demo Payment System

AeroBook uses a built-in **Demo Payment System** for all bookings. No external payment gateway is configured or required.

## How It Works

1. During checkout, the user enters card details in the booking form
2. The server validates the card format (16-digit number, 3-digit CVV)
3. No real charge is made — payment is simulated
4. The booking is confirmed and the user is redirected to the confirmation page

## Card Validation Rules

| Field | Format | Example |
|-------|--------|---------|
| Card Number | 16 digits | `4111222233334444` |
| CVV | 3 digits | `123` |

## Security Notes

- AeroBook **never stores**, logs, or transmits actual card details
- Card input is accepted only for format validation during the demo flow
- All card numbers are discarded after the request completes
- The system clearly indicates "This is a simulated payment. No real charges will be made."

## No Payment Gateway Integration

This project intentionally does **not** integrate with any external payment gateway:

- No Razorpay
- No Stripe
- No PayPal
- No other payment provider

The Demo Payment system is the **only** payment method. This keeps the project:
- Simple to deploy (no API keys needed)
- Safe for demonstration (no financial risk)
- Easy to audit (no sensitive payment data flows)
- Compatible with shared hosting (no HTTPS requirement for payment processing)

## Production Considerations

If you need real payments in production:

1. Replace the card validation in `includes/Validation.php` (`validatePayment()`)
2. Integrate a payment gateway of your choice in the booking submission handler in `booking.php`
3. Never store card numbers or security codes in your database
4. Ensure PCI DSS compliance for your chosen payment flow

---

[⬅ Back to Configuration](CONFIGURATION.md)
