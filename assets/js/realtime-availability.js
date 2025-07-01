/**
 * Real-time Class Availability System
 * Handles AJAX updates, auto-refresh, and UI updates for class availability
 */

class RealTimeAvailability {
  constructor(options = {}) {
    this.apiEndpoint = options.apiEndpoint || "api/classes.php";
    this.refreshInterval = options.refreshInterval || 30000; // 30 seconds default
    this.autoRefresh = options.autoRefresh !== false; // enabled by default
    this.lastUpdated = null;
    this.refreshTimer = null;
    this.isLoading = false;
    this.retryCount = 0;
    this.maxRetries = 3;

    this.init();
  }

  init() {
    this.createControls();
    this.loadInitialData();
    if (this.autoRefresh) {
      this.startAutoRefresh();
    }
    this.bindEvents();
  }

  createControls() {
    // Create refresh controls container
    const controlsContainer = document.createElement("div");
    controlsContainer.className = "availability-controls mb-3";
    controlsContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button class="btn btn-sm btn-outline-primary" id="refreshNow">
                        <i class="fas fa-sync-alt"></i> Refresh Now
                    </button>
                    <span class="text-muted ms-2" id="lastUpdateTime">
                        <small>Loading...</small>
                    </span>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="autoRefreshToggle" ${
                      this.autoRefresh ? "checked" : ""
                    }>
                    <label class="form-check-label" for="autoRefreshToggle">
                        <small>Auto-refresh</small>
                    </label>
                </div>
            </div>
            <div class="progress mt-2" id="refreshProgress" style="height: 3px; display: none;">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
        `;

    // Insert controls before the classes container
    const classesContainer = document.querySelector(
      "#classesContainer, .classes-list, .calendar-container"
    );
    if (classesContainer) {
      classesContainer.parentNode.insertBefore(
        controlsContainer,
        classesContainer
      );
    }
  }

  bindEvents() {
    // Manual refresh button
    document.getElementById("refreshNow")?.addEventListener("click", () => {
      this.refreshData();
    });

    // Auto-refresh toggle
    document
      .getElementById("autoRefreshToggle")
      ?.addEventListener("change", (e) => {
        this.autoRefresh = e.target.checked;
        if (this.autoRefresh) {
          this.startAutoRefresh();
        } else {
          this.stopAutoRefresh();
        }
      });

    // Page visibility change handling
    document.addEventListener("visibilitychange", () => {
      if (document.hidden) {
        this.stopAutoRefresh();
      } else if (this.autoRefresh) {
        this.refreshData();
        this.startAutoRefresh();
      }
    });
  }

  async loadInitialData() {
    await this.refreshData();
  }

  async refreshData() {
    if (this.isLoading) return;

    this.isLoading = true;
    this.showLoading();

    try {
      const response = await fetch(this.apiEndpoint, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "Cache-Control": "no-cache",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const responseText = await response.text();

      // Try to parse as JSON
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error(
          "API returned non-JSON response:",
          responseText.substring(0, 200)
        );
        throw new Error(
          "Server returned invalid response. Check console for details."
        );
      }

      if (data.success) {
        this.updateUI(data.data);
        this.lastUpdated = new Date();
        this.retryCount = 0;
        this.updateLastUpdateTime();
      } else {
        throw new Error(data.error || "Failed to fetch class data");
      }
    } catch (error) {
      console.error("Failed to refresh class data:", error);
      this.handleError(error);
    } finally {
      this.isLoading = false;
      this.hideLoading();
    }
  }

  updateUI(data) {
    const classes = data.classes || data; // Handle both response formats

    // Update calendar events if using calendar view
    if (typeof window.updateCalendarEvents === "function") {
      window.updateCalendarEvents(classes);
    }

    // Update class cards/list items
    classes.forEach((classData) => {
      this.updateClassCard(classData);
    });

    // Trigger custom event for other components
    document.dispatchEvent(
      new CustomEvent("classAvailabilityUpdated", {
        detail: { classes },
      })
    );
  }

  updateClassCard(classData) {
    const classElement = document.querySelector(
      `[data-class-id="${classData.id}"]`
    );
    if (!classElement) return;

    // Update spots remaining
    const spotsElement = classElement.querySelector(
      ".spots-remaining, .capacity-info"
    );
    if (spotsElement) {
      const spotsText = this.getSpotsRemainingText(classData);
      spotsElement.innerHTML = spotsText;

      // Update availability status class
      spotsElement.className = spotsElement.className.replace(
        /availability-\w+/g,
        ""
      );
      spotsElement.classList.add(
        `availability-${classData.availability_status}`
      );
    }

    // Update booking button state
    const bookButton = classElement.querySelector(".book-class-btn, .btn-book");
    if (bookButton) {
      if (classData.spots_remaining <= 0) {
        bookButton.disabled = true;
        bookButton.textContent = "Fully Booked";
        bookButton.classList.add("btn-secondary");
        bookButton.classList.remove("btn-primary");
      } else {
        bookButton.disabled = false;
        bookButton.textContent = "Book Now";
        bookButton.classList.add("btn-primary");
        bookButton.classList.remove("btn-secondary");
      }
    }

    // Add visual indicator
    this.updateAvailabilityIndicator(classElement, classData);
  }

  getSpotsRemainingText(classData) {
    const spotsRemaining = parseInt(classData.spots_remaining);
    const total = parseInt(classData.capacity);
    const current = parseInt(classData.current_bookings);

    if (spotsRemaining <= 0) {
      return `<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Fully Booked (${current}/${total})</span>`;
    } else if (spotsRemaining <= total * 0.2) {
      return `<span class="text-warning"><i class="fas fa-clock"></i> ${spotsRemaining} spots left (${current}/${total})</span>`;
    } else {
      return `<span class="text-success"><i class="fas fa-check-circle"></i> ${spotsRemaining} spots available (${current}/${total})</span>`;
    }
  }

  updateAvailabilityIndicator(element, classData) {
    // Remove existing indicators
    element
      .querySelectorAll(".availability-indicator")
      .forEach((el) => el.remove());

    // Create new indicator
    const indicator = document.createElement("div");
    indicator.className = `availability-indicator availability-${classData.availability_status}`;
    indicator.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            z-index: 10;
        `;

    // Set color based on availability
    const colors = {
      available: "#28a745",
      low: "#ffc107",
      full: "#dc3545",
    };
    indicator.style.backgroundColor =
      colors[classData.availability_status] || colors.available;

    // Add tooltip
    indicator.title = `${
      classData.availability_status.charAt(0).toUpperCase() +
      classData.availability_status.slice(1)
    } - ${classData.spots_remaining} spots remaining`;

    // Ensure parent is positioned
    if (getComputedStyle(element).position === "static") {
      element.style.position = "relative";
    }

    element.appendChild(indicator);
  }

