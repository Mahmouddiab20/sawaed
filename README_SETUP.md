# Sawaed Marketing Agency - Visitor IP Geolocation System

## Overview

This comprehensive visitor IP geolocation system captures visitor data, resolves their location using geolocation APIs, and stores lead information with full GDPR compliance. Perfect for marketing agencies to track leads and analyze visitor behavior.

## Features

### 🔍 **IP Detection & Geolocation**
- Robust IP detection (handles proxies, load balancers, CDNs)
- Multiple geolocation API support (ip-api.com, ipinfo.io, ipstack.com)
- Accurate country, region, city, and ISP detection
- Latitude/longitude coordinates for mapping

### 📊 **Lead Tracking & Analytics**
- Complete lead capture with geolocation data
- Form type tracking (contact, job applications, etc.)
- UTM parameter tracking for campaign analysis
- User agent and referrer tracking
- Session and visit duration tracking

### 🔒 **Privacy & Compliance**
- GDPR-compliant consent management
- Privacy banner with granular preferences
- Data retention policies
- Right to be forgotten (data deletion)
- Secure data storage and transmission

### 📈 **Admin Dashboard**
- Real-time analytics dashboard
- Geographic distribution charts
- Lead management interface
- Export capabilities
- Performance metrics

## Installation Guide

### 1. **Database Setup**

```sql
-- Create database
CREATE DATABASE sawaed_leads CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u username -p sawaed_leads < database/schema.sql
```

### 2. **Configuration**

Edit `config/database.php`:

```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sawaed_leads');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Geolocation API (optional - free tier available)
define('GEOLOCATION_API_PROVIDER', 'ipapi'); // ipapi, ipinfo, ipstack
define('GEOLOCATION_API_KEY', ''); // Leave empty for free services

// Privacy settings
define('REQUIRE_CONSENT', true);
define('DATA_RETENTION_DAYS', 365);
```

### 3. **File Permissions**

```bash
# Set proper permissions
chmod 755 uploads/
chmod 644 config/database.php
chmod 644 includes/ip_geolocation.php
```

### 4. **Web Server Configuration**

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1 [L]
```

#### Nginx
```nginx
location /api/ {
    try_files $uri $uri/ /api/index.php?$query_string;
}
```

## API Configuration

### Free Geolocation APIs

#### 1. **ip-api.com** (Recommended - Free)
- No API key required
- 1000 requests/minute limit
- Good accuracy
- No HTTPS for free tier

#### 2. **ipinfo.io**
- 50,000 requests/month free
- Requires API key
- HTTPS support
- Good accuracy

#### 3. **ipstack.com**
- 10,000 requests/month free
- Requires API key
- HTTPS support
- High accuracy

### API Setup Examples

```php
// For ipinfo.io
define('GEOLOCATION_API_PROVIDER', 'ipinfo');
define('GEOLOCATION_API_KEY', 'your_ipinfo_api_key');

// For ipstack.com
define('GEOLOCATION_API_PROVIDER', 'ipstack');
define('GEOLOCATION_API_KEY', 'your_ipstack_api_key');
```

## Usage Examples

### 1. **Basic Lead Capture**

```php
<?php
require_once 'config/database.php';
require_once 'includes/ip_geolocation.php';

$db = get_database_connection();
$geo = new IPGeolocation($db);

// Capture lead data
$form_data = [
    'name' => $_POST['name'],
    'email' => $_POST['email'],
    'message' => $_POST['message']
];

$success = $geo->capture_lead($form_data, 'contact');
?>
```

### 2. **Get Analytics Data**

```php
// Get 30-day analytics
$analytics = $geo->get_analytics(30);

// Get recent leads
$leads = $geo->get_recent_leads(50);
```

### 3. **Privacy Compliance**

```php
// Check user consent
$has_consent = $geo->check_consent();

// Set user consent
$geo->set_consent(true);
```

## Frontend Integration

### 1. **Add Privacy Banner**

```html
<!-- Include privacy banner script -->
<script src="js/privacy-banner.js"></script>
```

### 2. **Update Contact Forms**

```html
<form id="contactForm" novalidate>
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <textarea name="message" required></textarea>
    
    <!-- Consent checkbox -->
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="consent" required>
        <label class="form-check-label">
            أوافق على سياسة الخصوصية
        </label>
    </div>
    
    <button type="submit">إرسال</button>
