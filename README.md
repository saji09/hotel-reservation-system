# Hotel Reservation System
## Project Maintainer - Sajipragas

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.2+-7952B3?logo=bootstrap&logoColor=white)

A complete web-based hotel management system with reservation, check-in/check-out, billing, and reporting functionality.

## Features

- **User Roles**:
  - Admin: Full system access
  - Clerk: Guest check-in/check-out
  - Customer: Make reservations
  - Travel Company: Block bookings

- **Reservation Management**:
  - Room/suite selection with availability checking
  - Credit card guarantees
  - Automatic no-show processing
  - Cancellation handling

- **Operations**:
  - Check-in with room assignment
  - Check-out with billing
  - Additional service charges
  - Residential suite options (weekly/monthly rates)

- **Reporting**:
  - Financial reports
  - Occupancy reports
  - Export to PDF/Excel

## Technologies Used

- **Frontend**:
  - HTML5, CSS3
  - JavaScript (Vanilla)
  - Bootstrap 5

- **Backend**:
  - PHP 8.0+
  - MySQL 8.0
  - mPDF (for PDF generation)

- **Dependencies**:
  - Font Awesome (icons)
  - jQuery (AJAX requests)

## Installation

### Prerequisites
- Web server (Apache/Nginx)
- PHP 8.0+
- MySQL 8.0+
- Composer (for dependencies)

### Setup Steps

1. Clone the repository:
   ```bash
   git clone https://github.com/saji09/hotel-reservation-system.git
   cd hotel-reservation-system
   composer install