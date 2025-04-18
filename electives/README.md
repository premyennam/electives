# Open Elective Management System

A web-based system for managing open electives at JNTUA College of Engineering Anantapur.

## Features

- Student Portal

  - Login with admission number
  - View available electives
  - Select electives
  - View allotment status
  - Update profile
- HOD Portal

  - Login with department credentials
  - Manage department electives
  - Upload student data via CSV
  - View department statistics
  - Handle student queries
- Admin Portal

  - Login with admin credentials
  - Manage HOD accounts
  - View system statistics
  - Monitor elective allotments
  - Generate reports

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone the repository to your web server directory:

   ```bash
   git clone https://github.com/yourusername/electives.git
   ```
2. Create a MySQL database and import the database schema:

   ```bash
   mysql -u root -p < database.sql
   ```
3. Configure the database connection:

   - Open `config/database.php`
   - Update the database credentials:
     ```php
     $host = 'localhost';
     $dbname = 'electives_db';
     $username = 'your_username';
     $password = 'your_password';
     ```
4. Set up the web server:

   - For Apache, ensure mod_rewrite is enabled
   - Configure the document root to point to the project directory
   - Ensure the web server has write permissions for the uploads directory

## Default Login Credentials

### Admin

- Username: admin
- Password: password

### HOD

- Username: hod_cse (or hod_ece, hod_mech, hod_civil)@jntua.ac.in
- Password: password

### Students

- Login using admission number
- Default password is the admission number

## Directory Structure

```
electives/
├── admin/              # Admin portal files
├── hod/               # HOD portal files
├── student/           # Student portal files
├── assets/           # CSS, JS, and other assets
├── config/           # Configuration files
├── uploads/          # File uploads directory
├── database.sql      # Database schema
├── index.php         # Main entry point
└── README.md         # This file
```

## Security Considerations

1. Change default passwords after first login
2. Use HTTPS for secure data transmission
3. Implement proper session management
4. Validate and sanitize all user inputs
5. Use prepared statements for database queries
6. Implement rate limiting for login attempts

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please contact:

- Email: support@jntua.ac.in
- Phone: +91-XXXXXXXXXX

## Acknowledgments

- JNTUA College of Engineering Anantapur
- Bootstrap for the UI framework
- Font Awesome for icons
