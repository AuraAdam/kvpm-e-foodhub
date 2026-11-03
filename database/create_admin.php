<?php
/**
 * Create an administrator account from the command line.
 *
 * There is deliberately NO web page for creating administrators.
 * Run this once after importing schema.sql (it works on an empty database):
 *
 *   Windows (XAMPP):  C:\xampp\php\php.exe database\create_admin.php
 *   macOS/Linux:      php database/create_admin.php
 *
 * You will be asked for a name, e-mail and password. The password is typed
 * by you, hashed with password_hash(), and never stored in any file.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

function ask(string $label, bool $hidden = false): string
{
    fwrite(STDOUT, $label . ': ');
    if ($hidden && DIRECTORY_SEPARATOR === '/' && function_exists('posix_isatty') && posix_isatty(STDIN) && function_exists('shell_exec') && trim((string) shell_exec('command -v stty')) !== '') {
        shell_exec('stty -echo');
        $value = (string) fgets(STDIN);
        shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
    } else {
        $value = (string) fgets(STDIN); // Windows terminals cannot hide input without extensions
    }
    return trim($value);
}

fwrite(STDOUT, "E-FoodHub — create administrator\n");
if (DIRECTORY_SEPARATOR === '\\') {
    fwrite(STDOUT, "Note: the password will be visible while you type. Make sure nobody is watching your screen.\n");
}

$name = ask('Full name');
$email = mb_strtolower(ask('E-mail'));
$password = ask('Password (min 10 characters, letters and numbers)', true);
$confirm = ask('Repeat password', true);

$errors = [];
if (mb_strlen($name) < 3) {
    $errors[] = 'Name must be at least 3 characters.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'E-mail is not valid.';
}
if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    $errors[] = 'Password must be at least 10 characters and contain letters and numbers.';
}
if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}
if (Database::value('SELECT id FROM users WHERE email = ?', [$email])) {
    $errors[] = 'An account with this e-mail already exists.';
}
if ($errors) {
    fwrite(STDERR, "\nNot created:\n - " . implode("\n - ", $errors) . "\n");
    exit(1);
}

$id = Database::insert('users', [
    'name'          => $name,
    'email'         => $email,
    'phone'         => null,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role'          => 'admin',
    'status'        => 'active',
    'created_at'    => now(),
    'updated_at'    => now(),
]);
Database::insert('audit_logs', [
    'actor_id'    => null,
    'action'      => 'admin.created',
    'entity_type' => 'user',
    'entity_id'   => $id,
    'details'     => json_encode(['email' => $email, 'via' => 'cli']),
    'ip_address'  => 'cli',
    'created_at'  => now(),
]);
fwrite(STDOUT, "\nAdministrator #$id created for $email. Sign in at /login.\n");
