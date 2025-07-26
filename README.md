# MOSOFT MEMO SYSTEM

A comprehensive web-based memo management system designed for organizations to streamline internal communication and document management.

## 🌟 Features

### Core Functionality

- **User Authentication & Authorization** - Secure login system with role-based access control
- **Memo Composition & Distribution** - Create and send memos to multiple recipients
- **Inbox Management** - Organized inbox with read/unread status tracking
- **Acknowledgment System** - Track memo acknowledgments and reading status
- **File Attachments** - Support for document attachments with secure upload/download
- **PDF Generation** - Automatic PDF creation for memo archival and distribution
- **Forwarding System** - Forward memos to additional recipients
- **Department Management** - Organize users by departments and roles

### Administrative Features

- **User Management** - Add, edit, and manage system users
- **Department Administration** - Create and manage organizational departments
- **Category Management** - Organize memos by categories
- **Role Management** - Define user roles and permissions
- **System Reports** - Generate reports on memo activity and usage
- **Contact Directory** - Internal contact list management

### Security Features

- **Session Management** - Secure session handling with timeout protection
- **Input Validation** - Comprehensive input sanitization and validation
- **File Security** - Secure file upload with type validation
- **Access Control** - Role-based permissions for different system areas
- **SQL Injection Protection** - Prepared statements for database security

## 🛠️ Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **PDF Generation**: TCPDF Library
- **Frontend**: HTML5, CSS3, Bootstrap 5
- **JavaScript**: Vanilla JS for interactive elements
- **Package Management**: Composer

## 📋 Requirements

### System Requirements

- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: Version 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **PHP Extensions**:
  - `mysqli`
  - `session`
  - `fileinfo`
  - `gd` (optional, for image processing)

### Server Configuration

- PHP `upload_max_filesize`: 50M (recommended)
- PHP `post_max_size`: 55M (recommended)
- PHP `max_execution_time`: 300 (for PDF generation)
- MySQL `max_allowed_packet`: 64M

## 🚀 Installation

### 1. Clone/Download the Project

```bash
git clone [repository-url] memo-system
cd memo-system
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Database Setup

1. Create a MySQL database named `mars` (or customize in config.php)
2. Import the database schema:

```bash
mysql -u username -p mars < db.sql
```

### 4. Configuration

1. Copy and configure the database settings in `config.php`:

```php
$dbhostname = "localhost";
$dbusername = "your_db_username";
$dbpassword = "your_db_password";
$dbname = "mars";
```

2. Update the base URL and site title:

```php
$sitetitle = "Your Organization Memo System";
$baseurl = "http://your-domain.com";
```

### 5. Directory Permissions

Ensure the following directories are writable by the web server:

```bash
chmod 755 attachments/
chmod 755 pdf_exports/
```

### 6. Default Login

- **Username**: admin
- **Password**: (check the database or create a new user)

## 📁 Project Structure

```text
memo-system/
├── admin.php                 # Administrative interface
├── config.php               # Configuration settings
├── functions.php            # Core system functions
├── index.php               # Login page
├── dologin.php             # Main dashboard and routing
├── composer.json           # Composer dependencies
├── db.sql                  # Database schema
├── attachments/            # File upload directory
├── pdf_exports/            # Generated PDF storage
├── includes/               # Include files
│   └── header.php          # Common header
└── vendor/                 # Composer dependencies
    └── tecnickcom/tcpdf/   # PDF generation library
```

## 🔧 Configuration Options

### Database Configuration (`config.php`)

- `$dbhostname` - Database server hostname
- `$dbusername` - Database username
- `$dbpassword` - Database password
- `$dbname` - Database name
- `$tblprefix` - Table prefix (default: "mars")

### System Configuration

- `$sitetitle` - Application title
- `$baseurl` - Base URL for the application
- `$attachmentpath` - Path for file attachments

## 📊 Database Schema

### Main Tables

- **users** - User accounts and profiles
- **departments** - Organizational departments
- **memos** - Memo content and metadata
- **memo_recipients** - Memo distribution tracking
- **attachments** - File attachment references
- **categories** - Memo categorization
- **roles** - User role definitions

## 🎯 Usage Guide

### For Regular Users

1. **Login** - Access the system with your credentials
2. **Dashboard** - View unread memos and system overview
3. **Inbox** - Read and acknowledge received memos
4. **Compose** - Create and send new memos
5. **Sent Items** - Track sent memos and their status

### For Administrators

1. **User Management** - Add/edit users and assign roles
2. **Department Setup** - Create organizational structure
3. **System Reports** - Monitor system usage and activity
4. **Category Management** - Organize memo types

## 🔒 Security Features

- **Input Sanitization** - All user inputs are validated and sanitized
- **SQL Injection Prevention** - Prepared statements used throughout
- **File Upload Security** - File type validation and secure storage
- **Session Security** - Secure session management with timeout
- **Access Control** - Role-based permissions system

## 📄 PDF Generation

The system includes automatic PDF generation for memos:

- **Automatic Creation** - PDFs generated when memos are sent
- **Manual Generation** - Generate PDFs on-demand
- **Professional Format** - Branded PDF output with memo details
- **Secure Downloads** - Protected PDF access

For detailed PDF feature documentation, see `PDF_FEATURE_README.md`.

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config.php`
   - Ensure MySQL service is running
   - Verify database exists and is accessible

2. **File Upload Issues**
   - Check `attachments/` directory permissions
   - Verify PHP upload settings (`upload_max_filesize`, `post_max_size`)

3. **PDF Generation Problems**
   - Ensure `pdf_exports/` directory is writable
   - Check PHP memory limit and execution time
   - Verify TCPDF library is installed via Composer

4. **Session Issues**
   - Check PHP session configuration
   - Ensure cookies are enabled in browser
   - Verify session directory permissions

## 🔄 Updates and Maintenance

### Regular Maintenance

- Monitor `attachments/` and `pdf_exports/` directory sizes
- Regular database backups
- Review and clean old session files
- Update dependencies via Composer

### Security Updates

- Keep PHP and MySQL updated
- Monitor for security vulnerabilities in dependencies
- Regular security audits of user permissions

## 📞 Support

For technical support or questions:

- Review the troubleshooting section above
- Check system logs for error messages
- Ensure all requirements are met
- Verify configuration settings

## 📄 License

This project is proprietary software. All rights reserved.

## 🤝 Contributing

This is a private/proprietary system. Contact the system administrator for any modifications or enhancements.

---

**Version**: 2.0  
**Last Updated**: July 26, 2025  
**Developed by**: MOSOFT
