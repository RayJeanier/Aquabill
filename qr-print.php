<?php
require "includes/auth.php";
require "database/database.php";
require_once "includes/icons.php";
require_once "includes/qr.php";

// One consumer (?user_code=...) or every consumer
$userCode = trim($_GET["user_code"] ?? "");

if ($userCode !== "") {
    $stmt = $pdo->prepare("SELECT user_code, name, address, meter_no, qr_code FROM consumers WHERE user_code = :user_code");
    $stmt->execute([":user_code" => $userCode]);
} else {
    $stmt = $pdo->query("SELECT user_code, name, address, meter_no, qr_code FROM consumers ORDER BY name");
}

$consumers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="img/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="img/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $userCode !== "" ? "QR Code - " . htmlspecialchars($userCode) : "All Consumer QR Codes" ?> - AquaBill</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="<?= QR_LIBRARY_URL ?>"></script>
    <script src="js/qr.js"></script>

    <style>
        body{ background:#f4f7fb; }

        .print-page{
            max-width:960px;
            margin:0 auto;
            padding:32px 24px;
        }

        .print-grid{
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(210px,1fr));
            gap:16px;
        }

        .qr-sticker{
            background:#fff;
            border:1px dashed #cbd5e1;
            border-radius:12px;
            padding:16px;
            text-align:center;
            break-inside:avoid;
        }

        .qr-sticker .qr-box{
            width:150px;
            margin:0 auto 10px;
        }

        .qr-sticker .brand-line{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            font-size:12px;
            font-weight:700;
            color:#0b6fa4;
            margin-bottom:8px;
        }

        .qr-sticker .brand-line .icon{
            width:14px;
            height:14px;
        }

        .qr-sticker strong{
            display:block;
            font-size:14px;
            color:#111827;
        }

        .qr-sticker .code{
            display:block;
            margin-top:2px;
        }

        .qr-sticker small{
            display:block;
            margin-top:4px;
            font-size:11px;
            color:#6b7280;
        }

        @media print{
            body{ background:#fff; }
            .no-print{ display:none !important; }
            .print-page{ padding:0; max-width:none; }
            .print-grid{ grid-template-columns:repeat(3,1fr); gap:10px; }
            .qr-sticker{ border-color:#9ca3af; }
        }
    </style>
</head>

<body>

<div class="print-page">

    <div class="header no-print">
        <div>
            <h1><?= $userCode !== "" ? "Consumer QR Code" : "All Consumer QR Codes" ?></h1>
            <p><?= count($consumers) ?> QR code<?= count($consumers) === 1 ? "" : "s" ?> · cut out and stick on each water meter</p>
        </div>

        <div class="header-actions">
            <a href="consumers.php" class="btn btn-secondary">Back to Consumers</a>
            <button type="button" class="btn" onclick="window.print()"><?= icon("printer") ?> Print</button>
        </div>
    </div>

    <?php if (!$consumers): ?>
        <div class="table-container"><p class="empty">No consumers found.</p></div>
    <?php endif; ?>

    <div class="print-grid">
        <?php foreach ($consumers as $c): ?>
            <div class="qr-sticker">
                <div class="brand-line"><?= icon("droplet") ?> AquaBill · San Vicente</div>
                <div class="qr-box" data-qr="<?= htmlspecialchars($c["qr_code"] ?: consumer_qr_value($c["user_code"])) ?>"></div>
                <strong><?= htmlspecialchars($c["name"]) ?></strong>
                <span class="code"><?= htmlspecialchars($c["user_code"]) ?></span>
                <small><?= htmlspecialchars($c["address"]) ?> · Meter no. <?= htmlspecialchars($c["meter_no"]) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
    renderQrCodes();
</script>

</body>
</html>
