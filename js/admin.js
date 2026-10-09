// Shared admin UI behavior: dropdown menus, modals and confirm prompts.

// ---------- Dropdowns (<details class="dropdown">) ----------
// Only one open at a time; closes on outside click or Escape.

document.addEventListener("toggle", (e) => {
    const opened = e.target;
    if (!opened.matches || !opened.matches("details.dropdown") || !opened.open) return;

    document.querySelectorAll("details.dropdown[open]").forEach((d) => {
        if (d !== opened) d.open = false;
    });
}, true);

document.addEventListener("click", (e) => {
    document.querySelectorAll("details.dropdown[open]").forEach((d) => {
        if (!d.contains(e.target)) d.open = false;
    });
});

// Clicking an item inside a menu closes the menu
document.addEventListener("click", (e) => {
    const item = e.target.closest(".dropdown-item");
    if (item) {
        const menu = item.closest("details.dropdown");
        if (menu) menu.open = false;
    }
});

// ---------- Modals ----------
// Open:  <button data-modal-open="modalId">
// Close: <button data-modal-close>, the backdrop, or Escape

function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add("open");
    const first = modal.querySelector("input:not([type=hidden]), select, textarea");
    if (first) setTimeout(() => first.focus(), 50);
}

function closeModal(modal) {
    if (modal) modal.classList.remove("open");
}

document.addEventListener("click", (e) => {
    const opener = e.target.closest("[data-modal-open]");
    if (opener) {
        openModal(opener.dataset.modalOpen);
        return;
    }

    const closer = e.target.closest("[data-modal-close]");
    if (closer) {
        closeModal(closer.closest(".modal"));
        return;
    }

    if (e.target.classList && e.target.classList.contains("modal")) {
        closeModal(e.target);
    }
});

document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;

    document.querySelectorAll(".modal.open").forEach(closeModal);
    document.querySelectorAll("details.dropdown[open]").forEach((d) => (d.open = false));
});

// ---------- Confirm before submitting ----------
// <form data-confirm="Delete this consumer?">

document.addEventListener("submit", (e) => {
    const message = e.target.dataset && e.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        e.preventDefault();
    }
});
