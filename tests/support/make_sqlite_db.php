<?php
/**
 * TEST SUPPORT ONLY — builds a SQLite copy of the database from the SAME
 * database/schema.sql and database/seed.sql used on MySQL, so the automated
 * tests can run on a machine without a MySQL server.
 *
 *   php tests/support/make_sqlite_db.php <output.sqlite> [--no-seed]
 *
 * The converter only understands the disciplined DDL style used in schema.sql.
 * Passing tests on SQLite does NOT replace a final check on MySQL/MariaDB.
 */
declare(strict_types=1);

$out = $argv[1] ?? null;
if (!$out) {
    fwrite(STDERR, "Usage: php make_sqlite_db.php <output.sqlite> [--no-seed]\n");
    exit(1);
}
$root = dirname(__DIR__, 2);
@unlink($out);
$pdo = new PDO('sqlite:' . $out, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('PRAGMA foreign_keys = ON');

function split_statements(string $sql): array
{
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $parts = [];
    $buf = '';
    $inString = false;
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        if ($ch === "'") {
            if ($inString && ($sql[$i + 1] ?? '') === "'") {
                $buf .= "''";
                $i++;
                continue;
            }
            $inString = !$inString;
        }
        if ($ch === ';' && !$inString) {
            if (trim($buf) !== '') {
                $parts[] = trim($buf);
            }
            $buf = '';
            continue;
        }
        $buf .= $ch;
    }
    if (trim($buf) !== '') {
        $parts[] = trim($buf);
    }
    return $parts;
}

function convert_create(string $stmt, array &$indexes): string
{
    preg_match('/CREATE TABLE (\w+)/', $stmt, $m);
    $table = $m[1];
    $open = strpos($stmt, '(');
    $close = strrpos($stmt, ')');
    $body = substr($stmt, $open + 1, $close - $open - 1);
    $lines = array_map('trim', preg_split('/,\s*\n/', $body));
    $keep = [];
    foreach ($lines as $line) {
        $line = rtrim($line, ',');
        if ($line === '') {
            continue;
        }
        if (preg_match('/^KEY (\w+) \(([^)]+)\)$/', $line, $k)) {
            $indexes[] = "CREATE INDEX {$k[1]} ON $table ({$k[2]})";
            continue;
        }
        if (preg_match('/^UNIQUE KEY (\w+) \(([^)]+)\)$/', $line, $k)) {
            $keep[] = "CONSTRAINT {$k[1]} UNIQUE ({$k[2]})";
            continue;
        }
        $line = preg_replace('/INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY/', 'INTEGER PRIMARY KEY AUTOINCREMENT', $line);
        $line = str_replace([' UNSIGNED', ' ON UPDATE CURRENT_TIMESTAMP'], '', $line);
        $keep[] = $line;
    }
    return "CREATE TABLE $table (\n  " . implode(",\n  ", $keep) . "\n)";
}

function run_file(PDO $pdo, string $file): int
{
    $count = 0;
    $indexes = [];
    foreach (split_statements(file_get_contents($file)) as $stmt) {
        if (preg_match('/^(CREATE DATABASE|USE |SET )/i', $stmt)) {
            continue;
        }
        if (str_starts_with($stmt, 'CREATE TABLE')) {
            $stmt = convert_create($stmt, $indexes);
        }
        $stmt = preg_replace('/\) ENGINE=InnoDB.*$/s', ')', $stmt);
        $pdo->exec($stmt);
        $count++;
    }
    foreach ($indexes as $idx) {
        $pdo->exec($idx);
    }
    return $count;
}

$n = run_file($pdo, $root . '/database/schema.sql');
if (!in_array('--no-seed', $argv, true)) {
    $n += run_file($pdo, $root . '/database/seed.sql');
}
echo "SQLite test database ready: $out ($n statements)\n";
