<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $driver = env('DB_DRIVER', DB_DRIVER ?: 'sqlite');
    if ($driver === 'mysql') {
        $pdo = new PDO('mysql:host=' . env('DB_HOST','127.0.0.1') . ';port=' . env('DB_PORT','3306') . ';dbname=' . env('DB_NAME','chorale') . ';charset=utf8mb4', env('DB_USER','chorale'), env('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    } else {
        $pdo = new PDO('sqlite:' . env('DB_PATH', DB_PATH), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

function is_mysql(): bool { return db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'; }
function sql_now(): string { return is_mysql() ? 'NOW()' : "datetime('now')"; }
function sql_full_name(string $alias): string { return is_mysql() ? "CONCAT($alias.first_name, ' ', $alias.last_name)" : "$alias.first_name || ' ' || $alias.last_name"; }

function initialize_database(): void {
    $pdo = db();
    if (is_mysql()) { initialize_mysql($pdo); seed_admin($pdo); return; }
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
 id INTEGER PRIMARY KEY, email TEXT NOT NULL UNIQUE COLLATE NOCASE, first_name TEXT NOT NULL DEFAULT '', last_name TEXT NOT NULL DEFAULT '', phone TEXT,
 password_hash TEXT, role TEXT NOT NULL DEFAULT 'member' CHECK(role IN ('admin','member')), status TEXT NOT NULL DEFAULT 'invited' CHECK(status IN ('invited','active','disabled')),
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, last_login_at TEXT
);
CREATE TABLE IF NOT EXISTS profiles (
 user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE, address TEXT, latitude REAL, longitude REAL, approx_latitude REAL, approx_longitude REAL,
 availability TEXT NOT NULL DEFAULT 'passenger' CHECK(availability IN ('driver','passenger','both')), seats INTEGER NOT NULL DEFAULT 0 CHECK(seats >= 0), detour TEXT, comment TEXT, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS events (
 id INTEGER PRIMARY KEY, title TEXT NOT NULL, type TEXT NOT NULL CHECK(type IN ('repetition','concert')), starts_at TEXT NOT NULL, location TEXT NOT NULL, details TEXT, status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','cancelled')), created_by INTEGER NOT NULL REFERENCES users(id), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS invitations (
 id INTEGER PRIMARY KEY, email TEXT NOT NULL COLLATE NOCASE, token_hash TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','used','expired')), created_by INTEGER NOT NULL REFERENCES users(id), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, used_at TEXT
);
CREATE TABLE IF NOT EXISTS password_resets (
 id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, token_hash TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL, used_at TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS carpool_requests (
 id INTEGER PRIMARY KEY, event_id INTEGER NOT NULL REFERENCES events(id), requester_id INTEGER NOT NULL REFERENCES users(id), recipient_id INTEGER NOT NULL REFERENCES users(id), pickup_place TEXT NOT NULL, pickup_time TEXT NOT NULL, message TEXT, status TEXT NOT NULL DEFAULT 'en_attente' CHECK(status IN ('en_attente','acceptee','refusee','contre_proposition','annulee')), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, CHECK(requester_id != recipient_id)
);
CREATE TABLE IF NOT EXISTS request_responses (
 id INTEGER PRIMARY KEY, request_id INTEGER NOT NULL REFERENCES carpool_requests(id) ON DELETE CASCADE, author_id INTEGER NOT NULL REFERENCES users(id), decision TEXT NOT NULL CHECK(decision IN ('acceptee','refusee','contre_proposition','annulee')), message TEXT, proposed_place TEXT, proposed_time TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS notification_log (
 id INTEGER PRIMARY KEY, recipient TEXT NOT NULL, type TEXT NOT NULL, subject TEXT NOT NULL, status TEXT NOT NULL, error TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_events_starts ON events(starts_at);
CREATE INDEX IF NOT EXISTS idx_requests_recipient ON carpool_requests(recipient_id, status);
CREATE INDEX IF NOT EXISTS idx_requests_requester ON carpool_requests(requester_id, status);
CREATE INDEX IF NOT EXISTS idx_invitation_email ON invitations(email, status);
SQL);
    seed_admin($pdo);
}

function seed_admin(PDO $pdo): void {
    $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?'); $exists->execute(['admin@chorale.test']);
    if (!$exists->fetch()) {
        $pdo->prepare("INSERT INTO users(email,first_name,last_name,password_hash,role,status) VALUES(?,?,?,?, 'admin','active')")
            ->execute(['admin@chorale.test','Admin','Chorale',password_hash('ChangeMe!2026', PASSWORD_DEFAULT)]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO profiles(user_id, availability) VALUES(?,?)')->execute([$id, 'both']);
    }
}

function initialize_mysql(PDO $pdo): void {
    $schema = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, first_name VARCHAR(100) NOT NULL DEFAULT '', last_name VARCHAR(100) NOT NULL DEFAULT '', phone VARCHAR(50) NULL, password_hash VARCHAR(255) NULL, role ENUM('admin','member') NOT NULL DEFAULT 'member', status ENUM('invited','active','disabled') NOT NULL DEFAULT 'invited', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, last_login_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS profiles (user_id INT UNSIGNED PRIMARY KEY, address TEXT NULL, latitude DECIMAL(10,7) NULL, longitude DECIMAL(10,7) NULL, approx_latitude DECIMAL(10,2) NULL, approx_longitude DECIMAL(10,2) NULL, availability ENUM('driver','passenger','both') NOT NULL DEFAULT 'passenger', seats SMALLINT UNSIGNED NOT NULL DEFAULT 0, detour VARCHAR(255) NULL, comment TEXT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_profile_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS events (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, type ENUM('repetition','concert') NOT NULL, starts_at DATETIME NOT NULL, location VARCHAR(255) NOT NULL, details TEXT NULL, status ENUM('active','cancelled') NOT NULL DEFAULT 'active', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_events_starts(starts_at), FOREIGN KEY(created_by) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS invitations (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, status ENUM('pending','used','expired') NOT NULL DEFAULT 'pending', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, used_at DATETIME NULL, INDEX idx_invitation_email(email,status), FOREIGN KEY(created_by) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS password_resets (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS carpool_requests (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, event_id INT UNSIGNED NOT NULL, requester_id INT UNSIGNED NOT NULL, recipient_id INT UNSIGNED NOT NULL, pickup_place VARCHAR(255) NOT NULL, pickup_time TIME NOT NULL, message TEXT NULL, status ENUM('en_attente','acceptee','refusee','contre_proposition','annulee') NOT NULL DEFAULT 'en_attente', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_requests_recipient(recipient_id,status), INDEX idx_requests_requester(requester_id,status), FOREIGN KEY(event_id) REFERENCES events(id), FOREIGN KEY(requester_id) REFERENCES users(id), FOREIGN KEY(recipient_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS request_responses (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, request_id INT UNSIGNED NOT NULL, author_id INT UNSIGNED NOT NULL, decision ENUM('acceptee','refusee','contre_proposition','annulee') NOT NULL, message TEXT NULL, proposed_place VARCHAR(255) NULL, proposed_time TIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(request_id) REFERENCES carpool_requests(id) ON DELETE CASCADE, FOREIGN KEY(author_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS notification_log (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, recipient VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, subject VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, error TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) $pdo->exec($statement);
}
