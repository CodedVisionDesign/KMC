# Class Booking System

A modern, responsive web application for managing class bookings with user and admin interfaces.

## Features

### User Features

- **Interactive Calendar**: View available classes in a beautiful calendar interface
- **Class Details**: Click on any class to view detailed information
- **Easy Booking**: Book classes directly through modal forms
- **Booking Confirmation**: Instant feedback on booking success or failure
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices

### Admin Features

- **Secure Login**: Password-protected admin access
- **Dashboard**: Overview of classes and bookings statistics
- **Class Management**: Create, edit, and delete classes
- **Booking Overview**: View all bookings with filtering options
- **Capacity Management**: Automatic prevention of overbooking

## Technology Stack

- **Frontend**: HTML5, CSS3 (Bootstrap 5), JavaScript (FullCalendar)
- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL/MariaDB
- **Security**: Password hashing, input validation, SQL injection prevention

## Installation

### Prerequisites

- Web server with PHP 7.4 or higher
- MySQL or MariaDB database
- Web browser with JavaScript enabled

### Setup Instructions

1. **Clone or download** this project to your web server directory

2. **Database Setup**:

   ```sql
   -- Create database
   CREATE DATABASE testbook;

   -- Import the setup script
   mysql -u your_username -p testbook < config/setup.sql
   ```

3. **Configure Database Connection**:
   Edit `public/api/db.php` and update the database credentials:

   ```php
   $host = 'localhost';
   $db   = 'testbook';
   $user = 'your_username';
   $pass = 'your_password';
   ```

4. **Set Permissions**:
   Ensure your web server has read access to all files and write access to session storage.

5. **Access the Application**:
   - User Interface: `http://your-domain.com/public/index.php`
   - Admin Interface: `http://your-domain.com/admin/login.php`

### Default Admin Credentials

- **Username**: admin
- **Password**: admin123

**⚠️ Important**: Change the default admin password immediately after setup!

## File Structure

```
├── admin/                  # Admin interface
│   ├── auth.php           # Authentication utilities
│   ├── bookings.php       # Booking management
│   ├── classes.php        # Class management
│   ├── dashboard.php      # Admin dashboard
│   ├── login.php          # Admin login
│   └── logout.php         # Logout functionality
├── assets/                # Static assets
│   ├── css/
│   │   └── custom.css     # Custom styles
│   └── js/
│       └── main.js        # Frontend JavaScript
├── config/                # Configuration files
│   ├── database.sql       # Database schema
│   ├── security.php       # Security utilities
│   └── setup.sql          # Complete setup script
├── public/                # Public web root
│   ├── api/               # API endpoints
│   │   ├── book.php       # Booking submission
│   │   ├── class.php      # Single class details
│   │   ├── classes.php    # All classes
│   │   └── db.php         # Database connection
│   └── index.php          # Main user interface
├── templates/             # HTML templates
│   └── base.php           # Base template
└── README.md              # This file
```

## Usage Guide

### For Users

1. **View Classes**: Open the main page to see the calendar with available classes
2. **Book a Class**: Click on any class in the calendar to open the booking modal
3. **Fill Details**: Enter your name and email address
4. **Confirm Booking**: Click "Book This Class" to confirm your reservation
5. **Confirmation**: You'll see a success message if the booking is confirmed

### For Administrators

1. **Login**: Access `/admin/login.php` with your credentials
2. **Dashboard**: View system statistics and navigate to management areas
3. **Manage Classes**:
   - Create new classes with date, time, and capacity
   - Edit existing class details
   - Delete classes (this also removes all bookings)
4. **View Bookings**: See all bookings with filtering by class
5. **Logout**: Always logout when finished for security

## API Endpoints

The system provides RESTful API endpoints:

- `GET /api/classes.php` - Fetch all classes
- `GET /api/class.php?id={id}` - Fetch single class details
- `POST /api/book.php` - Submit a booking

### Booking API Example

```javascript
fetch("/api/book.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    class_id: 1,
    name: "John Doe",
    email: "john@example.com",
  }),
});
```

## Security Features

- **SQL Injection Prevention**: All database queries use prepared statements
- **Input Validation**: Server-side validation for all user inputs
- **Password Security**: Admin passwords are hashed using PHP's password_hash()
- **Session Management**: Secure session handling for admin authentication
- **XSS Prevention**: All output is properly escaped

## Customization

### Styling

- Edit `assets/css/custom.css` to customize the appearance
- The system uses Bootstrap 5 for responsive design
- FullCalendar can be customized through JavaScript options

### Database

- Modify `config/database.sql` to add additional fields
- Update API endpoints to handle new fields
- Adjust frontend forms accordingly

## Troubleshooting

### Common Issues

1. **Calendar not loading**: Check browser console for JavaScript errors
2. **Booking fails**: Verify database connection and table structure
3. **Admin login fails**: Ensure admin user exists and password is correct
4. **Classes not displaying**: Check API endpoints and database data

### Debug Mode

Add this to the top of PHP files for debugging:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## License

This project is open source and available under the MIT License.

## Support

For issues and questions:

1. Check the troubleshooting section above
2. Verify your PHP and database versions meet requirements
3. Ensure all file permissions are correctly set
4. Check browser console for JavaScript errors

---

**Note**: This system is designed for small to medium-scale class booking needs. For high-traffic applications, consider implementing caching and database optimization.
