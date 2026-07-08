<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$roomId = post_int('room_id');
$csrf = (string) ($_POST['csrf_token'] ?? '');

if (!verify_csrf_token($csrf, 'favorite')) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'invalid_token']);
    exit;
}

if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'auth_required',
        'login_url' => url_for('login.php'),
        'register_url' => url_for('register.php'),
    ]);
    exit;
}

if (!user_has_group('student')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'student_required']);
    exit;
}

if ($roomId <= 0 || (new RoomRepository())->find($roomId) === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

$favorite = toggle_favorite($roomId);

echo json_encode(['ok' => true, 'favorite' => $favorite, 'count' => count(current_favorite_ids())]);
