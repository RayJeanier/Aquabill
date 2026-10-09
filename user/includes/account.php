<?php

// Works out a consumer's bills and balance.
//
// Each meter reading is one bill. Every payment the consumer has made at the
// office is applied to the oldest bill first, so each bill ends up
// Paid, Partially paid, Unpaid or Overdue.

require_once __DIR__ . "/../../includes/billing.php";

function get_consumer_account(PDO $pdo, string $userCode): array
{
    $pricing = get_pricing();
    $today   = new DateTimeImmutable("today");

    $stmt = $pdo->prepare("
        SELECT id, previous_reading, current_reading, reading_date, amount
        FROM readings
        WHERE user_code = :user_code
        ORDER BY reading_date ASC, id ASC
    ");
    $stmt->execute([":user_code" => $userCode]);
    $readings = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE user_code = :user_code");
    $stmt->execute([":user_code" => $userCode]);
    $totalPaid = (float) $stmt->fetchColumn();

    $bills       = [];
    $remaining   = $totalPaid;
    $totalBilled = 0.0;

    foreach ($readings as $r) {
        $usage = max(0, (float) $r["current_reading"] - (float) $r["previous_reading"]);

        // Older readings were saved before amounts were stored
        $amount = (float) $r["amount"] > 0 ? (float) $r["amount"] : compute_bill($usage, $pricing);

        $readOn = (new DateTimeImmutable($r["reading_date"]))->setTime(0, 0);
        $dueOn  = $readOn->modify("+" . (int) $pricing["due_days"] . " days");

        $paid      = min($remaining, $amount);
        $remaining = round($remaining - $paid, 2);
        $unpaid    = round($amount - $paid, 2);

        if ($unpaid <= 0) {
            $status = "Paid";
        } elseif ($dueOn < $today) {
            $status = "Overdue";
        } elseif ($paid > 0) {
            $status = "Partially paid";
        } else {
            $status = "Unpaid";
        }

        $bills[] = [
            "id"        => (int) $r["id"],
            "period"    => $readOn->format("F Y"),
            "month"     => $readOn->format("M"),
            "usage"     => $usage,
            "reading"   => (float) $r["current_reading"],
            "read_on"   => $readOn,
            "due_on"    => $dueOn,
            "amount"    => $amount,
            "paid"      => $paid,
            "unpaid"    => $unpaid,
            "status"    => $status,
            "days_left" => (int) $today->diff($dueOn)->format("%r%a"),
        ];

        $totalBilled += $amount;
    }

    $unpaidBills = array_values(array_filter($bills, fn ($b) => $b["unpaid"] > 0));

    return [
        "bills"        => array_reverse($bills),   // newest first
        "unpaid_bills" => $unpaidBills,            // oldest first
        "current"      => $bills ? end($bills) : null,
        "previous"     => count($bills) > 1 ? $bills[count($bills) - 2] : null,
        "total_billed" => $totalBilled,
        "total_paid"   => $totalPaid,
        "balance"      => max(0, round($totalBilled - $totalPaid, 2)),
        "credit"       => max(0, round($totalPaid - $totalBilled, 2)),
        "next_due"     => $unpaidBills[0]["due_on"] ?? null,
    ];
}

// "Due in 6 days" / "Due today" / "Overdue by 3 days" / "Paid"
function due_label(array $bill): string
{
    if ($bill["unpaid"] <= 0) {
        return "Paid";
    }

    $days = $bill["days_left"];

    if ($days < 0) {
        return "Overdue by " . abs($days) . " day" . (abs($days) === 1 ? "" : "s");
    }

    if ($days === 0) {
        return "Due today";
    }

    return "Due in " . $days . " day" . ($days === 1 ? "" : "s");
}

// CSS class for a bill status pill
function status_class(string $status): string
{
    return match ($status) {
        "Paid"    => "good",
        "Overdue" => "critical",
        default   => "warning",
    };
}

// Icon + color tone for a bill row, matching its status pill
function bill_status_icon(string $status): array
{
    return match ($status) {
        "Paid"    => ["check", "green"],
        "Overdue" => ["alert", "red"],
        default   => ["clock", "amber"],
    };
}

// 12.50 -> "12.5", 300.00 -> "300"
function format_number(string|float $value): string
{
    return rtrim(rtrim(number_format((float) $value, 2), "0"), ".");
}
