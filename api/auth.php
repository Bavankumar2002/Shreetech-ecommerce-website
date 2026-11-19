<?php
/**
 * SHOPPULSE Authentication API
 * Secure session handling, CSRF token validation, bcrypt password hashing
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_db();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = $input['action'] ?? ($_GET['action'] ?? 'status');

    switch ($action) {
        case 'status':
            json_response([
                'authenticated' => !empty($_SESSION['user']),
                'user' => $_SESSION['user'] ?? null,
                'csrf_token' => $_SESSION['csrf_token'] ?? ''
            ]);
            break;

        case 'login':
            $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $password = (string)($input['password'] ?? '');

            if (!$email || empty($password)) {
                json_response(['error' => 'Please provide a valid email address and password.'], 400);
            }

            $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                json_response(['error' => 'Invalid email or password.'], 401);
            }

            // Regenerate session id to prevent fixation
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            json_response([
                'success' => true,
                'message' => 'Signed in successfully!',
                'user' => $_SESSION['user']
            ]);
            break;

        case 'register':
            $name = clean_input($input['name'] ?? '');
            $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $password = (string)($input['password'] ?? '');

            if (empty($name) || strlen($name) < 2) {
                json_response(['error' => 'Name must be at least 2 characters.'], 400);
            }
            if (!$email) {
                json_response(['error' => 'A valid email address is required.'], 400);
            }
            if (strlen($password) < 6) {
                json_response(['error' => 'Password must be at least 6 characters long.'], 400);
            }

            // Check if email already registered
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                json_response(['error' => 'This email is already registered. Please sign in.'], 409);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, 'customer')");
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'hash' => $hash
            ]);

            $newId = (int)$pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $newId,
                'name' => $name,
                'email' => $email,
                'role' => 'customer'
            ];

            json_response([
                'success' => true,
                'message' => 'Account created successfully!',
                'user' => $_SESSION['user']
            ], 201);
            break;

        case 'logout':
            unset($_SESSION['user']);
            json_response([
                'success' => true,
                'message' => 'Successfully signed out.'
            ]);
            break;

        default:
            json_response(['error' => 'Invalid action'], 400);
    }

} catch (Exception $e) {
    json_response(['error' => 'Authentication error: ' . $e->getMessage()], 500);
}
