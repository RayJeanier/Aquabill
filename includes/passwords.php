<?php

// Password hashing for every account (admins, staff and consumers).
//
// Passwords are stored as bcrypt hashes, never as plain text. They're saved in the
// "$2a$" form: PHP's password_verify() and Postgres' pgcrypto crypt() both understand
// it, so the Android app can log in through the app_login() database function and
// the website can log in here, against the same hashes.

const PASSWORD_MIN_LENGTH = 6;

function hash_password(string $password): string
{
    // PHP labels bcrypt "$2y$"; pgcrypto only accepts "$2a$". Same algorithm, different label.
    return '$2a$' . substr(password_hash($password, PASSWORD_BCRYPT, ["cost" => 10]), 4);
}

function is_password_hash(?string $stored): bool
{
    return $stored !== null && preg_match('/^\$2[aby]\$/', $stored) === 1;
}

// Checks a typed password against what's stored. Accounts that haven't been
// hashed yet (plain text) still work, so nobody is locked out during the switch.
function verify_password(string $input, ?string $stored): bool
{
    if ($stored === null || $stored === "") {
        return false;
    }

    if (is_password_hash($stored)) {
        return password_verify($input, $stored);
    }

    return hash_equals($stored, $input);
}

// Temporary password for admin resets, e.g. "kx7m-p4tw" (no look-alike characters)
function generate_temp_password(): string
{
    $chars = "abcdefghjkmnpqrstuvwxyz23456789";
    $pick  = fn () => $chars[random_int(0, strlen($chars) - 1)];

    return implode("", array_map(fn () => $pick(), range(1, 4)))
        . "-"
        . implode("", array_map(fn () => $pick(), range(1, 4)));
}
