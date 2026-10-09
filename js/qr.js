// Consumer QR codes, drawn in the browser with the qrcode-generator library
// (loaded from cdnjs - see includes/qr.php).

function buildQr(text) {
    const qr = qrcode(0, "M");   // auto size, medium error correction
    qr.addData(text);
    qr.make();
    return qr;
}

// Crisp SVG version for showing on screen / printing
function qrSvg(text, margin = 2) {
    const qr = buildQr(text);
    const count = qr.getModuleCount();
    const size = count + margin * 2;
    let path = "";

    for (let row = 0; row < count; row++) {
        for (let col = 0; col < count; col++) {
            if (qr.isDark(row, col)) {
                path += `M${col + margin} ${row + margin}h1v1h-1z`;
            }
        }
    }

    return `<svg class="qr-svg" viewBox="0 0 ${size} ${size}" shape-rendering="crispEdges" role="img" aria-label="QR code for ${text}">`
        + `<rect width="${size}" height="${size}" fill="#fff"/>`
        + `<path d="${path}" fill="#000"/></svg>`;
}

// Fill every element that has data-qr="..." with its QR code
function renderQrCodes(root = document) {
    root.querySelectorAll("[data-qr]").forEach((el) => {
        el.innerHTML = qrSvg(el.dataset.qr);
    });
}

// Download a PNG sticker: QR code with the consumer's name and code underneath
function downloadQrPng(text, name, filename) {
    const qr = buildQr(text);
    const count = qr.getModuleCount();
    const cell = 10;
    const margin = 4 * cell;
    const qrSize = count * cell;
    const width = qrSize + margin * 2;
    const height = width + 70;

    const canvas = document.createElement("canvas");
    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext("2d");
    ctx.fillStyle = "#fff";
    ctx.fillRect(0, 0, width, height);

    ctx.fillStyle = "#000";
    for (let row = 0; row < count; row++) {
        for (let col = 0; col < count; col++) {
            if (qr.isDark(row, col)) {
                ctx.fillRect(margin + col * cell, margin + row * cell, cell, cell);
            }
        }
    }

    ctx.textAlign = "center";
    ctx.fillStyle = "#111827";
    ctx.font = "bold 22px Inter, Arial, sans-serif";
    ctx.fillText(name, width / 2, width + 18);
    ctx.fillStyle = "#4b5563";
    ctx.font = "18px Inter, Arial, sans-serif";
    ctx.fillText(text, width / 2, width + 48);

    const link = document.createElement("a");
    link.download = filename;
    link.href = canvas.toDataURL("image/png");
    link.click();
}
