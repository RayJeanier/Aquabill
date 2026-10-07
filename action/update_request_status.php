<?php

require "../includes/auth.php";
require "../database/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../maintenance.php");
    exit;
}

$id     = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
$status = $_POST["status"] ?? "";

if ($id === false || !in_array($status, ["Open", "In Progress", "Resolved"], true)) {
    die("Invalid request.");
}

try {

    $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = :status WHERE id = :id");
    $stmt->execute([":status" => $status, ":id" => $id]);

    header("Location: ../maintenance.php?updated=1");
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
