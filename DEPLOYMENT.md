# 🚀 Deployment Guide - InfinityFree

This guide explains how to deploy the MOSOFT MEMO SYSTEM to InfinityFree using GitHub Actions.

## 📋 Prerequisites

1. **InfinityFree Account** - Free hosting account at [infinityfree.net](https://infinityfree.net)
2. **GitHub Repository** - Your memo system code in a GitHub repository
3. **FTP Credentials** - Available from your InfinityFree control panel

## 🔧 Setup Instructions

### 1. Configure GitHub Secrets

In your GitHub repository, go to **Settings > Secrets and variables > Actions** and add these secrets:

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `FTP_SERVER` | Your InfinityFree FTP server | `ftpupload.net` |
| `FTP_USERNAME` | Your FTP username | `if0_12345678` |
| `FTP_PASSWORD` | Your FTP password | `your_ftp_password` |

### 2. InfinityFree Setup

#### A. Create Database
1. Go to your InfinityFree control panel
2. Navigate to **MySQL Databases**
3. Create a new database and note the credentials:
   - Database name (e.g., `if0_12345678_memo`)
   - Username (e.g., `if0_12345678`)
   - Password
   - Hostname (usually `sql200.infinityfree.com` or similar)

#### B. Import Database
1. Go to **phpMyAdmin** in your control panel
2. Select your database
3. Go to **Import** tab
4. Upload the `db.sql` file from your repository
5. Click **Go** to import

### 3. Configuration Files

#### A. Create config.php
After deployment, create `config.php` on your server with:

```php
<?php
// Security check
if (preg_match("/config\.php/i", $_SERVER['PHP_SELF'])) {
    Header("Location: index.php");
    die();
}

// Upload paths
$attachmentpath = __DIR__ . DIRECTORY_SEPARATOR . "attachments" . DIRECTORY_SEPARATOR;

// Site configuration
$sitetitle = "MOSOFT MEMO SYSTEM";
$baseurl = "https://yourdomain.infinityfreeapp.com"; // Replace with your domain

// Database configuration - IMPORTANT: Use your InfinityFree database details
$dbhostname = "sql200.infinityfree.com"; // Your DB hostname
$dbusername = "if0_12345678";            // Your DB username
$dbpassword = "your_db_password";        // Your DB password
$dbname = "if0_12345678_memo";          // Your DB name
$tblprefix = "mars";
?>
```

#### B. Set Directory Permissions
Ensure these directories are writable (usually 755 or 777):
- `attachments/`
- `pdf_exports/`

## 🔄 Deployment Process

### Automatic Deployment
The workflow automatically triggers when you:
- Push code to the `main` branch
- Manually trigger it from GitHub Actions tab

### Manual Deployment
1. Go to your repository on GitHub
2. Click **Actions** tab
3. Select **Deploy to InfinityFree** workflow
4. Click **Run workflow**

## 📁 What Gets Deployed

### ✅ Included:
- All PHP source files
- Composer dependencies (vendor/)
- CSS, JavaScript, and image files
- Database schema (db.sql)
- Security files (.htaccess)

### ❌ Excluded:
- Development files (test_*.php, debug_*.php)
- User uploads (attachments/, pdf_exports/)
- Configuration files (config.php)
- Git files and documentation
- Temporary and cache files

## 🔒 Security Considerations

### 1. Protected Directories
The deployment automatically creates:
- `.htaccess` files to prevent directory browsing
- `index.php` redirects in sensitive directories

### 2. Configuration Security
- `config.php` is excluded from deployment for security
- You must manually create it with your database credentials

### 3. File Permissions
Ensure proper permissions:
```
Files: 644
Directories: 755
attachments/: 755 (or 777 if needed)
pdf_exports/: 755 (or 777 if needed)
```

## 🐛 Troubleshooting

### Common Issues:

1. **FTP Connection Failed**
   - Verify FTP credentials in GitHub Secrets
   - Check InfinityFree FTP server address

2. **Database Connection Error**
   - Verify database credentials in `config.php`
   - Ensure database is imported correctly

3. **File Upload Issues**
   - Check directory permissions
   - Verify `attachments/` directory exists and is writable

4. **Composer Dependencies Missing**
   - Workflow installs dependencies automatically
   - Check deployment logs if issues occur

### Deployment Logs
Check GitHub Actions logs:
1. Go to **Actions** tab in your repository
2. Click on the latest deployment run
3. Expand each step to see detailed logs

## 📞 Support

### InfinityFree Limitations:
- Max file size: 10MB
- PHP memory limit: 256MB
- No SSH access
- Limited cron job functionality

### Getting Help:
1. Check GitHub Actions logs for deployment errors
2. Review InfinityFree documentation
3. Test locally before deploying

## 🎉 Post-Deployment Checklist

After successful deployment:

- [ ] Verify website loads at your InfinityFree domain
- [ ] Test login with admin credentials
- [ ] Test memo creation and sending
- [ ] Test file upload functionality
- [ ] Test PDF generation
- [ ] Configure any additional settings

Your MOSOFT MEMO SYSTEM should now be live on InfinityFree! 🚀
