# 🚀 Sawaed Leads Tracker - Netlify Setup Guide

## Overview
This guide will help you set up the visitor IP geolocation and lead tracking system on Netlify with Supabase as the database.

## 📋 Prerequisites
- Netlify account
- Supabase account (free tier available)
- Basic knowledge of web development

## 🗄️ Step 1: Set Up Supabase Database

### 1.1 Create Supabase Project
1. Go to [supabase.com](https://supabase.com)
2. Sign up/login and create a new project
3. Choose a region close to your users
4. Wait for the project to be created

### 1.2 Create Database Tables
Go to the SQL Editor in your Supabase dashboard and run this SQL:

```sql
-- Create leads table
CREATE TABLE leads (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    message TEXT,
    subject VARCHAR(255),
    form_type VARCHAR(50) DEFAULT 'contact',
    ip_address VARCHAR(45),
    country VARCHAR(100),
    region VARCHAR(100),
    city VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    isp VARCHAR(255),
    timezone VARCHAR(50),
    user_agent TEXT,
    referrer TEXT,
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    consent_given BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Create consent records table
CREATE TABLE consent_records (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    consent_type VARCHAR(50) NOT NULL,
    consent_given BOOLEAN NOT NULL,
    consent_timestamp TIMESTAMP DEFAULT NOW(),
    user_agent TEXT,
    country VARCHAR(100)
);

-- Create indexes for better performance
CREATE INDEX idx_leads_email ON leads(email);
CREATE INDEX idx_leads_ip_address ON leads(ip_address);
CREATE INDEX idx_leads_country ON leads(country);
CREATE INDEX idx_leads_form_type ON leads(form_type);
CREATE INDEX idx_leads_created_at ON leads(created_at);
CREATE INDEX idx_consent_ip_address ON consent_records(ip_address);
CREATE INDEX idx_consent_type ON consent_records(consent_type);
```

### 1.3 Get Supabase Credentials
1. Go to Settings > API in your Supabase dashboard
2. Copy your:
   - Project URL
   - Anon/Public Key

## 🚀 Step 2: Deploy to Netlify

### 2.1 Upload Files
Upload all the files to your Netlify site:
- `netlify/functions/` folder
- `netlify/_redirects`
- `netlify/_headers`
- `netlify.toml`
- `package.json`
- `admin-dashboard.html`

### 2.2 Set Environment Variables
In your Netlify dashboard:
1. Go to Site Settings > Environment Variables
2. Add these variables:

```
SUPABASE_URL=your_supabase_project_url
SUPABASE_ANON_KEY=your_supabase_anon_key
ADMIN_TOKEN=sawaed-admin-2024
```

### 2.3 Install Dependencies
In your Netlify dashboard:
1. Go to Site Settings > Build & Deploy
2. Set Build Command: `npm install && npm run build`
3. Set Publish Directory: `.`

## 🔧 Step 3: Update Your Contact Form

### 3.1 Update contact.html
Replace your contact form with this:

```html
<form id="contactForm" novalidate>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control bg-secondary border-0" 
                       id="name" name="name" placeholder="اسمك" required>
                <label for="name">اسمك *</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating">
                <input type="email" class="form-control bg-secondary border-0" 
                       id="email" name="email" placeholder="بريدك الإلكتروني" required>
                <label for="email">بريدك الإلكتروني *</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-floating">
                <input type="tel" class="form-control bg-secondary border-0" 
                       id="phone" name="phone" placeholder="رقم الهاتف">
                <label for="phone">رقم الهاتف</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-floating">
                <input type="text" class="form-control bg-secondary border-0" 
                       id="subject" name="subject" placeholder="الموضوع" required>
                <label for="subject">الموضوع *</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-floating">
                <textarea class="form-control bg-secondary border-0" 
                          id="message" name="message" placeholder="اكتب رسالتك هنا" 
                          style="height: 150px;" required></textarea>
                <label for="message">الرسالة *</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="consent" name="consent" required>
                <label class="form-check-label" for="consent">
                    أوافق على سياسة الخصوصية وجمع البيانات لأغراض التسويق *
                </label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-outline-primary border-2 w-100 py-3" type="submit">
                إرسال الرسالة
            </button>
        </div>
    </div>
</form>
```

### 3.2 Add JavaScript for Form Handling
Add this script to your contact.html:

```javascript
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('/.netlify/functions/contact-handler', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('تم إرسال رسالتك بنجاح!');
            this.reset();
        } else {
            alert('حدث خطأ: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ في إرسال الرسالة');
    }
});
```

## 📊 Step 4: Access Your Dashboard

### 4.1 Dashboard URL
Your dashboard will be available at:
```
https://yourdomain.netlify.app/admin-dashboard.html
```

### 4.2 Login Credentials
- **Username:** `admin`
- **Password:** `sawaed2024`

⚠️ **Change these credentials immediately after first login!**

## 🔍 Step 5: Test the System

### 5.1 Test Form Submission
1. Go to your contact page
2. Fill out and submit the form
3. Check if the lead appears in your dashboard

### 5.2 Test Dashboard
1. Go to `/admin-dashboard.html`
2. Login with the credentials
3. Verify you can see leads and analytics

## 🛠️ Troubleshooting

### Common Issues:

#### 1. Functions Not Working
- Check environment variables are set correctly
- Verify Supabase credentials
- Check Netlify function logs

#### 2. Database Connection Issues
- Verify Supabase URL and key
- Check if tables exist in Supabase
- Test database connection in Supabase dashboard

#### 3. CORS Issues
- Check `_headers` file is uploaded
- Verify redirects in `_redirects` file

#### 4. Form Not Submitting
- Check browser console for errors
- Verify form action points to correct function
- Test with simple form first

## 📈 Features You'll Get

### ✅ Lead Tracking
- Complete visitor information
- Geographic location data
- Form type classification
- Consent tracking

### ✅ Analytics Dashboard
- Real-time statistics
- Geographic distribution charts
- Lead management interface
- Export capabilities

### ✅ Privacy Compliance
- GDPR-compliant consent management
- Data retention policies
- Right to be forgotten

## 🔒 Security Notes

1. **Change default admin credentials**
2. **Use HTTPS (Netlify provides this automatically)**
3. **Regular security updates**
4. **Monitor access logs**
5. **Backup data regularly**

## 📞 Support

If you need help:
- Check Netlify function logs
- Verify Supabase database connection
- Test with simple form first
- Contact: sawaedflow@gmail.com

---

**🎉 Congratulations!** You now have a complete visitor IP geolocation and lead tracking system running on Netlify!
