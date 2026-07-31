<?php
/**
 * AeroBook – Token Helper (CSRF protection for GET-based admin actions)
 *
 * Generates, validates, and cleans up one-time delete tokens.
 * Tokens expire after 15 minutes.
 */

if (!defined('AEROBOOK_TOKEN_HELPER')) {

function generateDeleteToken() {
    cleanupDeleteTokens();
    $token = bin2hex(random_bytes(16));
    $_SESSION['delete_tokens'][$token] = time() + 900; // 15 min expiry
    return $token;
}

function validateDeleteToken($token, $clear = true) {
    if (empty($token) || !isset($_SESSION['delete_tokens'][$token])) {
        return false;
    }
    if ($_SESSION['delete_tokens'][$token] < time()) {
        unset($_SESSION['delete_tokens'][$token]);
        return false;
    }
    if ($clear) {
        unset($_SESSION['delete_tokens'][$token]);
    }
    return true;
}

function deleteLink($baseUrl, $param, $value) {
    $token = generateDeleteToken();
    return BASE_URL . $baseUrl . '?' . $param . '=' . $value . '&token=' . $token;
}

function cleanupDeleteTokens() {
    $now = time();
    if (isset($_SESSION['delete_tokens']) && count($_SESSION['delete_tokens']) > 100) {
        foreach ($_SESSION['delete_tokens'] as $tk => $exp) {
            if ($exp < $now || count($_SESSION['delete_tokens']) > 100) {
                unset($_SESSION['delete_tokens'][$tk]);
            }
        }
    }
}

define('AEROBOOK_TOKEN_HELPER', true);
}
