/**
 * DECEPTION MODULE - Level 4.0
 * Client-side deception and behavioral analysis
 */

class DeceptionModule {
    constructor() {
        this.honeytokens = {};
        this.behaviorData = {
            mouseMovements: [],
            keystrokeTiming: [],
            scrollEvents: [],
            focusEvents: [],
            formInteractions: []
        };
        
        this.init();
    }
    
    init() {
        this.generateHoneytokens();
        this.startBehaviorTracking();
        this.injectDeceptionElements();
        this.setupFormProtection();
    }
    
    generateHoneytokens() {
        // Generate honeytoken values
        this.honeytokens = {
            csrf_backup: 'ht_' + this.generateRandomString(32),
            debug_token: 'ht_' + this.generateRandomString(16),
            api_access: 'ht_' + this.generateRandomString(24)
        };
        
        // Set honeytoken cookies
        Object.entries(this.honeytokens).forEach(([name, value]) => {
            document.cookie = `ht_${name}=${value}; path=/; SameSite=Strict`;
        });
        
        // Inject honeytoken fields into forms
        this.injectHoneytokenFields();
    }
    
    generateRandomString(length) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
    
    injectHoneytokenFields() {
        // Create hidden honeytoken fields
        const fields = [
            { name: 'csrf_token_backup', value: this.honeytokens.csrf_backup },
            { name: 'debug_mode', value: this.honeytokens.debug_token },
            { name: 'api_secret', value: this.honeytokens.api_access }
        ];
        
        fields.forEach(field => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = field.name;
            input.value = field.value;
            input.className = 'honeytoken-field';
            
            // Add to all forms
            document.querySelectorAll('form').forEach(form => {
                if (!form.querySelector(`[name="${field.name}"]`)) {
                    form.appendChild(input.cloneNode(true));
                }
            });
        });
    }
    
    injectDeceptionElements() {
        // Add fake elements to confuse scrapers/bots
        this.addFakeLinks();
        this.addFakeForms();
        this.addTimeBasedTraps();
    }
    
    addFakeLinks() {
        const fakeLinks = [
            { text: 'Admin Panel', href: '/admin/login.php' },
            { text: 'Database Backup', href: '/backup/db_dump.sql' },
            { text: 'Config Files', href: '/config/settings.ini' }
        ];
        
        const fakeContainer = document.createElement('div');
        fakeContainer.style.display = 'none';
        fakeContainer.className = 'deception-links';
        
        fakeLinks.forEach(link => {
            const a = document.createElement('a');
            a.href = link.href;
            a.textContent = link.text;
            a.style.color = '#ff0000';
            a.style.textDecoration = 'none';
            a.onclick = (e) => {
                e.preventDefault();
                this.logDeceptionTrigger('fake_link_clicked', {
                    link: link.href,
                    text: link.text
                });
                return false;
            };
            fakeContainer.appendChild(a);
            fakeContainer.appendChild(document.createElement('br'));
        });
        
        document.body.appendChild(fakeContainer);
    }
    
    addFakeForms() {
        const fakeForm = document.createElement('form');
        fakeForm.style.display = 'none';
        fakeForm.id = 'fake_login_form';
        fakeForm.innerHTML = `
            <input type="text" name="admin_user" placeholder="Admin Username">
            <input type="password" name="admin_pass" placeholder="Admin Password">
            <button type="submit">Login as Admin</button>
        `;
        
        fakeForm.onsubmit = (e) => {
            e.preventDefault();
            this.logDeceptionTrigger('fake_form_submitted', {
                form: 'fake_admin_login',
                username: fakeForm.admin_user.value,
                password: fakeForm.admin_pass.value
            });
            return false;
        };
        
        document.body.appendChild(fakeForm);
    }
    
    addTimeBasedTraps() {
        // Trap for rapid form submission
        let lastSubmitTime = 0;
        
        document.querySelectorAll('form').forEach(form => {
            const originalSubmit = form.onsubmit;
            
            form.onsubmit = (e) => {
                const now = Date.now();
                const timeDiff = now - lastSubmitTime;
                
                if (timeDiff < 1000) { // Less than 1 second
                    this.logDeceptionTrigger('rapid_form_submission', {
                        time_diff: timeDiff,
                        form: form.id || 'unknown'
                    });
                    
                    // Add artificial delay
                    if (timeDiff < 500) {
                        e.preventDefault();
                        setTimeout(() => form.submit(), 2000);
                        return false;
                    }
                }
                
                lastSubmitTime = now;
                
                if (originalSubmit) {
                    return originalSubmit.call(form, e);
                }
            };
        });
    }
    
    startBehaviorTracking() {
        this.trackMouseMovements();
        this.trackKeystrokes();
        this.trackScrollBehavior();
        this.trackFocusChanges();
        this.trackFormInteractions();
    }
    
    trackMouseMovements() {
        let lastX, lastY, lastTime;
        
        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            
            if (lastX !== undefined && lastY !== undefined && lastTime !== undefined) {
                const distance = Math.sqrt(
                    Math.pow(e.clientX - lastX, 2) + 
                    Math.pow(e.clientY - lastY, 2)
                );
                const timeDiff = now - lastTime;
                
                if (timeDiff > 0) {
                    const speed = distance / timeDiff;
                    
                    this.behaviorData.mouseMovements.push({
                        x: e.clientX,
                        y: e.clientY,
                        speed: speed,
                        timestamp: now
                    });
                    
                    // Keep only last 100 movements
                    if (this.behaviorData.mouseMovements.length > 100) {
                        this.behaviorData.mouseMovements.shift();
                    }
                    
                    // Detect bot-like behavior (perfectly straight lines, constant speed)
                    this.analyzeMousePatterns();
                }
            }
            
            lastX = e.clientX;
            lastY = e.clientY;
            lastTime = now;
        });
    }
    
    trackKeystrokes() {
        let lastKeyTime;
        
        document.addEventListener('keydown', (e) => {
            const now = Date.now();
            
            if (lastKeyTime !== undefined) {
                const timeDiff = now - lastKeyTime;
                
                this.behaviorData.keystrokeTiming.push({
                    key: e.key,
                    timeDiff: timeDiff,
                    timestamp: now
                });
                
                // Keep only last 50 keystrokes
                if (this.behaviorData.keystrokeTiming.length > 50) {
                    this.behaviorData.keystrokeTiming.shift();
                }
                
                // Detect automated typing (perfect timing, no errors)
                this.analyzeTypingPatterns();
            }
            
            lastKeyTime = now;
        });
    }
    
    trackScrollBehavior() {
        let lastScrollTime;
        
        window.addEventListener('scroll', (e) => {
            const now = Date.now();
            
            if (lastScrollTime !== undefined) {
                const timeDiff = now - lastScrollTime;
                
                this.behaviorData.scrollEvents.push({
                    position: window.scrollY,
                    timeDiff: timeDiff,
                    timestamp: now
                });
            }
            
            lastScrollTime = now;
        });
    }
    
    trackFocusChanges() {
        document.addEventListener('focusin', (e) => {
            this.behaviorData.focusEvents.push({
                element: e.target.tagName,
                id: e.target.id || 'none',
                timestamp: Date.now()
            });
        });
    }
    
    trackFormInteractions() {
        document.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('focus', () => {
                this.behaviorData.formInteractions.push({
                    field: field.name || field.id,
                    type: field.type,
                    action: 'focus',
                    timestamp: Date.now()
                });
            });
            
            field.addEventListener('blur', () => {
                this.behaviorData.formInteractions.push({
                    field: field.name || field.id,
                    type: field.type,
                    action: 'blur',
                    timestamp: Date.now()
                });
            });
        });
    }
    
    analyzeMousePatterns() {
        if (this.behaviorData.mouseMovements.length < 10) return;
        
        const movements = this.behaviorData.mouseMovements.slice(-10);
        const speeds = movements.map(m => m.speed);
        const avgSpeed = speeds.reduce((a, b) => a + b) / speeds.length;
        
        // Check for unnatural consistency (bot-like)
        const variance = speeds.reduce((a, b) => a + Math.pow(b - avgSpeed, 2), 0) / speeds.length;
        
        if (variance < 0.001 && avgSpeed > 0.5) { // Too consistent and fast
            this.logDeceptionTrigger('unnatural_mouse_movement', {
                variance: variance,
                avg_speed: avgSpeed
            });
        }
    }
    
    analyzeTypingPatterns() {
        if (this.behaviorData.keystrokeTiming.length < 5) return;
        
        const timings = this.behaviorData.keystrokeTiming.slice(-5).map(k => k.timeDiff);
        const avgTiming = timings.reduce((a, b) => a + b) / timings.length;
        
        // Check for robotic timing (too consistent)
        const variance = timings.reduce((a, b) => a + Math.pow(b - avgTiming, 2), 0) / timings.length;
        
        if (variance < 10 && avgTiming > 0) { // Too consistent
            this.logDeceptionTrigger('robotic_typing_pattern', {
                variance: variance,
                avg_timing: avgTiming
            });
        }
    }
    
    setupFormProtection() {
        document.querySelectorAll('form').forEach(form => {
            // Add submission delay for bots
            form.addEventListener('submit', (e) => {
                const startTime = Date.now();
                form.dataset.submitStart = startTime;
                
                // Check for inhuman submission speed
                if (form.dataset.lastSubmit) {
                    const timeSinceLast = startTime - parseInt(form.dataset.lastSubmit);
                    if (timeSinceLast < 1000) { // Less than 1 second
                        this.logDeceptionTrigger('rapid_form_resubmission', {
                            time_since_last: timeSinceLast
                        });
                    }
                }
                
                form.dataset.lastSubmit = startTime;
            });
            
            // Track time spent on form
            let formStartTime;
            form.addEventListener('focusin', () => {
                if (!formStartTime) {
                    formStartTime = Date.now();
                }
            });
            
            form.addEventListener('submit', () => {
                if (formStartTime) {
                    const timeSpent = Date.now() - formStartTime;
                    
                    // Unusually fast form completion (bot)
                    if (timeSpent < 1000) { // Less than 1 second
                        this.logDeceptionTrigger('fast_form_completion', {
                            time_spent: timeSpent
                        });
                    }
                }
            });
        });
    }
    
    logDeceptionTrigger(type, data) {
        const logData = {
            type: type,
            data: data,
            timestamp: Date.now(),
            url: window.location.href,
            userAgent: navigator.userAgent,
            honeytokens: this.honeytokens
        };
        
        // Send to server
        fetch('/lab.php?action=log_deception', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(logData)
        }).catch(console.error);
        
        // Also log locally
        console.warn('Deception trigger:', type, data);
    }
    
    getBehaviorSummary() {
        return {
            mouseMovements: this.behaviorData.mouseMovements.length,
            keystrokes: this.behaviorData.keystrokeTiming.length,
            scrolls: this.behaviorData.scrollEvents.length,
            focusChanges: this.behaviorData.focusEvents.length,
            formInteractions: this.behaviorData.formInteractions.length,
            sessionDuration: Date.now() - (this.behaviorData.mouseMovements[0]?.timestamp || Date.now())
        };
    }
    
    sendBehaviorReport() {
        const report = {
            behaviorSummary: this.getBehaviorSummary(),
            honeytokens: this.honeytokens,
            sessionData: {
                startTime: this.behaviorData.mouseMovements[0]?.timestamp || Date.now(),
                endTime: Date.now(),
                pageViews: 1
            }
        };
        
        return fetch('/lab.php?action=behavior_report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(report)
        });
    }
}

// Initialize deception module
document.addEventListener('DOMContentLoaded', () => {
    window.deceptionModule = new DeceptionModule();
    
    // Send behavior report before page unload
    window.addEventListener('beforeunload', () => {
        if (window.deceptionModule) {
            window.deceptionModule.sendBehaviorReport().catch(() => {});
        }
    });
    
    // Periodically send behavior reports
    setInterval(() => {
        if (window.deceptionModule) {
            window.deceptionModule.sendBehaviorReport().catch(() => {});
        }
    }, 30000); // Every 30 seconds
});