  showLoading() {
    const progressBar = document.getElementById("refreshProgress");
    const refreshBtn = document.getElementById("refreshNow");

    if (progressBar) {
      progressBar.style.display = "block";
      progressBar.querySelector(".progress-bar").style.width = "50%";
    }

    if (refreshBtn) {
      refreshBtn.disabled = true;
      const icon = refreshBtn.querySelector("i");
      if (icon) {
        icon.classList.add("fa-spin");
      }
    }
  }

  hideLoading() {
    const progressBar = document.getElementById("refreshProgress");
    const refreshBtn = document.getElementById("refreshNow");

    if (progressBar) {
      setTimeout(() => {
        progressBar.style.display = "none";
        progressBar.querySelector(".progress-bar").style.width = "0%";
      }, 500);
    }

    if (refreshBtn) {
      refreshBtn.disabled = false;
      const icon = refreshBtn.querySelector("i");
      if (icon) {
        icon.classList.remove("fa-spin");
      }
    }
  }

  handleError(error) {
    this.retryCount++;

    // Show error message
    this.showErrorMessage(error.message);

    // Exponential backoff for retries
    if (this.retryCount < this.maxRetries && this.autoRefresh) {
      const retryDelay = Math.min(1000 * Math.pow(2, this.retryCount), 30000);
      setTimeout(() => {
        this.refreshData();
      }, retryDelay);
    }
  }

  showErrorMessage(message) {
    // Create or update error alert
    let errorAlert = document.getElementById("availability-error");
    if (!errorAlert) {
      errorAlert = document.createElement("div");
      errorAlert.id = "availability-error";
      errorAlert.className = "alert alert-warning alert-dismissible fade show";

      const controlsContainer = document.querySelector(
        ".availability-controls"
      );
      if (controlsContainer) {
        controlsContainer.after(errorAlert);
      }
    }

    errorAlert.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Connection Issue:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

    // Auto-hide after 5 seconds
    setTimeout(() => {
      if (errorAlert) {
        errorAlert.remove();
      }
    }, 5000);
  }

  updateLastUpdateTime() {
    const timeElement = document.getElementById("lastUpdateTime");
    if (timeElement && this.lastUpdated) {
      const timeStr = this.lastUpdated.toLocaleTimeString();
      timeElement.innerHTML = `<small>Last updated: ${timeStr}</small>`;
    }
  }

  startAutoRefresh() {
    this.stopAutoRefresh(); // Clear any existing timer

    if (this.autoRefresh && this.refreshInterval > 0) {
      this.refreshTimer = setInterval(() => {
        if (!document.hidden) {
          // Only refresh if page is visible
          this.refreshData();
        }
      }, this.refreshInterval);
    }
  }

  stopAutoRefresh() {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
      this.refreshTimer = null;
    }
  }

  // Public methods
  setRefreshInterval(interval) {
    this.refreshInterval = interval;
    if (this.autoRefresh) {
      this.startAutoRefresh();
    }
  }

  enableAutoRefresh() {
    this.autoRefresh = true;
    this.startAutoRefresh();
    const toggle = document.getElementById("autoRefreshToggle");
    if (toggle) toggle.checked = true;
  }

  disableAutoRefresh() {
    this.autoRefresh = false;
    this.stopAutoRefresh();
    const toggle = document.getElementById("autoRefreshToggle");
    if (toggle) toggle.checked = false;
  }

  destroy() {
    this.stopAutoRefresh();
    document.removeEventListener("visibilitychange", this.bindEvents);

    // Remove controls
    document.querySelector(".availability-controls")?.remove();
    document.getElementById("availability-error")?.remove();
  }
}

// Auto-initialize if DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  // Only initialize if we're on a page with classes
  if (
    document.querySelector(
      "#classesContainer, .classes-list, .calendar-container"
    )
  ) {
    window.realTimeAvailability = new RealTimeAvailability({
      refreshInterval: 30000, // 30 seconds
      autoRefresh: true,
    });
  }
});

// Export for manual initialization
window.RealTimeAvailability = RealTimeAvailability;
