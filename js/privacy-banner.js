/**
 * GDPR Privacy Banner for Sawaed Marketing Agency
 * 
 * This script handles the privacy consent banner and user preferences
 * for GDPR compliance and lead tracking.
 */

class PrivacyBanner {
    constructor() {
        this.bannerId = 'privacy-banner';
        this.consentKey = 'sawaed_consent';
        this.preferencesKey = 'sawaed_preferences';
        this.init();
    }
    
    init() {
        // Check if user has already made a choice
        if (this.hasConsentChoice()) {
            this.hideBanner();
            return;
        }
        
        // Show banner if no choice made
        this.showBanner();
        this.bindEvents();
    }
    
    hasConsentChoice() {
        return localStorage.getItem(this.consentKey) !== null || 
               this.getCookie(this.consentKey) !== null;
    }
    
    showBanner() {
        // Create banner HTML
        const bannerHTML = `
            <div id="${this.bannerId}" class="privacy-banner">
                <div class="privacy-banner-content">
                    <div class="privacy-banner-text">
                        <h4>سياسة الخصوصية والبيانات</h4>
                        <p>نحن نستخدم ملفات تعريف الارتباط وتقنيات التتبع لتحسين تجربتك على موقعنا وتقديم خدمات تسويقية مخصصة. 
                        <a href="#" onclick="privacyBanner.showDetails()">اقرأ المزيد</a></p>
                    </div>
                    <div class="privacy-banner-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="privacyBanner.acceptAll()">
                            قبول الكل
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="privacyBanner.showPreferences()">
                            تخصيص
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="privacyBanner.rejectAll()">
                            رفض الكل
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Add banner to page
        document.body.insertAdjacentHTML('beforeend', bannerHTML);
        
        // Add CSS if not already added
        this.addStyles();
    }
    
    hideBanner() {
        const banner = document.getElementById(this.bannerId);
        if (banner) {
            banner.remove();
        }
    }
    
    acceptAll() {
        this.setConsent(true, {
            necessary: true,
            analytics: true,
            marketing: true,
            preferences: true
        });
        this.hideBanner();
        this.trackConsent('accepted_all');
    }
    
    rejectAll() {
        this.setConsent(false, {
            necessary: true,
            analytics: false,
            marketing: false,
            preferences: false
        });
        this.hideBanner();
        this.trackConsent('rejected_all');
    }
    
    showPreferences() {
        this.showPreferencesModal();
    }
    
    setConsent(consent, preferences = {}) {
        // Set consent in localStorage
        localStorage.setItem(this.consentKey, consent ? 'accepted' : 'declined');
        localStorage.setItem(this.preferencesKey, JSON.stringify(preferences));
        
        // Set consent cookie
        const expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
        document.cookie = `${this.consentKey}=${consent ? 'accepted' : 'declined'}; expires=${expires.toUTCString()}; path=/; SameSite=Strict`;
        
        // Send consent to server
        this.sendConsentToServer(consent, preferences);
        
        // Trigger consent event
        this.triggerConsentEvent(consent, preferences);
    }
    
    sendConsentToServer(consent, preferences) {
        fetch('/api/consent_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                consent: consent,
                preferences: preferences,
                timestamp: new Date().toISOString()
            })
        }).catch(error => {
            console.error('Failed to send consent to server:', error);
        });
    }
    
    trackConsent(action) {
        // Track consent action for analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'consent_action', {
                'event_category': 'privacy',
                'event_label': action
            });
        }
    }
    
    triggerConsentEvent(consent, preferences) {
        // Dispatch custom event for other scripts
        const event = new CustomEvent('privacyConsentChanged', {
            detail: {
                consent: consent,
                preferences: preferences
            }
        });
        document.dispatchEvent(event);
    }
    
    showPreferencesModal() {
        const modalHTML = `
            <div class="modal fade" id="privacyPreferencesModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">إعدادات الخصوصية</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="privacy-preference-item">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="prefNecessary" checked disabled>
                                    <label class="form-check-label" for="prefNecessary">
                                        <strong>الضرورية</strong>
                                        <small class="text-muted d-block">مطلوبة لتشغيل الموقع</small>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="privacy-preference-item">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="prefAnalytics">
                                    <label class="form-check-label" for="prefAnalytics">
                                        <strong>التحليلات</strong>
                                        <small class="text-muted d-block">لتحسين أداء الموقع</small>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="privacy-preference-item">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="prefMarketing">
                                    <label class="form-check-label" for="prefMarketing">
                                        <strong>التسويق</strong>
                                        <small class="text-muted d-block">لإرسال عروض مخصصة</small>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="privacy-preference-item">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="prefPreferences">
                                    <label class="form-check-label" for="prefPreferences">
                                        <strong>التفضيلات</strong>
                                        <small class="text-muted d-block">لحفظ إعداداتك</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="button" class="btn btn-primary" onclick="privacyBanner.savePreferences()">حفظ الإعدادات</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('privacyPreferencesModal'));
        modal.show();
        
