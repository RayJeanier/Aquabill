<?php

require "../includes/auth.php";
require "../includes/billing.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pricing.php");
    exit;
}

$minimumCharge = filter_var($_POST["minimum_charge"] ?? "", FILTER_VALIDATE_FLOAT);
$minimumCubic  = filter_var($_POST["minimum_cubic"] ?? "", FILTER_VALIDATE_INT);
$excessRate    = filter_var($_POST["excess_rate"] ?? "", FILTER_VALIDATE_FLOAT);
$dueDays       = filter_var($_POST["due_days"] ?? "", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if ($minimumCharge === false || $minimumCubic === false || $excessRate === false
    || $minimumCharge < 0 || $minimumCubic < 0 || $excessRate < 0) {
    header("Location: ../pricing.php?error=" . urlencode("All rates must be numbers of 0 or more."));
    exit;
}

if ($dueDays === false) {
    header("Location: ../pricing.php?error=" . urlencode("Days until due must be a whole number of 1 or more."));
    exit;
}

$saved = save_pricing([
    "minimum_charge" => $minimumCharge,
    "minimum_cubic"  => $minimumCubic,
    "excess_rate"    => $excessRate,
    "due_days"       => $dueDays,
]);

if (!$saved) {
    header("Location: ../pricing.php?error=" . urlencode("Could not save config/pricing.json - check folder permissions."));
    exit;
}

header("Location: ../pricing.php?success=1");
exit;
