# Frontend Components Documentation

## JavaScript Components and APIs

### Calendar Component

Located in: `assets/js/main.js`

#### FullCalendar Integration

```javascript
// Calendar initialization with custom configuration
const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek'
    },
    events: {
        url: '/public/api/classes.php',
        method: 'GET',
        failure: function() {
            alert('There was an error while fetching events!');
        }
    },
    eventClick: function(info) {
        showClassModal(info.event);
    },
    eventClassNames: function(arg) {
        // Dynamic styling based on availability
        const availability = arg.event.extendedProps.availability_status;
        return ['class-event', `availability-${availability}`];
    }
});
```

#### Event Handling Functions

```javascript
// Show class booking modal
function showClassModal(event) {
    const modal = new bootstrap.Modal(document.getElementById('classModal'));
    
    // Populate modal with event data
    document.getElementById('modalClassName').textContent = event.title;
    document.getElementById('modalClassDate').textContent = event.start.toLocaleDateString();
    document.getElementById('modalClassTime').textContent = event.start.toLocaleTimeString();
    document.getElementById('modalCapacity').textContent = event.extendedProps.capacity;
    document.getElementById('modalAvailable').textContent = event.extendedProps.spots_remaining;
    
    // Set booking button data
    const bookBtn = document.getElementById('bookClassBtn');
    bookBtn.setAttribute('data-class-id', event.id);
    
    modal.show();
}

// Handle class booking
function bookClass(classId) {
    const button = document.getElementById('bookClassBtn');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Booking...';
    
    fetch('/public/api/book.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ class_id: parseInt(classId) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            calendar.refetchEvents();
            bootstrap.Modal.getInstance(document.getElementById('classModal')).hide();
        } else {
            showAlert('danger', data.error);
        }
    })
    .catch(error => {
        console.error('Booking error:', error);
        showAlert('danger', 'An error occurred while booking the class.');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = 'Book This Class';
    });
}
```

### Form Validation Components

#### Registration Form Validation

```javascript
// Client-side form validation
function validateRegistrationForm(form) {
    const errors = [];
    
    // Name validation
    if (!form.first_name.value.trim()) {
        errors.push('First name is required');
    }
    if (!form.last_name.value.trim()) {
        errors.push('Last name is required');
    }
    
    // Email validation
    const email = form.email.value.trim();
    if (!email) {
        errors.push('Email is required');
    } else if (!isValidEmail(email)) {
        errors.push('Please enter a valid email address');
    }
    
    // Password validation
    const password = form.password.value;
    if (!password) {
        errors.push('Password is required');
    } else if (!isValidPassword(password)) {
        errors.push('Password must be at least 8 characters with letters and numbers');
    }
    
    // Confirm password
    if (form.confirm_password.value !== password) {
        errors.push('Passwords do not match');
    }
    
    return errors;
}

// Validation helper functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPassword(password) {
    return password.length >= 8 && 
           /[A-Za-z]/.test(password) && 
           /[0-9]/.test(password);
}
```

### Alert System

```javascript
// Unified alert system
function showAlert(type, message, container = 'alertContainer') {
    const alertContainer = document.getElementById(container);
    if (!alertContainer) return;
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
```

### Reviews Component

Located in: `assets/js/reviews.js`

```javascript
// Initialize testimonials carousel
function initTestimonials() {
    const glide = new Glide('#testimonials-glide', {
        type: 'carousel',
        startAt: 0,
        perView: 3,
        gap: 20,
        autoplay: 4000,
        hoverpause: true,
        breakpoints: {
            992: { perView: 2 },
            576: { perView: 1 }
        }
    });
    
    loadReviews().then(reviews => {
        populateTestimonials(reviews);
        glide.mount();
    });
}

// Load reviews from Google Places API or static data
async function loadReviews() {
    try {
        const response = await fetch('/assets/reviews/reviews.json');
        const data = await response.json();
        return data.reviews || [];
    } catch (error) {
        console.error('Error loading reviews:', error);
        return getStaticReviews();
    }
}

// Populate testimonials in the carousel
function populateTestimonials(reviews) {
    const slidesContainer = document.querySelector('#testimonials-glide .glide__slides');
    slidesContainer.innerHTML = '';
    
    reviews.forEach(review => {
        const slide = document.createElement('li');
        slide.className = 'glide__slide';
        slide.innerHTML = `
            <div class="testimonial-card card h-100">
                <div class="card-body text-center">
                    <div class="stars mb-3">
                        ${generateStars(review.rating)}
                    </div>
                    <p class="card-text">"${review.text}"</p>
                    <footer class="blockquote-footer mt-3">
                        <strong>${review.author_name}</strong>
                        <small class="text-muted d-block">${formatDate(review.time)}</small>
                    </footer>
                </div>
            </div>
        `;
        slidesContainer.appendChild(slide);
    });
}
```