</form>
```

### 3. **Form Submission Handler**

```javascript
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    const response = await fetch('/api/contact_handler.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert('تم إرسال الرسالة بنجاح!');
    } else {
        alert('حدث خطأ: ' + result.message);
    }
});
```

## Admin Dashboard

### 1. **Access Dashboard**

Navigate to `/admin/dashboard.php`

Default credentials:
- Username: `admin`
- Password: `sawaed2024`

**⚠️ Change these credentials immediately!**

### 2. **Dashboard Features**

- **Statistics Cards**: Total leads, contact forms, job applications
- **Geographic Charts**: Visitor distribution by country/region
- **Lead Management**: View, search, and delete leads
- **Analytics**: Form type distribution, consent rates
- **Real-time Updates**: Auto-refresh every 5 minutes

### 3. **Lead Management**

- View detailed lead information
- Geographic location data
- UTM campaign tracking
- Consent status
- Export capabilities

## Privacy & Legal Compliance

### 1. **GDPR Requirements**

- ✅ User consent collection
- ✅ Granular consent preferences
- ✅ Data retention policies
- ✅ Right to be forgotten
- ✅ Data portability
- ✅ Privacy by design

### 2. **Data Retention**

```php
// Automatic cleanup (run via cron)
$geo->cleanup_old_leads(365); // Keep data for 1 year
```

### 3. **Consent Management**

```javascript
// Check consent status
if (privacyBanner.hasConsentChoice()) {
    // User has made a choice
}

// Set consent preferences
privacyBanner.setConsent(true, {
    analytics: true,
    marketing: true,
    necessary: true
});
```

## Security Features

### 1. **Input Validation**
- XSS protection
- SQL injection prevention
- CSRF protection
- Rate limiting

### 2. **Data Security**
- Encrypted data transmission (HTTPS)
- Secure database connections
- Input sanitization
- Error logging

### 3. **Access Control**
- Admin authentication
- Session management
- IP-based restrictions (optional)

## Performance Optimization

### 1. **Caching**
- Geolocation API response caching
- Database query optimization
- Static asset caching

### 2. **Rate Limiting**
- API request limits
- Form submission limits
- IP-based throttling

### 3. **Database Optimization**
- Proper indexing
- Query optimization
- Data archiving

## Troubleshooting

### Common Issues

#### 1. **Geolocation Not Working**
```php
// Check API configuration
$geo = new IPGeolocation($db, 'your_api_key', 'ipapi');
$result = $geo->get_geolocation('8.8.8.8');
var_dump($result);
```

#### 2. **Database Connection Issues**
```php
// Test database connection
try {
    $db = get_database_connection();
    echo "Database connected successfully";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
}
```

#### 3. **Privacy Banner Not Showing**
```javascript
// Check if banner is initialized
console.log(window.privacyBanner);

// Manually show banner
privacyBanner.showBanner();
```

### Debug Mode

Enable debug logging in `config/database.php`:

```php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable database logging
define('ENABLE_DB_LOGGING', true);
```

## API Reference

### IPGeolocation Class

#### Methods

```php
// Get client IP
$ip = $geo->get_client_ip();

// Get geolocation data
$location = $geo->get_geolocation($ip);

// Capture lead
$success = $geo->capture_lead($form_data, 'contact');

// Set consent
$geo->set_consent(true);

// Get analytics
$analytics = $geo->get_analytics(30);

// Get recent leads
$leads = $geo->get_recent_leads(50);

// Cleanup old data
$deleted = $geo->cleanup_old_leads(365);
```

## Support & Maintenance

### 1. **Regular Maintenance**
- Database cleanup
- Log file rotation
- Security updates
- Performance monitoring

### 2. **Monitoring**
- Error logging
- Performance metrics
- API usage tracking
- Security alerts

### 3. **Backup**
- Database backups
- File system backups
- Configuration backups
- Disaster recovery plan

## License

This system is proprietary software for Sawaed Marketing Agency. All rights reserved.

## Contact

For technical support or questions:
- Email: sawaedflow@gmail.com
- Website: [Your Website URL]

---

**⚠️ Important Security Notes:**

1. Change default admin credentials immediately
2. Use HTTPS in production
3. Regular security updates
4. Monitor access logs
5. Backup data regularly
6. Test privacy compliance
7. Review data retention policies
