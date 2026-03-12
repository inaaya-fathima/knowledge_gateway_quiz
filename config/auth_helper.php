<?php
// config/auth_helper.php
// JSON-based authentication + star reward tracking + strong password validation

define('USERS_JSON_PATH', __DIR__ . '/../data/users.json');

// ── Initialize users.json if missing ──────────────────────────
if (!file_exists(USERS_JSON_PATH)) {
    if (!is_dir(dirname(USERS_JSON_PATH))) {
        mkdir(dirname(USERS_JSON_PATH), 0777, true);
    }
    file_put_contents(USERS_JSON_PATH, json_encode([
        'users'  => [],
        'admins' => []
    ], JSON_PRETTY_PRINT));
}

// ── Password strength constants ────────────────────────────────
define('PW_MIN_LENGTH',     8);
define('PW_BCRYPT_COST',   12);   // Higher cost = harder to brute-force

/**
 * Validate password strength.
 * Returns an array of error strings (empty = password is strong).
 *
 * Rules:
 *  - Minimum 8 characters
 *  - At least one uppercase letter (A–Z)
 *  - At least one lowercase letter (a–z)
 *  - At least one digit (0–9)
 *  - At least one special character (!@#$%^&* etc.)
 */
function validate_password_strength(string $password): array {
    $errors = [];

    if (strlen($password) < PW_MIN_LENGTH) {
        $errors[] = "At least " . PW_MIN_LENGTH . " characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "At least one uppercase letter (A–Z)";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "At least one lowercase letter (a–z)";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "At least one number (0–9)";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "At least one special character (!@#\$%^&*...)";
    }

    return $errors;
}

/**
 * Returns a score 0–4 for the password strength meter.
 */
function password_strength_score(string $password): int {
    $score = 0;
    if (strlen($password) >= PW_MIN_LENGTH)          $score++;
    if (preg_match('/[A-Z]/', $password))             $score++;
    if (preg_match('/[a-z]/', $password))             $score++;
    if (preg_match('/[0-9]/', $password))             $score++;
    if (preg_match('/[^A-Za-z0-9]/', $password))     $score++;
    return (int)ceil($score * 4 / 5); // normalize to 0–4
}

// ── Read / Write helpers ───────────────────────────────────────
function get_all_users(): array {
    $json = file_get_contents(USERS_JSON_PATH);
    return json_decode($json, true) ?: ['users' => [], 'admins' => []];
}

function save_all_users(array $data): bool {
    return file_put_contents(USERS_JSON_PATH, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// ── Register new user ──────────────────────────────────────────
/**
 * @param string $username   Unique identifier
 * @param string $password   Plain-text password (already validated)
 * @param string $role       'user' | 'admin'
 * @param array  $extraData  Additional fields (db_id, name, etc.)
 * @return bool  True on success, false if username taken
 */
function register_json_user(string $username, string $password, string $role = 'user', array $extraData = []): bool {
    $data     = get_all_users();
    $category = ($role === 'admin') ? 'admins' : 'users';

    // Case-insensitive username uniqueness check
    foreach ($data[$category] as $user) {
        if (strtolower($user['username']) === strtolower($username)) {
            return false;
        }
    }

    $data[$category][] = array_merge([
        'username'        => $username,
        'password'        => password_hash($password, PASSWORD_BCRYPT, ['cost' => PW_BCRYPT_COST]),
        'role'            => $role,
        'created_at'      => date('Y-m-d H:i:s'),
        'stars'           => 0,
        'last_active_day' => '',
        'streak_days'     => 0,
    ], $extraData);

    return save_all_users($data);
}

// ── Authenticate user ──────────────────────────────────────────
/**
 * @return array|false  User array (without password) on success, false on failure
 */
function authenticate_json_user(string $username, string $password, string $role = 'user') {
    $data     = get_all_users();
    $category = ($role === 'admin') ? 'admins' : 'users';

    foreach ($data[$category] as &$user) {
        if (strtolower($user['username']) !== strtolower($username)) continue;

        // Support old PASSWORD_DEFAULT hashes — re-hash transparently on next login
        if (password_verify($password, $user['password'])) {

            // Upgrade hash to bcrypt cost 12 if it was stored with a different algo
            if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => PW_BCRYPT_COST])) {
                $user['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => PW_BCRYPT_COST]);
            }

            // Update streak on login
            $today   = date('Y-m-d');
            $lastDay = $user['last_active_day'] ?? '';
            $streak  = (int)($user['streak_days']  ?? 0);

            if ($lastDay === $today) {
                // Same day — no change
            } elseif ($lastDay === date('Y-m-d', strtotime('-1 day'))) {
                // Consecutive day — extend streak + bonus star
                $user['streak_days']     = $streak + 1;
                $user['last_active_day'] = $today;
                $user['stars']           = ((int)($user['stars'] ?? 0)) + 1;
            } else {
                // Streak broken — reset to 1
                $user['streak_days']     = 1;
                $user['last_active_day'] = $today;
            }

            save_all_users($data);

            $safe = $user;
            unset($safe['password']);
            return $safe;
        }
    }
    return false;
}

// ── Star system ────────────────────────────────────────────────

/**
 * Get star count for a user by their DB id.
 */
function get_user_stars(int $db_id): int {
    $data = get_all_users();
    foreach ($data['users'] as $u) {
        if (isset($u['db_id']) && (int)$u['db_id'] === $db_id) {
            return (int)($u['stars'] ?? 0);
        }
    }
    return 0;
}

/**
 * Add stars to a user.
 */
function add_user_stars(int $db_id, int $amount): void {
    if ($amount <= 0) return;
    $data = get_all_users();
    foreach ($data['users'] as &$u) {
        if (isset($u['db_id']) && (int)$u['db_id'] === $db_id) {
            $u['stars'] = ((int)($u['stars'] ?? 0)) + $amount;
            break;
        }
    }
    save_all_users($data);
}

/**
 * Get current streak days for a user.
 */
function get_user_streak(int $db_id): int {
    $data = get_all_users();
    foreach ($data['users'] as $u) {
        if (isset($u['db_id']) && (int)$u['db_id'] === $db_id) {
            return (int)($u['streak_days'] ?? 0);
        }
    }
    return 0;
}
?>