## CSS Components

### Custom Styling

Located in: `assets/css/custom.css`

#### Calendar Styling

```css
/* Calendar container styling */
.calendar-container {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* Event styling based on availability */
.fc-event.availability-available {
    background-color: #28a745;
    border-color: #1e7e34;
}

.fc-event.availability-low {
    background-color: #ffc107;
    border-color: #e0a800;
    color: #212529;
}

.fc-event.availability-full {
    background-color: #dc3545;
    border-color: #bd2130;
}

/* Hover effects */
.fc-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}
```

#### Form Styling

```css
/* Enhanced form styling */
.form-floating > .form-control:focus ~ label {
    color: #0d6efd;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

/* Validation styling */
.was-validated .form-control:valid {
    border-color: #28a745;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.94-.94 1.44 1.44L7.41 4.5 8.35 5.44 4.12 9.67z'/%3e%3c/svg%3e");
}

.was-validated .form-control:invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4M8.2 4.6l-2.4 2.4'/%3e%3c/svg%3e");
}
```

#### Responsive Components

```css
/* Instructor cards responsive design */
.instructor-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.instructor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.instructor-profile-photo {
    transition: transform 0.3s ease;
}

.instructor-card:hover .instructor-profile-photo {
    transform: scale(1.05);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .hero-section {
        min-height: 50vh !important;
        padding: 2rem 0;
    }
    
    .hero-section h1 {
        font-size: 2rem;
    }
    
    .calendar-container {
        padding: 1rem !important;
    }
}
```

## Bootstrap Modal Components

### Class Booking Modal

```html
<!-- Class Booking Modal -->
<div class="modal fade" id="classModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalClassName">Class Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-calendar me-2"></i>Date & Time</h6>
                        <p id="modalClassDate" class="mb-1">-</p>
                        <p id="modalClassTime" class="mb-3">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-users me-2"></i>Availability</h6>
                        <p class="mb-1">Capacity: <span id="modalCapacity">-</span></p>
                        <p class="mb-3">Available: <span id="modalAvailable">-</span></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6><i class="fas fa-info-circle me-2"></i>Description</h6>
                        <p id="modalClassDescription">-</p>
                    </div>
                </div>
                <div id="modalAlerts"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="bookClassBtn" onclick="bookClass(this.getAttribute('data-class-id'))">
                    <i class="fas fa-calendar-plus me-2"></i>Book This Class
                </button>
            </div>
        </div>
    </div>
</div>
```

### User Registration Modal

```html
<!-- Registration Modal -->
<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="registrationForm" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="firstName" name="first_name" required>
                                <label for="firstName">First Name</label>
                                <div class="invalid-feedback">Please provide your first name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="lastName" name="last_name" required>
                                <label for="lastName">Last Name</label>
                                <div class="invalid-feedback">Please provide your last name.</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" required>
                        <label for="email">Email Address</label>
                        <div class="invalid-feedback">Please provide a valid email address.</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <label for="password">Password</label>
                                <div class="invalid-feedback">Password must be at least 8 characters.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                                <label for="confirmPassword">Confirm Password</label>
                                <div class="invalid-feedback">Passwords must match.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

## Template System

### Base Template Structure

Located in: `templates/base.php`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Class Booking System'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <?php if (isset($cssPath)): ?>
        <link href="<?php echo $cssPath; ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <?php include 'navigation.php'; ?>
    
    <!-- Main Content -->
    <main class="container-fluid px-0">
        <?php echo $content ?? ''; ?>
    </main>
    
    <!-- Footer -->
    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Additional JS -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JS -->
    <?php if (isset($inlineJS)): ?>
        <script><?php echo $inlineJS; ?></script>
    <?php endif; ?>
</body>
</html>
```

This documentation covers the main frontend components, JavaScript APIs, and template structures used in the Class Booking System.