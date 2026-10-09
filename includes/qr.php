<?php

// Consumer QR codes.
//
// Each consumer's QR code holds their user code (e.g. SVOB-CONS-AFB440), so a
// meter reader's app can scan the sticker on the meter and look the consumer up.
// The value is saved in consumers.qr_code; the QR image itself is drawn in the
// browser by js/qr.js (the qrcode-generator library), so PHP needs no image extension.

const QR_LIBRARY_URL = "https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js";

function consumer_qr_value(string $userCode): string
{
    return $userCode;
}