        // Load current preferences
        this.loadPreferences();
    }
    
    loadPreferences() {
        const preferences = JSON.parse(localStorage.getItem(this.preferencesKey) || '{}');
        
        document.getElementById('prefAnalytics').checked = preferences.analytics || false;
        document.getElementById('prefMarketing').checked = preferences.marketing || false;
        document.getElementById('prefPreferences').checked = preferences.preferences || false;
    }
    
    savePreferences() {
        const preferences = {
            necessary: true,
            analytics: document.getElementById('prefAnalytics').checked,
            marketing: document.getElementById('prefMarketing').checked,
            preferences: document.getElementById('prefPreferences').checked
        };
        
        const hasAnyConsent = preferences.analytics || preferences.marketing || preferences.preferences;
        
        this.setConsent(hasAnyConsent, preferences);
        this.hideBanner();
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('privacyPreferencesModal'));
        modal.hide();
        
        this.trackConsent('custom_preferences');
    }
    
    showDetails() {
        // Show detailed privacy policy
        window.open('/privacy-policy.html', '_blank');
    }
    
    bindEvents() {
        // Listen for consent changes
        document.addEventListener('privacyConsentChanged', (event) => {
            const { consent, preferences } = event.detail;
            
            // Enable/disable tracking based on consent
            if (preferences.analytics) {
                this.enableAnalytics();
            } else {
                this.disableAnalytics();
            }
            
            if (preferences.marketing) {
                this.enableMarketing();
            } else {
                this.disableMarketing();
            }
        });
    }
    
    enableAnalytics() {
        // Enable Google Analytics or other analytics
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'analytics_storage': 'granted'
            });
        }
    }
    
    disableAnalytics() {
        // Disable analytics
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'analytics_storage': 'denied'
            });
        }
    }
    
    enableMarketing() {
        // Enable marketing tracking
        console.log('Marketing tracking enabled');
    }
    
    disableMarketing() {
        // Disable marketing tracking
        console.log('Marketing tracking disabled');
    }
    
    addStyles() {
        if (document.getElementById('privacy-banner-styles')) {
            return;
        }
        
        const styles = `
            <style id="privacy-banner-styles">
                .privacy-banner {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                    color: white;
                    padding: 20px;
                    z-index: 9999;
                    box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
                    border-top: 3px solid #007bff;
                }
                
                .privacy-banner-content {
                    max-width: 1200px;
                    margin: 0 auto;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 20px;
                }
                
                .privacy-banner-text h4 {
                    margin: 0 0 10px 0;
                    color: #007bff;
                }
                
                .privacy-banner-text p {
                    margin: 0;
                    font-size: 14px;
                    line-height: 1.5;
                }
                
                .privacy-banner-text a {
                    color: #007bff;
                    text-decoration: underline;
                }
                
                .privacy-banner-actions {
                    display: flex;
                    gap: 10px;
                    flex-shrink: 0;
                }
                
                .privacy-preference-item {
                    padding: 15px 0;
                    border-bottom: 1px solid #eee;
                }
                
                .privacy-preference-item:last-child {
                    border-bottom: none;
                }
                
                @media (max-width: 768px) {
                    .privacy-banner-content {
                        flex-direction: column;
                        text-align: center;
                    }
                    
                    .privacy-banner-actions {
                        flex-wrap: wrap;
                        justify-content: center;
                    }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', styles);
    }
    
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
}

// Initialize privacy banner when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.privacyBanner = new PrivacyBanner();
});
