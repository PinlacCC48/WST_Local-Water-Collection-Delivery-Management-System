<?php
/* ===========================
   CSRF helper for customer-side db connections
   returns the session token (creates it once)
   blocks any POST without a valid token
    puts the token into every <form method="POST">
   ========================================================= */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('majesticCsrfToken')) {
    function majesticCsrfToken(): string {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('majesticCsrfCheck')) {
    function majesticCsrfCheck(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }
        $sent = (string)($_POST['csrf_token'] ?? '');
        if ($sent === '' || !hash_equals(majesticCsrfToken(), $sent)) {
            http_response_code(403);
            die('Invalid or expired form. Please go back, refresh the page, and try again.');
        }
    }
}

if (!function_exists('majesticCsrfInject')) {
    function majesticCsrfInject(string $html, string $token): string {
        // remove any old/hard-coded token inputs, then add the real one to every POST form
        $html  = preg_replace('/<input\b[^>]*name\s*=\s*["\']csrf_token["\'][^>]*>\s*/i', '', $html) ?? $html;
        $field = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
        return preg_replace_callback(
            '/<form\b[^>]*\bmethod\s*=\s*["\']?post["\']?[^>]*>/i',
            static fn(array $m): string => $m[0] . $field,
            $html
        ) ?? $html;
    }
}
