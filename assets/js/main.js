// JS for calendar and booking logic

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

        var events = classesData.map((cls) => ({
          id: cls.generated_id || cls.id,
          title: getClassTitle(cls),
          start: cls.date + "T" + cls.time, // Proper ISO format for FullCalendar
          description: cls.description,
          classData: cls, // Store full class data
          backgroundColor: getEventColor(cls),
          borderColor: getEventColor(cls),
        }));

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
        showClassModal(info.event.id);
      },
    });
    calendar.render();
  }

  // Function to update calendar events (called by real-time availability system)
  window.updateCalendarEvents = function (classes) {
    classesData = classes;
    var events = classes.map((cls) => ({
      id: cls.generated_id || cls.id,
      title: getClassTitle(cls),
      start: cls.date + "T" + cls.time, // Proper ISO format for FullCalendar
      description: cls.description,
      classData: cls,
      backgroundColor: getEventColor(cls),
      borderColor: getEventColor(cls),
    }));

    if (calendar) {
      calendar.removeAllEvents();
      calendar.addEventSource(events);
    }
  };

  function showClassModal(classId) {
    var waitAttempts = 0;
    var maxWaitAttempts = 50; // Maximum 5 seconds (50 * 100ms)

    // Wait for user bookings to be loaded before showing modal
    function waitForBookingsAndShow() {
      if (window.userBookingsLoaded) {
        console.log("User bookings ready, showing modal for class", classId);
        // First try to get data from cached classesData
        var cachedClass = classesData.find((cls) => cls.id == classId);

        if (cachedClass) {
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
      } else {
        waitAttempts++;
        if (waitAttempts >= maxWaitAttempts) {
          console.warn(
            "Timeout waiting for user bookings, showing modal anyway"
          );
          // Force bookings to be marked as loaded and show modal
          window.userBookingsLoaded = true;
          window.userBookedClasses = [];

          var cachedClass = classesData.find((cls) => cls.id == classId);
          if (cachedClass) {
            displayClassModal(cachedClass);
          }
        } else {
          // If bookings aren't loaded yet, wait a bit and try again
          console.log(
            `Waiting for user bookings to load... (${waitAttempts}/${maxWaitAttempts})`
          );
          setTimeout(waitForBookingsAndShow, 100);
        }
      }
    }

    waitForBookingsAndShow();
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
                  ? `<p class="card-text small text-muted mb-2">${cls.instructor_bio}</p>`
                  : ""
              }
              ${
                cls.instructor_specialties
                  ? `
                <div class="d-flex flex-wrap gap-1">
                  ${cls.instructor_specialties
                    .split(",")
                    .map(
                      (specialty) =>
                        `<span class="badge bg-secondary">${specialty.trim()}</span>`
                    )
                    .join("")}
                </div>
              `
                  : ""
              }
            </div>
          </div>
        </div>
      `;
    }

    // Determine booking status and button
    let bookingStatusHtml = "";
    let bookingButtonHtml = "";

    if (!isLoggedIn) {
      bookingStatusHtml = `
        <div class="alert alert-warning">
          <i class="fas fa-sign-in-alt me-2"></i>
          You must be <a href="login.php">logged in</a> to book this class.
        </div>
      `;
      bookingButtonHtml = `
        <a href="login.php" class="btn btn-primary">
          <i class="fas fa-sign-in-alt me-2"></i>Login to Book
        </a>
      `;
    } else if (userHasBooked) {
      bookingStatusHtml = `
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i>
          You have already booked this class.
        </div>
      `;
      bookingButtonHtml = `
        <button type="button" class="btn btn-success" disabled>
          <i class="fas fa-check me-2"></i>Already Booked
        </button>
      `;
    } else if (isFullyBooked) {
      bookingStatusHtml = `
        <div class="alert alert-danger">
          <i class="fas fa-times-circle me-2"></i>
          This class is fully booked.
        </div>
      `;
      bookingButtonHtml = `
        <button type="button" class="btn btn-secondary" disabled>
          <i class="fas fa-times me-2"></i>Fully Booked
        </button>
      `;
    } else if (!isTrialEligible) {
      bookingStatusHtml = `
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          This class requires a membership to book. Trial bookings are not available.
        </div>
      `;
      bookingButtonHtml = `
        <form id="bookingForm">
          <input type="hidden" name="class_id" value="${cls.id}">
          <button type="submit" class="btn btn-primary book-class-btn">
            <i class="fas fa-calendar-check me-2"></i>Book This Class
          </button>
        </form>
      `;
    } else {
      bookingStatusHtml = `
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i>
          This class is available for booking${
            isTrialEligible ? " (Trial eligible)" : ""
          }.
        </div>
      `;
      bookingButtonHtml = `
        <form id="bookingForm">
          <input type="hidden" name="class_id" value="${cls.id}">
          <button type="submit" class="btn btn-primary book-class-btn">
            <i class="fas fa-calendar-check me-2"></i>Book This Class
          </button>
        </form>
      `;
    }

    body.innerHTML = `
      <div data-class-id="${cls.id}" class="class-modal-content">
        <h5>${cls.name}</h5>
        <p>${cls.description || ""}</p>
        <div class="row">
          <div class="col-sm-6">
            <p><strong><i class="fas fa-calendar"></i> Date:</strong> ${
              cls.date
            }</p>
          </div>
          <div class="col-sm-6">
            <p><strong><i class="fas fa-clock"></i> Time:</strong> ${
              cls.time
            }</p>
          </div>
        </div>
        ${instructorHtml}
        <div class="capacity-info spots-remaining mb-3">
          ${availabilityHtml}
        </div>
        <div class="progress mb-3" style="height: 8px;">
          <div class="progress-bar ${getProgressBarClass(status)}" 
               role="progressbar" 
               style="width: ${100 - percentage}%" 
               aria-valuenow="${100 - percentage}" 
               aria-valuemin="0" 
               aria-valuemax="100">
          </div>
        </div>
        ${bookingStatusHtml}
        ${bookingButtonHtml}
        <div id="bookingMsg" class="mt-3"></div>
      </div>
    `;

    var modal = new bootstrap.Modal(document.getElementById("classModal"));
    modal.show();

    // Set up booking form if it exists
    var form = document.getElementById("bookingForm");
    if (form) {
      form.onsubmit = function (e) {
        e.preventDefault();
        submitBooking(form, cls.id);
      };
    }
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

  function getProgressBarClass(status) {
    const classes = {
      available: "bg-success",
      low: "bg-warning",
      full: "bg-danger",
    };
    return classes[status] || classes.available;
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

          // Trigger real-time refresh to update availability
          if (window.realTimeAvailability) {
            window.realTimeAvailability.refreshData();
          }

          // Refresh user bookings to update UI immediately
          loadUserBookings();

          // Immediately update the current modal to show "Successfully Booked" state
          const currentClassId = parseInt(
            form.querySelector('input[name="class_id"]').value
          );
          window.userBookedClasses.push(currentClassId); // Add to local array immediately

          const statusDiv = form.parentElement.querySelector(
            ".alert-success, .alert-info, .alert-warning, .alert"
          );
          if (statusDiv) {
            statusDiv.className = "alert alert-success";
            statusDiv.innerHTML =
              '<i class="fas fa-check-circle me-2"></i>You have successfully booked this class!';
          }

          const submitButton = form.querySelector('button[type="submit"]');
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
          msg.innerHTML =
            '<div class="alert alert-danger">' + errorMsg + "</div>";

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
