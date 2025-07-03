// JS for calendar and booking logic with improved timetable display

document.addEventListener("DOMContentLoaded", function () {
  var calendarEl = document.getElementById("calendar");
  if (!calendarEl) return;

  var calendar; // Store calendar instance globally
  var classesData = []; // Store classes data for real-time updates

  // Fetch classes from backend
  fetchClassesData();

  function fetchClassesData() {
    fetch("api/classes.php")
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) throw new Error("Failed to load classes");

        // Handle both old and new API response formats
        classesData = data.data ? data.data.classes : data.classes;

        // Group classes by date and class type for consolidated display
        var events = consolidateClassesToEvents(classesData);

        if (calendar) {
          calendar.removeAllEvents();
          calendar.addEventSource(events);
        } else {
          renderCalendar(events);
        }
      })
      .catch(() => {
        renderCalendar([]);
      });
  }

  function normalizeClassName(className) {
    // Normalize class names to ensure proper grouping
    if (!className) return "";

    // Handle common class name variations
    const normalizedName = className
      .trim()
      .replace(/^Private\s*1-1.*$/i, "Private 1-1 Tuition")
      .replace(/^Adult\s*Advanced.*$/i, "Adult Advanced")
      .replace(/^Adult\s*Fundamentals.*$/i, "Adult Fundamentals")
      .replace(/^Adults?\s*Any\s*Level.*$/i, "Adults Any Level")
      .replace(/^Adults?\s*Bag\s*Work.*$/i, "Adults Bag Work / Open Mat")
      .replace(/^Adults?\s*Sparring.*$/i, "Adults Sparring")
      .replace(/^Infants?.*$/i, "Infants")
      .replace(/^Juniors?.*$/i, "Juniors")
      .replace(/^Seniors?.*$/i, "Seniors");

    return normalizedName;
  }

  function consolidateClassesToEvents(classes) {
    // Group classes by date and normalized class name
    const groupedClasses = {};

    console.log("Starting consolidation with classes:", classes);

    classes.forEach((cls) => {
      const dateKey = cls.date;
      const originalClassName = cls.name;
      const normalizedClassName = normalizeClassName(cls.name);
      const classKey = `${dateKey}_${normalizedClassName}`;

      console.log(
        `Processing class: "${originalClassName}" -> "${normalizedClassName}" (key: ${classKey})`
      );

      if (!groupedClasses[classKey]) {
        groupedClasses[classKey] = {
          date: cls.date,
          className: normalizedClassName,
          classes: [],
          totalCapacity: 0,
          totalBookings: 0,
          instructors: new Set(), // Track all instructors for this class type
          description: cls.description,
        };
        console.log(`Created new group for: ${classKey}`);
      } else {
        console.log(`Adding to existing group: ${classKey}`);
      }

      groupedClasses[classKey].classes.push(cls);
      groupedClasses[classKey].totalCapacity += cls.capacity;
      groupedClasses[classKey].totalBookings += cls.current_bookings || 0;

      // Add instructor to the set (handles multiple instructors for same class type)
      if (cls.instructor_name) {
        groupedClasses[classKey].instructors.add(cls.instructor_name);
      }
    });

    console.log("Grouped classes result:", groupedClasses);

    // Convert to calendar events
    return Object.values(groupedClasses).map((group) => {
      const spotsRemaining = group.totalCapacity - group.totalBookings;
      const availabilityStatus = getGroupAvailabilityStatus(
        spotsRemaining,
        group.totalCapacity
      );

      // Convert instructor set to array and format
      const instructorArray = Array.from(group.instructors);
      const instructorText =
        instructorArray.length > 1
          ? `${instructorArray.length} instructors`
          : instructorArray[0] || "TBA";

      return {
        id: `${group.date}_${group.className.replace(/\s+/g, "_")}`,
        title: getConsolidatedTitle(group, spotsRemaining, availabilityStatus),
        start: group.date,
        allDay: true,
        classData: {
          ...group,
          instructor: instructorText,
          instructors: instructorArray,
        },
        backgroundColor: getEventColor({
          availability_status: availabilityStatus,
        }),
        borderColor: getEventColor({ availability_status: availabilityStatus }),
        extendedProps: {
          isConsolidated: true,
          availabilityStatus: availabilityStatus,
          spotsRemaining: spotsRemaining,
        },
      };
    });
  }

  function getConsolidatedTitle(group, spotsRemaining, status) {
    let title = group.className;

    // Add time slots count
    if (group.classes.length > 1) {
      title += ` (${group.classes.length} slots)`;
    }

    // Add availability info
    if (status === "full") {
      title += ` - Full`;
    } else if (status === "low") {
      title += ` - ${spotsRemaining} spots left`;
    } else {
      title += ` - ${spotsRemaining} spots`;
    }

    return title;
  }

  function getGroupAvailabilityStatus(spotsRemaining, totalCapacity) {
    if (spotsRemaining <= 0) {
      return "full";
    } else if (spotsRemaining <= totalCapacity * 0.2) {
      return "low";
    } else {
      return "available";
    }
  }

  function getClassTitle(cls) {
    const spotsRemaining =
      cls.spots_remaining !== undefined ? cls.spots_remaining : cls.capacity;
    const status = cls.availability_status || "available";

    let title = cls.name;

    // Add instructor info if available
    if (cls.instructor_name) {
      title += ` - ${cls.instructor_name}`;
    }

    // Add spots information
    if (status === "full") {
      title += ` (Full)`;
    } else if (status === "low") {
      title += ` (${spotsRemaining} left)`;
    } else {
      title += ` (${spotsRemaining} spots)`;
    }

    return title;
  }

  function getEventColor(cls) {
    const status = cls.availability_status || "available";
    const colors = {
      available: "#28a745",
      low: "#ffc107",
      full: "#dc3545",
    };
    return colors[status] || colors.available;
  }

  function renderCalendar(events) {
    calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: "dayGridMonth",
      height: 600,
      events: events,
      eventClick: function (info) {
        if (info.event.extendedProps.isConsolidated) {
          showTimeSlotModal(info.event.extendedProps.classData);
        } else {
          showClassModal(info.event.id);
        }
      },
    });
    calendar.render();
  }

  // Function to update calendar events (called by real-time availability system)
  window.updateCalendarEvents = function (classes) {
    classesData = classes;
    var events = consolidateClassesToEvents(classes);

    if (calendar) {
      calendar.removeAllEvents();
      calendar.addEventSource(events);
    }
  };

  function showTimeSlotModal(classGroup) {
    console.log("Showing time slot modal for:", classGroup);

    // Sort time slots by time
    const sortedClasses = classGroup.classes.sort((a, b) => {
      return a.time.localeCompare(b.time);
    });

    // Generate modal content
    const modalContent = generateTimeSlotModalContent(
      classGroup,
      sortedClasses
    );

    // Update modal content
    document.getElementById("timeSlotModalLabel").textContent = `${
      classGroup.className
    } - ${formatDate(classGroup.date)}`;
    document.getElementById("timeSlotModalBody").innerHTML = modalContent;

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById("timeSlotModal"));
    modal.show();

    // Initialize time slot selection
    initializeTimeSlotSelection(sortedClasses);
  }

  function generateTimeSlotModalContent(classGroup, classes) {
    const isLoggedIn = window.userLoggedIn || false;

    // Debug logging for grouping issues
    console.log("Generating modal for class group:", classGroup);
    console.log("Individual classes in group:", classes);

    // Handle instructor display for multiple instructors
    let instructorDisplay = "";
    if (classGroup.instructors && classGroup.instructors.length > 0) {
      if (classGroup.instructors.length === 1) {
        instructorDisplay = `<p class="mb-2"><strong>Instructor:</strong> ${classGroup.instructors[0]}</p>`;
      } else {
        instructorDisplay = `<p class="mb-2"><strong>Instructors:</strong> ${classGroup.instructors.join(
          ", "
        )}</p>`;
      }
    } else if (classGroup.instructor && classGroup.instructor !== "TBA") {
      instructorDisplay = `<p class="mb-2"><strong>Instructor:</strong> ${classGroup.instructor}</p>`;
    }

    let content = `
      <div class="class-info mb-4">
        <h6><i class="fas fa-info-circle me-2"></i>Class Information</h6>
        <p class="mb-2"><strong>Class:</strong> ${classGroup.className}</p>
        <p class="mb-2"><strong>Date:</strong> ${formatDate(
          classGroup.date
        )}</p>
        ${instructorDisplay}
        ${
          classGroup.description
            ? `<p class="mb-2"><strong>Description:</strong> ${classGroup.description}</p>`
            : ""
        }
        <p class="mb-2"><strong>Total Slots:</strong> ${classes.length}</p>
      </div>
      
      <div class="time-slots-selection">
        <h6><i class="fas fa-clock me-2"></i>Available Time Slots</h6>
        <p class="text-muted mb-3">Select one or more time slots to book:</p>
        
        <div class="row g-2 mb-4">
    `;

    classes.forEach((cls) => {
      const spotsRemaining =
        cls.spots_remaining !== undefined ? cls.spots_remaining : cls.capacity;
      const isAvailable = spotsRemaining > 0;
      const status = cls.availability_status || "available";

      const statusClass = isAvailable
        ? status === "low"
          ? "warning"
          : "success"
        : "danger";
      const statusText = isAvailable ? `${spotsRemaining} spots` : "Full";

      content += `
        <div class="col-md-6 col-lg-4">
          <div class="time-slot-card ${
            isAvailable ? "available" : "unavailable"
          }" 
               data-class-id="${cls.id || cls.generated_id}" 
               data-time="${cls.time}"
               data-available="${isAvailable}">
            <div class="card h-100 ${
              isAvailable ? "border-" + statusClass : "border-secondary"
            }">
              <div class="card-body p-3 text-center">
                <h6 class="card-title mb-2">${formatTime(cls.time)}</h6>
                <p class="card-text mb-2">
                  <span class="badge bg-${statusClass}">${statusText}</span>
                </p>
                <div class="form-check">
                  <input class="form-check-input time-slot-checkbox" 
                         type="checkbox" 
                         id="slot_${cls.id || cls.generated_id}"
                         data-class-id="${cls.id || cls.generated_id}"
                         ${!isAvailable ? "disabled" : ""}
                         ${!isLoggedIn ? "disabled" : ""}>
                  <label class="form-check-label" for="slot_${
                    cls.id || cls.generated_id
                  }">
                    ${
                      isAvailable && isLoggedIn
                        ? "Select"
                        : !isLoggedIn
                        ? "Login Required"
                        : "Full"
                    }
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    content += `
        </div>
        
        <div class="selected-slots-summary mb-3" style="display: none;">
          <h6><i class="fas fa-calendar-check me-2"></i>Selected Slots</h6>
          <div class="selected-slots-list"></div>
        </div>
    `;

    if (isLoggedIn) {
      content += `
        <div class="booking-actions">
          <button type="button" class="btn btn-primary btn-lg w-100" id="bookSelectedSlots" disabled>
            <i class="fas fa-calendar-plus me-2"></i>Book Selected Slots
          </button>
        </div>
      `;
    } else {
      content += `
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Please log in to book classes.</strong>
          <br>
          <a href="login.php" class="btn btn-primary mt-2">
            <i class="fas fa-sign-in-alt me-2"></i>Login / Register
          </a>
        </div>
      `;
    }

    return content;
  }

  function initializeTimeSlotSelection(classes) {
    const checkboxes = document.querySelectorAll(".time-slot-checkbox");
    const bookButton = document.getElementById("bookSelectedSlots");
    const summaryDiv = document.querySelector(".selected-slots-summary");
    const summaryList = document.querySelector(".selected-slots-list");

    let selectedSlots = [];

    checkboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", function () {
        const classId = this.getAttribute("data-class-id");
        const classData = classes.find(
          (c) => (c.id || c.generated_id) == classId
        );

        if (this.checked) {
          selectedSlots.push(classData);
        } else {
          selectedSlots = selectedSlots.filter(
            (s) => (s.id || s.generated_id) != classId
          );
        }

        updateSelectedSlotsSummary();
      });
    });

    function updateSelectedSlotsSummary() {
      if (selectedSlots.length > 0) {
        summaryDiv.style.display = "block";
        if (bookButton) bookButton.disabled = false;

        // Update summary list
        summaryList.innerHTML = selectedSlots
          .map(
            (slot) => `
          <span class="badge bg-primary me-2">${formatTime(slot.time)}</span>
        `
          )
          .join("");

        // Update button text
        if (bookButton) {
          bookButton.textContent = `Book ${selectedSlots.length} Slot${
            selectedSlots.length > 1 ? "s" : ""
          }`;
          bookButton.innerHTML = `<i class="fas fa-calendar-plus me-2"></i>Book ${
            selectedSlots.length
          } Slot${selectedSlots.length > 1 ? "s" : ""}`;
        }
      } else {
        summaryDiv.style.display = "none";
        if (bookButton) bookButton.disabled = true;
      }
    }

    // Handle booking submission
    if (bookButton) {
      bookButton.addEventListener("click", function () {
        if (selectedSlots.length === 0) return;

        // Show confirmation
        const confirmation = confirm(
          `Are you sure you want to book ${selectedSlots.length} time slot${
            selectedSlots.length > 1 ? "s" : ""
          }?\n\n` +
            selectedSlots.map((slot) => `• ${formatTime(slot.time)}`).join("\n")
        );

        if (confirmation) {
          bookMultipleSlots(selectedSlots);
        }
      });
    }
  }

  function bookMultipleSlots(slots) {
    const bookButton = document.getElementById("bookSelectedSlots");
    if (bookButton) {
      bookButton.disabled = true;
      bookButton.innerHTML =
        '<i class="fas fa-spinner fa-spin me-2"></i>Booking...';
    }

    // Show overall progress toast
    const progressToast = showToast(
      `Booking ${slots.length} class${slots.length > 1 ? "es" : ""}...`,
      "info",
      0
    );

    // Book each slot
    Promise.all(slots.map((slot) => bookSingleSlot(slot)))
      .then((results) => {
        // Clear progress toast
        if (progressToast) progressToast.remove();

        const successful = results.filter((r) => r.success).length;
        const failed = results.filter((r) => !r.success);

        if (successful > 0) {
          // Show success toast
          let message = `🎉 Successfully booked ${successful} class${
            successful > 1 ? "es" : ""
          }!`;

          // Add trial/membership info if present
          const trialResults = results.filter((r) => r.trial_used);
          if (trialResults.length > 0) {
            message += `\n\n🏆 Used ${trialResults.length} trial booking${
              trialResults.length > 1 ? "s" : ""
            }!`;
          }

          showToast(message, "success", 8000);

          // Show detailed information for each successful booking
          setTimeout(() => {
            const successfulResults = results.filter((r) => r.success);
            if (
              successfulResults.length > 0 &&
              successfulResults[0].class_info
            ) {
              let detailMessage = "📅 Booked Classes:\n";
              successfulResults.forEach((result, index) => {
                if (result.class_info) {
                  detailMessage += `\n• ${result.class_info.name}`;
                  detailMessage += `\n  ${formatDate(
                    result.class_info.date
                  )} at ${result.class_info.time}`;
                  if (result.class_info.instructor) {
                    detailMessage += `\n  Instructor: ${result.class_info.instructor}`;
                  }
                }
              });
              showToast(detailMessage, "info", 10000);
            }
          }, 1000);

          // Show failed bookings if any
          if (failed.length > 0) {
            setTimeout(() => {
              let failMessage = `⚠️ ${failed.length} booking${
                failed.length > 1 ? "s" : ""
              } failed:\n`;
              failed.forEach((result, index) => {
                if (result.isConflict) {
                  failMessage += `\n• Already booked or full`;
                } else if (result.isPastBooking) {
                  failMessage += `\n• Past class cannot be booked`;
                } else if (result.isRestricted) {
                  failMessage += `\n• ${result.error}`;
                } else {
                  failMessage += `\n• ${result.error}`;
                }
              });
              showToast(failMessage, "warning", 12000);
            }, 2000);
          }

          // Close modal and refresh calendar
          setTimeout(() => {
            const modalElement = document.getElementById("timeSlotModal");
            if (modalElement) {
              const modal = bootstrap.Modal.getInstance(modalElement);
              if (modal) modal.hide();
            }
            fetchClassesData(); // Refresh calendar
          }, 1500);
        } else {
          // All bookings failed - show consolidated error
          let errorMessage = `❌ All ${failed.length} booking${
            failed.length > 1 ? "s" : ""
          } failed:\n`;

          // Group errors by type
          const conflicts = failed.filter((r) => r.isConflict).length;
          const pastBookings = failed.filter((r) => r.isPastBooking).length;
          const restrictions = failed.filter((r) => r.isRestricted).length;
          const others = failed.filter(
            (r) => !r.isConflict && !r.isPastBooking && !r.isRestricted
          ).length;

          if (conflicts > 0) {
            errorMessage += `\n• ${conflicts} already booked or full`;
          }
          if (pastBookings > 0) {
            errorMessage += `\n• ${pastBookings} past class${
              pastBookings > 1 ? "es" : ""
            }`;
          }
          if (restrictions > 0) {
            errorMessage += `\n• ${restrictions} restricted (age/membership)`;
          }
          if (others > 0) {
            errorMessage += `\n• ${others} other error${others > 1 ? "s" : ""}`;
          }

          showToast(errorMessage, "danger", 10000);

          // Show specific restriction details if present
          const restrictionErrors = failed.filter((r) => r.isRestricted);
          if (restrictionErrors.length > 0) {
            setTimeout(() => {
              let restrictionMessage = "🚫 Booking Restrictions:\n";
              restrictionErrors.forEach((result, index) => {
                restrictionMessage += `\n• ${result.error}`;
              });
              showToast(restrictionMessage, "warning", 12000);
            }, 2000);
          }
        }
      })
      .catch((error) => {
        // Clear progress toast
        if (progressToast) progressToast.remove();

        console.error("Booking error:", error);
        showToast(
          "🚨 An error occurred while booking. Please try again.",
          "danger",
          8000
        );
      })
      .finally(() => {
        if (bookButton) {
          bookButton.disabled = false;
          bookButton.innerHTML =
            '<i class="fas fa-calendar-plus me-2"></i>Book Selected Slots';
        }
      });
  }

  function bookSingleSlot(slot) {
    // Handle both id and generated_id
    const classId = slot.id || slot.generated_id;

    console.log("Booking slot:", slot);
    console.log("Class ID to send:", classId);

    if (!classId) {
      console.error("No valid class ID found in slot:", slot);
      return Promise.resolve({
        success: false,
        error: "Invalid slot data - no class ID",
      });
    }

    // Show loading indicator
    const loadingToast = showToast("Processing booking...", "info", 0);

    return fetch("api/book.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        class_id: classId,
        class_date: slot.date,
        class_time: slot.time,
      }),
    })
      .then((response) => {
        // Clear loading toast
        if (loadingToast) loadingToast.remove();

        console.log("Booking response status:", response.status);

        // Get response as text first to check for issues
        return response.text().then((text) => {
          console.log("Raw response:", text);

          try {
            const data = JSON.parse(text);

            // Enhance data with response metadata
            data.statusCode = response.status;
            data.isSuccess = response.ok;

            // Handle different HTTP status codes
            if (response.status === 409) {
              data.isConflict = true;
            } else if (response.status === 400) {
              data.isPastBooking = true;
            } else if (response.status === 403) {
              data.isRestricted = true;
            } else if (response.status === 401) {
              data.isUnauthorized = true;
            }

            return data;
          } catch (parseError) {
            console.error("JSON Parse Error:", parseError);
            console.error("Response text:", text);

            // Show error toast
            showToast(
              "Server response error - please try again",
              "danger",
              5000
            );

            return {
              success: false,
              error: "Server response error - please try again",
              parseError: true,
              statusCode: response.status,
              rawResponse: text,
            };
          }
        });
      })
      .then((data) => {
        console.log("Booking response data:", data);

        // Handle different response scenarios
        if (data.success) {
          // Success - show appropriate success message
          let message = data.message || "Class booked successfully!";

          // Add additional context from response
          if (data.class_info) {
            message += `\n\nClass: ${data.class_info.name}`;
            message += `\nDate: ${formatDate(data.class_info.date)}`;
            message += `\nTime: ${data.class_info.time}`;
            if (data.class_info.instructor) {
              message += `\nInstructor: ${data.class_info.instructor}`;
            }
          }

          // Show success toast
          showToast(message, "success", 6000);

          // Handle trial booking feedback
          if (data.trial_used) {
            setTimeout(() => {
              showToast(
                `Free trial used! You have ${
                  data.trial_remaining || 0
                } trial classes remaining.`,
                "info",
                5000
              );
            }, 1000);
          }

          // Handle membership feedback
          if (data.remaining_classes !== undefined) {
            setTimeout(() => {
              showToast(
                `You have ${data.remaining_classes} classes remaining this month.`,
                "info",
                4000
              );
            }, 1500);
          }
        } else {
          // Error handling with specific messages
          let errorMessage = data.error || "Booking failed";
          let toastType = "danger";
          let duration = 6000;

          // Handle specific error types
          if (data.isConflict) {
            toastType = "warning";
            errorMessage = data.error || "Class is already booked or full";
          } else if (data.isPastBooking) {
            toastType = "warning";
            errorMessage = data.error || "Cannot book past classes";
          } else if (data.isRestricted) {
            toastType = "warning";
            duration = 8000;

            // Age restriction or membership issues
            if (data.error.includes("Age restriction")) {
              errorMessage = `🚫 ${data.error}`;
            } else if (data.error.includes("membership")) {
              errorMessage = `💳 ${data.error}`;
            } else {
              errorMessage = `⚠️ ${data.error}`;
            }
          } else if (data.isUnauthorized) {
            toastType = "warning";
            errorMessage = "🔐 Please log in to book classes";
          }

          // Show error toast
          showToast(errorMessage, toastType, duration);

          // Handle redirect if needed
          if (data.redirect) {
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 2000);
          }
        }

        return data;
      })
      .catch((error) => {
        console.error("Booking fetch error:", error);

        // Clear loading toast if still present
        if (loadingToast) loadingToast.remove();

        // Show network error toast
        showToast(
          "Network error - please check your connection and try again",
          "danger",
          8000
        );

        return {
          success: false,
          error: error.message,
          networkError: true,
        };
      });
  }

  // Helper function to show toast notifications
  function showToast(message, type = "info", duration = 4000) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
      toastContainer = document.createElement("div");
      toastContainer.id = "toast-container";
      toastContainer.className =
        "toast-container position-fixed top-0 end-0 p-3";
      toastContainer.style.zIndex = "9999";
      document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toastId = "toast-" + Date.now();
    const toast = document.createElement("div");
    toast.id = toastId;
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");

    // Format message (handle line breaks)
    const formattedMessage = message.replace(/\n/g, "<br>");

    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">
          ${formattedMessage}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;

    // Add to container
    toastContainer.appendChild(toast);

    // Initialize and show toast
    const bsToast = new bootstrap.Toast(toast, {
      autohide: duration > 0,
      delay: duration,
    });
    bsToast.show();

    // Remove from DOM after it's hidden
    toast.addEventListener("hidden.bs.toast", function () {
      toast.remove();
    });

    // Return toast element for potential manual removal
    return toast;
  }

  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  function formatTime(timeString) {
    const [hours, minutes] = timeString.split(":");
    const hour = parseInt(hours);
    const period = hour >= 12 ? "PM" : "AM";
    const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    return `${displayHour}:${minutes} ${period}`;
  }

  // Legacy function for backward compatibility
  function showClassModal(classId) {
    // OPTIMIZED: Show modal immediately, no waiting
    var cachedClass = classesData.find((cls) => cls.id == classId);

    if (cachedClass) {
      // Show modal immediately with cached data
      displayClassModal(cachedClass);
    } else {
      // Fallback to API call if not in cache
      fetch(`api/class.php?id=${classId}`)
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) throw new Error("Class not found");
          displayClassModal(data.class);
        })
        .catch(() => {
          var body = document.getElementById("classModalBody");
          body.innerHTML =
            '<div class="alert alert-danger">Failed to load class details.</div>';
          var modal = new bootstrap.Modal(
            document.getElementById("classModal")
          );
          modal.show();
        });
    }
  }

  function displayClassModal(cls) {
    var body = document.getElementById("classModalBody");

    // Calculate spots information
    const spotsRemaining =
      cls.spots_remaining !== undefined ? cls.spots_remaining : cls.capacity;
    const currentBookings = cls.current_bookings || 0;
    const status = cls.availability_status || "available";
    const percentage = cls.availability_percentage || 100;

    // Get availability display
    const availabilityHtml = getAvailabilityDisplay(cls);

    // Check if user is logged in and class conditions
    const isLoggedIn = window.userLoggedIn || false;
    const isFullyBooked = spotsRemaining <= 0;
    const isTrialEligible =
      cls.trial_eligible == 1 || cls.trial_eligible === true;

    // Check if user has already booked this class
    const userHasBooked =
      window.userBookedClasses &&
      window.userBookedClasses.includes(parseInt(cls.id));

    // Build instructor info HTML
    let instructorHtml = "";
    if (cls.instructor_name) {
      instructorHtml = `
        <div class="instructor-info mb-3">
          <h6><i class="fas fa-user-tie me-2"></i>Instructor</h6>
          <div class="card">
            <div class="card-body p-3">
              <h6 class="card-title mb-1">${cls.instructor_name}</h6>
              ${
                cls.instructor_bio
                  ? `<p class="card-text mb-1"><small class="text-muted">${cls.instructor_bio}</small></p>`
                  : ""
              }
              ${
                cls.instructor_specialties
                  ? `<p class="card-text mb-0"><small><strong>Specialties:</strong> ${cls.instructor_specialties}</small></p>`
                  : ""
              }
            </div>
          </div>
        </div>
      `;
    }

    body.innerHTML = `
      <div class="class-details">
        <div class="class-header mb-3">
          <h5 class="mb-2">${cls.name}</h5>
          <p class="text-muted mb-0">${cls.description || ""}</p>
        </div>
        
        <div class="class-info mb-3">
          <div class="row">
            <div class="col-md-6">
              <p><strong><i class="fas fa-calendar"></i> Date:</strong> ${new Date(
                cls.date
              ).toLocaleDateString("en-US", {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric",
              })}</p>
              <p><strong><i class="fas fa-clock"></i> Time:</strong> ${
                cls.time
              }</p>
              <p><strong><i class="fas fa-users"></i> Capacity:</strong> ${
                cls.capacity
              } students</p>
            </div>
            <div class="col-md-6">
              <div class="availability-status">
                <h6><i class="fas fa-chart-bar me-2"></i>Availability</h6>
                ${availabilityHtml}
              </div>
            </div>
          </div>
        </div>
        
        ${instructorHtml}
        
        <div class="booking-section">
          ${
            !isLoggedIn
              ? `
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Please log in to book this class.</strong>
              <br>
              <a href="login.php" class="btn btn-primary mt-2">
                <i class="fas fa-sign-in-alt me-2"></i>Login / Register
              </a>
            </div>
          `
              : userHasBooked
              ? `
            <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i>
              <strong>You're already booked for this class!</strong>
              <br>
              <a href="user/bookings.php" class="btn btn-outline-primary mt-2">
                <i class="fas fa-calendar-check me-2"></i>View My Bookings
              </a>
            </div>
          `
              : isFullyBooked
              ? `
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>This class is fully booked.</strong>
              <br>
              <small>Check back later for cancellations or try another class.</small>
            </div>
          `
              : `
            <form id="bookingForm" class="needs-validation" novalidate>
              <input type="hidden" name="class_id" value="${cls.id}">
              <input type="hidden" name="class_date" value="${cls.date}">
              <input type="hidden" name="class_time" value="${cls.time}">
              
              <div id="bookingMsg"></div>
              
              <div class="d-grid">
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="fas fa-calendar-check me-2"></i>Book This Class
                </button>
              </div>
              
              ${
                isTrialEligible
                  ? `
                <div class="mt-3 text-center">
                  <small class="text-muted">
                    <i class="fas fa-graduation-cap me-1"></i>
                    Trial class available for new members
                  </small>
                </div>
              `
                  : ""
              }
            </form>
          `
          }
        </div>
      </div>
    `;

    // Add form submission handler if booking form exists
    const bookingForm = document.getElementById("bookingForm");
    if (bookingForm) {
      bookingForm.addEventListener("submit", function (e) {
        e.preventDefault();
        submitBooking(this, cls.id);
      });
    }

    // Show modal
    var modal = new bootstrap.Modal(document.getElementById("classModal"));
    modal.show();
  }

  function getAvailabilityDisplay(cls) {
    const spotsRemaining =
      cls.spots_remaining !== undefined ? cls.spots_remaining : cls.capacity;
    const currentBookings = cls.current_bookings || 0;
    const total = cls.capacity;
    const status = cls.availability_status || "available";

    if (spotsRemaining <= 0) {
      return `<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Fully Booked (${currentBookings}/${total})</span>`;
    } else if (status === "low") {
      return `<span class="text-warning"><i class="fas fa-clock"></i> ${spotsRemaining} spots left (${currentBookings}/${total})</span>`;
    } else {
      return `<span class="text-success"><i class="fas fa-check-circle"></i> ${spotsRemaining} spots available (${currentBookings}/${total})</span>`;
    }
  }

  function submitBooking(form, classId) {
    var msg = document.getElementById("bookingMsg");
    var submitButton = form.querySelector('button[type="submit"]');

    // Prevent multiple submissions
    if (submitButton.disabled) {
      return;
    }

    // Disable button and show loading state
    submitButton.disabled = true;
    var originalText = submitButton.innerHTML;
    submitButton.innerHTML =
      '<i class="fas fa-spinner fa-spin me-2"></i>Booking...';

    msg.innerHTML = "";
    var data = {
      class_id: classId,
    };
    fetch("api/book.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    })
      .then((res) => res.json().then((j) => ({ status: res.status, body: j })))
      .then(({ status, body }) => {
        if (body.success) {
          msg.innerHTML =
            '<div class="alert alert-success">' + body.message + "</div>";

          // Show additional context if provided
          if (body.free_trial_used) {
            msg.innerHTML +=
              '<div class="alert alert-info">Your free trial has been used. You\'ll need a membership for future bookings.</div>';
          }
          if (body.remaining_classes !== undefined) {
            msg.innerHTML +=
              '<div class="alert alert-info">You have ' +
              body.remaining_classes +
              " classes remaining this month.</div>";
          }

          form.reset();

          // OPTIMIZED: Update UI immediately, then refresh data in background
          // Immediately update the current modal to show "Successfully Booked" state
          const currentClassId = parseInt(
            form.querySelector('input[name="class_id"]').value
          );
          window.userBookedClasses.push(currentClassId); // Add to local array immediately

          // Background refresh (non-blocking, faster)
          setTimeout(() => {
            if (window.realTimeAvailability) {
              window.realTimeAvailability.refreshData();
            }
            loadUserBookings(); // Refresh in background
          }, 50); // Reduced from 100ms to 50ms

          // OPTIMIZED: Immediate UI updates with no delays
          const statusDiv = form.parentElement.querySelector(
            ".alert-success, .alert-info, .alert-warning, .alert"
          );
          if (statusDiv) {
            statusDiv.className = "alert alert-success";
            statusDiv.innerHTML =
              '<i class="fas fa-check-circle me-2"></i>You have successfully booked this class!';
          }

          // Keep the submit button in its current state but update styling immediately
          if (submitButton) {
            submitButton.disabled = true;
            submitButton.className = "btn btn-success";
            submitButton.innerHTML =
              '<i class="fas fa-check me-2"></i>Successfully Booked';
          }
        } else if (status === 401 && body.redirect) {
          // User needs to log in - redirect to login/register page
          var currentUrl = encodeURIComponent(window.location.href);
          window.location.href = body.redirect + "?redirect=" + currentUrl;
        } else if (status === 403 && body.redirect) {
          // Membership required - redirect to membership page
          msg.innerHTML =
            '<div class="alert alert-warning">' +
            (body.error || "Membership required") +
            ' <a href="' +
            body.redirect +
            '" class="btn btn-sm btn-primary ms-2">Get Membership</a></div>';
        } else {
          // General error - re-enable button
          submitButton.disabled = false;
          submitButton.innerHTML = originalText;

          var errorMsg = body.error || body.message || "Booking failed";
          var alertClass = "alert-danger";

          // Handle specific error types with better messaging
          if (status === 409) {
            // Conflict - already booked or class full
            alertClass = "alert-warning";
            if (errorMsg.includes("already booked")) {
              msg.innerHTML =
                '<div class="alert ' +
                alertClass +
                '"><i class="fas fa-exclamation-triangle me-2"></i><strong>Already Booked:</strong> ' +
                errorMsg +
                "</div>";
            } else if (errorMsg.includes("fully booked")) {
              msg.innerHTML =
                '<div class="alert ' +
                alertClass +
                '"><i class="fas fa-users me-2"></i><strong>Class Full:</strong> ' +
                errorMsg +
                "</div>";
            } else {
              msg.innerHTML =
                '<div class="alert ' + alertClass + '">' + errorMsg + "</div>";
            }
          } else if (status === 400 && errorMsg.includes("already started")) {
            // Past booking attempt
            alertClass = "alert-info";
            msg.innerHTML =
              '<div class="alert ' +
              alertClass +
              '"><i class="fas fa-clock me-2"></i><strong>Past Class:</strong> ' +
              errorMsg +
              "</div>";
          } else {
            msg.innerHTML =
              '<div class="alert ' + alertClass + '">' + errorMsg + "</div>";
          }

          // If it's a membership-related error, show membership link
          if (
            errorMsg.toLowerCase().includes("membership") ||
            errorMsg.toLowerCase().includes("trial")
          ) {
            msg.innerHTML +=
              '<div class="mt-2"><a href="user/membership.php" class="btn btn-sm btn-primary">View Membership Options</a></div>';
          }
        }
      })
      .catch((error) => {
        console.error("Booking error:", error);

        // Re-enable button on error
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;

        msg.innerHTML =
          '<div class="alert alert-danger">Booking failed. Please try again.</div>';
      });
  }

  // Load user bookings if logged in
  window.userBookedClasses = [];
  window.userBookingsLoaded = false;

  function loadUserBookings() {
    if (!window.userLoggedIn) {
      console.log("User not logged in, skipping bookings load");
      window.userBookingsLoaded = true;
      return Promise.resolve();
    }

    console.log("Loading user bookings...");
    return fetch("api/user_bookings.php")
      .then((res) => {
        console.log("User bookings response status:", res.status);
        if (!res.ok) {
          throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        return res.json();
      })
      .then((data) => {
        console.log("User bookings response data:", data);
        if (data.success) {
          window.userBookedClasses = data.booked_class_ids || [];
          console.log(
            "User bookings loaded successfully:",
            window.userBookedClasses
          );
        } else {
          console.warn("Failed to load user bookings:", data.message);
          window.userBookedClasses = [];
        }
        window.userBookingsLoaded = true;
      })
      .catch((err) => {
        console.error("Could not load user bookings:", err);
        window.userBookedClasses = [];
        window.userBookingsLoaded = true; // Set to true even on error to prevent infinite loop
      });
  }

  // Initialize application data with proper sequencing
  document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing...");
    console.log("User logged in:", window.userLoggedIn);
    console.log("User ID:", window.userId);

    // OPTIMIZED: Load user bookings immediately if user is logged in
    if (window.userLoggedIn) {
      loadUserBookings();
    } else {
      window.userBookingsLoaded = true;
      window.userBookedClasses = [];
    }

    // Force load user bookings immediately
    if (window.userLoggedIn) {
      console.log("User is logged in, loading bookings...");
      loadUserBookings().then(() => {
        console.log("User bookings loaded, ready for class interactions");
      });
    } else {
      console.log("User not logged in, skipping bookings");
      window.userBookingsLoaded = true;
    }
  });
});

// Initialize smooth image loading for class images
document.addEventListener("DOMContentLoaded", function () {
  // Handle class image loading
  const classImages = document.querySelectorAll(".class-image-container img");

  classImages.forEach(function (img) {
    const container = img.closest(".class-image-container");

    // Set up loading event
    img.onload = function () {
      this.classList.add("loaded");
      if (container) {
        container.classList.add("loaded");
      }
    };

    // Set up error event (fallback to placeholder)
    img.onerror = function () {
      // Hide the image container and show placeholder if image fails
      if (container) {
        container.classList.add("loaded"); // Remove loading animation
        container.innerHTML = `
          <div class="class-image-placeholder d-flex align-items-center justify-content-center text-white fw-bold">
            <div>
              <i class="fas fa-fist-raised fa-2x mb-2"></i><br>
              ${this.alt || "Class"}
            </div>
          </div>
        `;
      }
    };

    // If image is already loaded (from cache), trigger the load event
    if (img.complete && img.naturalHeight !== 0) {
      img.onload();
    }
  });
});

// Utility function to handle responsive image sizing
function handleImageResize() {
  const classContainers = document.querySelectorAll(".class-image-container");

  classContainers.forEach((container) => {
    const img = container.querySelector("img");
    if (img && img.complete) {
      // Calculate if image should use natural aspect ratio on this screen size
      const screenWidth = window.innerWidth;
      const naturalRatio = img.naturalWidth / img.naturalHeight;

      // On larger screens, allow wider images to show more naturally
      if (screenWidth > 1200 && naturalRatio > 1.8) {
        container.style.aspectRatio = `${naturalRatio}`;
      }
    }
  });
}

// Handle window resize for responsive images
window.addEventListener("resize", debounce(handleImageResize, 250));

// Debounce utility function
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}
