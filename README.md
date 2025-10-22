# College Auction Website

A simple auction website built with PHP, MySQL, and Tailwind CSS for a college project.

## Setup Instructions

### Prerequisites
- XAMPP (Apache, PHP 8+, MySQL/MariaDB)
- Web browser

### Database Setup

1. **Start XAMPP services:**
   - Start Apache and MySQL from XAMPP Control Panel

2. **Create the database:**
   - Option A: Run the setup script
     ```bash
     php setup_database.php
     ```
   
   - Option B: Manual setup via phpMyAdmin
     - Open http://localhost/phpmyadmin
     - Create a new database named `college_auction`
     - Import the SQL file: `sql/database_schema.sql`

3. **Test the connection:**
   ```bash
   php test_connection.php
   ```

### Database Configuration

The database connection settings are in `config/db_connect.php`:
- Host: localhost
- Database: college_auction
- Username: root
- Password: (empty by default)

Modify these settings if your XAMPP configuration is different.

### Project Structure

```
/auction-website/
├── config/
│   └── db_connect.php          # Database connection
├── sql/
│   └── database_schema.sql     # Database schema
├── setup_database.php          # Database setup script
├── test_connection.php         # Connection test script
└── README.md                   # This file
```

### Database Schema

The system uses three main tables:

- **users**: User accounts with authentication
- **items**: Auction items with bidding information
- **bids**: Bid history for all items

All tables include proper foreign key constraints and indexes for performance.

### Security Features

- PDO prepared statements for SQL injection prevention
- Password hashing using PHP's password_hash()
- Error handling with user-friendly messages
- Input sanitization (to be implemented in forms)

### Next Steps

After database setup, you can proceed with implementing:
1. User authentication system
2. Item creation and display
3. Bidding functionality
4. Frontend with Tailwind CSS