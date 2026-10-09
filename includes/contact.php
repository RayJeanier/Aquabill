<?php

// Optional consumer contact details (mobile number + email).
// Used by the admin Add/Edit Consumer forms and the consumer's Settings page.
// Each returns [cleaned value or null if left empty, error message or null].

function clean_contact_number(?string $input): array
{
    $value = trim((string) $input);

    if ($value === "") {
        return [null, null];
    }

    // Allow the usual ways of typing a number: 0917 123 4567, +63 917-123-4567, (02) 8123 4567
    if (!preg_match('/^\+?[0-9\s\-()]+$/', $value)) {
        return [null, "Contact number can only contain numbers, spaces, +, - and brackets."];
    }

    $digits = strlen(preg_replace('/\D/', "", $value));

    if ($digits < 7 || $digits > 15) {
        return [null, "Please enter a valid contact number (e.g. 0917 123 4567)."];
    }

    return [preg_replace('/\s+/', " ", $value), null];
}

function clean_email(?string $input): array
{
    $value = trim((string) $input);

    if ($value === "") {
        return [null, null];
    }

    if (strlen($value) > 254 || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
        return [null, "Please enter a valid email address (e.g. name@example.com)."];
    }

    return [strtolower($value), null];
}
