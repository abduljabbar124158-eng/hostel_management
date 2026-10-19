<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli('localhost', 'root', '', 'hostel_management');
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed. Check that MySQL is running and database.sql has been imported.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            exit('Invalid request token. Please go back, refresh the page, and try again.');
        }
    }
}
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
function active_occupancy(mysqli $conn, int $roomId): int {
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM allocations WHERE room_id=? AND release_date IS NULL');
    $stmt->bind_param('i', $roomId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['total'];
}
