// Minimal Bootstrap JavaScript replacement for offline use

// Alert functionality
class Alert {
  constructor(element) {
    this.element = element;
  }

  close() {
    if (this.element) {
      this.element.style.display = "none";
    }
  }
}

// Make Alert available globally
window.bootstrap = {
  Alert: Alert,
};

// Basic functionality for dismissible alerts
document.addEventListener("DOMContentLoaded", function () {
  // Handle alert close buttons
  const alertCloseButtons = document.querySelectorAll(
    '[data-bs-dismiss="alert"]'
  );
  alertCloseButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const alert = this.closest(".alert");
      if (alert) {
        alert.style.display = "none";
      }
    });
  });

  // Handle dropdown toggles (basic functionality)
  const dropdownToggles = document.querySelectorAll(
    '[data-bs-toggle="dropdown"]'
  );
  dropdownToggles.forEach((toggle) => {
    toggle.addEventListener("click", function (e) {
      e.preventDefault();
      const menu = this.nextElementSibling;
      if (menu && menu.classList.contains("dropdown-menu")) {
        menu.style.display = menu.style.display === "block" ? "none" : "block";
      }
    });
  });

  // Close dropdowns when clicking outside
  document.addEventListener("click", function (e) {
    if (!e.target.closest('[data-bs-toggle="dropdown"]')) {
      const openDropdowns = document.querySelectorAll(
        '.dropdown-menu[style*="block"]'
      );
      openDropdowns.forEach((menu) => {
        menu.style.display = "none";
      });
    }
  });
});
