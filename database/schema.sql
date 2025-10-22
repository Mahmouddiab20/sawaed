-- Sawaed Marketing Agency - Lead Tracking Database Schema
-- This schema stores visitor data with geolocation information for marketing analytics

-- Create database (uncomment if needed)
-- CREATE DATABASE sawaed_leads CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE sawaed_leads;

-- Leads table - stores all form submissions with geolocation data
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Form data
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    message TEXT,
    form_type ENUM('contact', 'job_application', 'newsletter', 'consultation') DEFAULT 'contact',
    
    -- IP and geolocation data
    ip_address VARCHAR(45) NOT NULL, -- IPv6 compatible
    country VARCHAR(100),
    region VARCHAR(100),
    city VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    isp VARCHAR(255),
    timezone VARCHAR(50),
    
    -- Tracking data
    user_agent TEXT,
    referrer TEXT,
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    
    -- Privacy compliance
    consent_given BOOLEAN DEFAULT FALSE,
    consent_timestamp TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_country (country),
    INDEX idx_form_type (form_type),
    INDEX idx_created_at (created_at),
    INDEX idx_utm_source (utm_source),
    INDEX idx_consent (consent_given)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Page visits table - tracks page views for analytics
CREATE TABLE IF NOT EXISTS page_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    page_url VARCHAR(500) NOT NULL,
    page_title VARCHAR(255),
    referrer TEXT,
    user_agent TEXT,
    country VARCHAR(100),
    region VARCHAR(100),
    city VARCHAR(100),
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    session_id VARCHAR(100),
    visit_duration INT DEFAULT 0, -- in seconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_ip_address (ip_address),
    INDEX idx_page_url (page_url),
    INDEX idx_country (country),
    INDEX idx_created_at (created_at),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent tracking table - for GDPR compliance
CREATE TABLE IF NOT EXISTS consent_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    consent_type ENUM('analytics', 'marketing', 'necessary') NOT NULL,
    consent_given BOOLEAN NOT NULL,
    consent_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_agent TEXT,
    country VARCHAR(100),
    
    INDEX idx_ip_address (ip_address),
    INDEX idx_consent_type (consent_type),
    INDEX idx_consent_timestamp (consent_timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics summary table - for faster reporting
CREATE TABLE IF NOT EXISTS analytics_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    total_visitors INT DEFAULT 0,
    total_leads INT DEFAULT 0,
    contact_leads INT DEFAULT 0,
    job_leads INT DEFAULT 0,
    newsletter_leads INT DEFAULT 0,
    consultation_leads INT DEFAULT 0,
    top_country VARCHAR(100),
    top_region VARCHAR(100),
    top_city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_date (date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create views for common queries
CREATE OR REPLACE VIEW lead_analytics AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_leads,
    COUNT(DISTINCT ip_address) as unique_visitors,
    COUNT(CASE WHEN form_type = 'contact' THEN 1 END) as contact_leads,
    COUNT(CASE WHEN form_type = 'job_application' THEN 1 END) as job_leads,
    COUNT(CASE WHEN form_type = 'newsletter' THEN 1 END) as newsletter_leads,
    COUNT(CASE WHEN form_type = 'consultation' THEN 1 END) as consultation_leads,
    COUNT(CASE WHEN consent_given = 1 THEN 1 END) as consented_leads
FROM leads 
GROUP BY DATE(created_at)
ORDER BY date DESC;

CREATE OR REPLACE VIEW geographic_analytics AS
SELECT 
    country,
    region,
    city,
    COUNT(*) as lead_count,
    COUNT(DISTINCT ip_address) as unique_visitors,
    COUNT(CASE WHEN form_type = 'contact' THEN 1 END) as contact_leads,
    COUNT(CASE WHEN form_type = 'job_application' THEN 1 END) as job_leads
FROM leads 
WHERE country IS NOT NULL
GROUP BY country, region, city
ORDER BY lead_count DESC;

-- Insert sample data (optional - remove in production)
INSERT INTO leads (name, email, phone, message, form_type, ip_address, country, region, city, latitude, longitude, isp, timezone, user_agent, consent_given) VALUES
('أحمد محمد', 'ahmed@example.com', '+966501234567', 'أريد استشارة تسويقية', 'contact', '192.168.1.1', 'Saudi Arabia', 'Riyadh', 'Riyadh', 24.7136, 46.6753, 'STC', 'Asia/Riyadh', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 1),
('فاطمة العلي', 'fatima@example.com', '+966502345678', 'تقدم لوظيفة مصمم جرافيك', 'job_application', '192.168.1.2', 'Saudi Arabia', 'Jeddah', 'Jeddah', 21.4858, 39.1925, 'Mobily', 'Asia/Riyadh', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', 1),
('محمد السعد', 'mohammed@example.com', '+966503456789', 'استفسار عن خدمات التسويق العقاري', 'consultation', '192.168.1.3', 'Saudi Arabia', 'Dammam', 'Dammam', 26.4207, 50.0888, 'Zain', 'Asia/Riyadh', 'Mozilla/5.0 (Android 10; Mobile; rv:68.0) Gecko/68.0 Firefox/68.0', 0);
