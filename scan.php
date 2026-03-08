<?php
/**
 * QR Code Scan Entry Point - Hôtel Corintel
 *
 * When a guest scans the QR code in their room, they land here.
 * The token is validated, the room context is stored in session,
 * and the guest is redirected to the main website — with room service
 * automatically unlocked for their session.
 *
 * Access: /scan.php?room=X&token=TOKEN
 */

require_once __DIR__ . '/includes/functions.php';

// Session must be started before we can write to it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$roomId = isset($_GET['room']) ? (int)$_GET['room'] : null;
$token  = $_GET['token'] ?? null;

if ($roomId && $token) {
    // Auto-detect browser language on first QR scan (before any redirect)
    if (!isset($_COOKIE['hotel_corintel_lang'])) {
        $detectedLang = detectBrowserLanguage();
        if ($detectedLang) {
            setcookie('hotel_corintel_lang', $detectedLang, time() + (365 * 24 * 60 * 60), '/');
        }
    }

    // Validate the HMAC token against the room
    $validation = validateRoomServiceAccess($roomId, $token);

    if ($validation['valid']) {
        // Store room context in session — this unlocks room service site-wide
        setRoomServiceSession($validation['room']);

        // Log the QR scan once per session per room (analytics)
        $scanKey = 'qr_scan_logged_' . $roomId;
        if (empty($_SESSION[$scanKey])) {
            logQrScan($roomId);
            $_SESSION[$scanKey] = time();
        }
    }
    // If invalid: redirect without setting session — room service stays locked
}

// Always redirect to the home page
header('Location: index.php');
exit;
