# إعداد البيانات الحقيقية في Netlify Dashboard

## الخطوة 1: إعداد متغيرات البيئة في Netlify

### 1.1 الحصول على Site ID و Access Token

1. **اذهب إلى Netlify Dashboard**: https://app.netlify.com/
2. **اختر موقعك**: sawaed4.netlify.app
3. **اذهب إلى Site Settings > Environment Variables**

### 1.2 إضافة المتغيرات المطلوبة

أضف المتغيرات التالية في Netlify:

```
NETLIFY_SITE_ID = [Site ID من إعدادات الموقع]
NETLIFY_ACCESS_TOKEN = [Personal Access Token]
ADMIN_TOKEN = sawaed-admin-2024
```

### 1.3 الحصول على Personal Access Token

1. **اذهب إلى**: https://app.netlify.com/user/applications#personal-access-tokens
2. **اضغط على "New access token"**
3. **أدخل اسم للتوكن**: "Sawaed Dashboard"
4. **انسخ التوكن** واحفظه في متغير البيئة

## الخطوة 2: إعداد النماذج في الموقع

### 2.1 إضافة Netlify Forms إلى النماذج

تأكد من أن جميع النماذج في موقعك تحتوي على:

```html
<form name="contact" method="POST" data-netlify="true" netlify-honeypot="bot-field">
    <input type="hidden" name="form-name" value="contact" />
    <p class="hidden">
        <label>Don't fill this out if you're human: <input name="bot-field" /></label>
    </p>
    
    <!-- باقي حقول النموذج -->
    <input type="text" name="name" placeholder="الاسم" required>
    <input type="email" name="email" placeholder="البريد الإلكتروني" required>
    <input type="tel" name="phone" placeholder="الهاتف">
    <input type="text" name="subject" placeholder="الموضوع" required>
    <textarea name="message" placeholder="الرسالة" required></textarea>
    
    <button type="submit">إرسال</button>
</form>
```

### 2.2 اختبار النماذج

1. **اذهب إلى موقعك**: https://sawaed4.netlify.app/
2. **املأ النماذج** وأرسل بعض البيانات التجريبية
3. **تحقق من Netlify Dashboard > Forms** لرؤية البيانات المرسلة

## الخطوة 3: استخدام Dashboard الجديد

### 3.1 رفع الملفات

1. **ارفع `netlify-dashboard.html`** إلى موقعك
2. **تأكد من وجود مجلد `netlify/functions/`** مع الملفات المطلوبة

### 3.2 الوصول إلى Dashboard

1. **اذهب إلى**: https://sawaed4.netlify.app/netlify-dashboard.html
2. **سجل الدخول** باستخدام:
   - اسم المستخدم: `admin`
   - كلمة المرور: `sawaed2024`

### 3.3 التحقق من البيانات

- **إذا ظهرت "بيانات حقيقية من Netlify"** في الأعلى = البيانات الحقيقية تعمل
- **إذا ظهرت "بيانات تجريبية"** = هناك مشكلة في الإعداد

## الخطوة 4: استكشاف الأخطاء

### 4.1 فحص Console

1. **افتح Developer Tools** (F12)
2. **اذهب إلى Console**
3. **ابحث عن رسائل الخطأ**

### 4.2 فحص Netlify Functions

1. **اذهب إلى Netlify Dashboard > Functions**
2. **تحقق من logs** للدوال
3. **تأكد من أن المتغيرات موجودة**

### 4.3 اختبار API مباشرة

يمكنك اختبار API مباشرة:

```
GET https://sawaed4.netlify.app/.netlify/functions/simple-dashboard
Headers: Authorization: Bearer sawaed-admin-2024
```

## الخطوة 5: إضافة المزيد من النماذج

### 5.1 نموذج الاستشارة

```html
<form name="consultation" method="POST" data-netlify="true" netlify-honeypot="bot-field">
    <input type="hidden" name="form-name" value="consultation" />
    <!-- حقول النموذج -->
</form>
```

### 5.2 نموذج طلب الوظيفة

```html
<form name="job_application" method="POST" data-netlify="true" netlify-honeypot="bot-field">
    <input type="hidden" name="form-name" value="job_application" />
    <!-- حقول النموذج -->
</form>
```

## الخطوة 6: تخصيص Dashboard

### 6.1 إضافة حقول جديدة

عدّل `netlify/functions/simple-dashboard.js` لإضافة حقول جديدة:

```javascript
// إضافة حقول جديدة
name: submission.data.name || 'غير محدد',
email: submission.data.email || 'غير محدد',
phone: submission.data.phone || 'غير محدد',
company: submission.data.company || 'غير محدد', // حقل جديد
service: submission.data.service || 'غير محدد', // حقل جديد
```

### 6.2 إضافة فلاتر

يمكنك إضافة فلاتر للبيانات حسب:
- نوع النموذج
- التاريخ
- الخدمة المطلوبة

## نصائح مهمة

1. **احفظ نسخة احتياطية** من البيانات المهمة
2. **اختبر النماذج** قبل النشر
3. **راقب الأداء** في Netlify Analytics
4. **حدث Dashboard** بانتظام

## الدعم

إذا واجهت مشاكل:

1. **تحقق من Console** للأخطاء
2. **راجع Netlify Functions logs**
3. **تأكد من صحة المتغيرات**
4. **اختبر النماذج** يدوياً

---

**ملاحظة**: هذا الدليل سيساعدك في الحصول على بيانات حقيقية في Dashboard الخاص بك على Netlify.
