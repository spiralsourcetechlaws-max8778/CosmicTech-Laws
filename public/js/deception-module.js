class DeceptionClient {
    constructor() {
        this.honeytokens = {};
        this.fingerprint = {};
        this.anomalies = [];
        this.init();
    }
    
    init() {
        this.collectFingerprint();
        this.setupHoneytokens();
        this.monitorUserBehavior();
        this.detectAutomation();
    }
    
    collectFingerprint() {
        this.fingerprint = {
            // Hardware
            screen: `${screen.width}x${screen.height}`,
            colorDepth: screen.colorDepth,
            pixelRatio: window.devicePixelRatio,
            orientation: screen.orientation.type,
            
            // Browser
            userAgent: navigator.userAgent,
            language: navigator.language,
            languages: navigator.languages,
            platform: navigator.platform,
            
            // Network
            connection: navigator.connection ? {
                effectiveType: navigator.connection.effectiveType,
                rtt: navigator.connection.rtt,
                downlink: navigator.connection.downlink
            } : null,
            
            // Time
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            timezoneOffset: new Date().getTimezoneOffset(),
            locale: Intl.DateTimeFormat().resolvedOptions().locale,
            
            // Canvas fingerprinting
            canvas: this.getCanvasFingerprint(),
            webgl: this.getWebGLFingerprint(),
            audio: this.getAudioFingerprint(),
            
            // Fonts
            fonts: this.getFontList(),
            
            // Plugins
            plugins: Array.from(navigator.plugins).map(p => ({
                name: p.name,
                description: p.description,
                filename: p.filename
            })),
            
            // Touch support
            touch: 'ontouchstart' in window,
            maxTouchPoints: navigator.maxTouchPoints || 0,
            
            // Storage
            localStorage: !!window.localStorage,
            sessionStorage: !!window.sessionStorage,
            indexedDB: !!window.indexedDB,
            
            // Performance
            performance: performance.timing ? {
                navigationStart: performance.timing.navigationStart,
                loadEventEnd: performance.timing.loadEventEnd
            } : null
        };
        
        return this.fingerprint;
    }
    
    getCanvasFingerprint() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Text with subtle differences
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillStyle = '#f60';
        ctx.fillRect(125, 1, 62, 20);
        ctx.fillStyle = '#069';
        ctx.fillText('COSMIC-OSINT', 2, 15);
        ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
        ctx.fillText('LAB', 4, 17);
        
        return canvas.toDataURL();
    }
    
    getWebGLFingerprint() {
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        
        if (!gl) return null;
        
        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
        return {
            vendor: gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL),
            renderer: gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL),
            version: gl.getParameter(gl.VERSION),
            shadingLanguage: gl.getParameter(gl.SHADING_LANGUAGE_VERSION)
        };
    }
    
    setupHoneytokens() {
        // Invisible honeytoken fields
        const honeyInput = document.createElement('input');
        honeyInput.type = 'text';
        honeyInput.name = 'honeytoken_email';
        honeyInput.style.cssText = 'position:absolute;left:-9999px;top:-9999px;opacity:0;';
        honeyInput.value = 'honeytoken@' + Math.random().toString(36).substr(2) + '.com';
        document.body.appendChild(honeyInput);
        
        // Monitor honeytoken interactions
        honeyInput.addEventListener('focus', () => this.triggerAlert('HONEYTOKEN_FOCUS'));
        honeyInput.addEventListener('change', () => this.triggerAlert('HONEYTOKEN_CHANGE'));
        
        this.honeytokens.email = honeyInput;
    }
    
    monitorUserBehavior() {
        let lastMouseMove = Date.now();
        let mouseMoves = 0;
        
        // Mouse movement patterns
        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            const speed = now - lastMouseMove;
            
            // Detect robotic movement (too linear/consistent)
            if (speed < 10 && mouseMoves > 100) {
                this.anomalies.push('ROBOTIC_MOUSE_MOVEMENT');
            }
            
            lastMouseMove = now;
            mouseMoves++;
        });
        
        // Keystroke dynamics
        let lastKeyPress = Date.now();
        document.addEventListener('keydown', (e) => {
            const now = Date.now();
            const speed = now - lastKeyPress;
            
            // Superhuman typing speed
            if (speed < 30) {
                this.anomalies.push('SUPERHUMAN_TYPING');
            }
            
            lastKeyPress = now;
        });
        
        // Scroll behavior
        let lastScroll = Date.now();
        window.addEventListener('scroll', (e) => {
            const now = Date.now();
            const speed = now - lastScroll;
            
            // Instant scroll to bottom (common in bots)
            if (speed < 100 && window.scrollY > document.body.scrollHeight - 1000) {
                this.anomalies.push('INSTANT_SCROLL_TO_BOTTOM');
            }
            
            lastScroll = now;
        });
    }
    
    detectAutomation() {
        // Check for automation tools
        const automationIndicators = [
            // WebDriver property
            () => navigator.webdriver === true,
            
            // Chrome automation
            () => /PhantomJS|HeadlessChrome|Selenium|Puppeteer/.test(navigator.userAgent),
            
            // Missing plugins (headless browsers often have fewer)
            () => navigator.plugins.length < 2,
            
            // Missing common mime types
            () => {
                const mimes = Array.from(navigator.mimeTypes);
                return mimes.length < 10;
            },
            
            // Language mismatch
            () => navigator.language !== navigator.languages[0],
            
            // Timezone mismatch with IP (would require server-side check)
            
            // Screen size anomalies
            () => window.screen.width < 300 || window.screen.height < 300,
            
            // No mouse movement for extended period but active typing
            () => {
                const now = Date.now();
                return (now - lastMouseMove) > 30000 && typingActivity > 10;
            }
        ];
        
        let automationScore = 0;
        automationIndicators.forEach(check => {
            if (check()) automationScore += 10;
        });
        
        if (automationScore > 50) {
            this.triggerAlert('AUTOMATION_DETECTED', { score: automationScore });
        }
    }
    
    triggerAlert(type, data = {}) {
        // Send alert to server
        fetch('/api/deception-alert', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                type,
                data,
                fingerprint: this.fingerprint,
                timestamp: Date.now(),
                url: window.location.href
            })
        });
        
        // Visual indication (subtle for real users, obvious for monitoring)
        if (data.score > 70) {
            document.body.style.border = '5px solid red';
            setTimeout(() => {
                document.body.style.border = '';
            }, 5000);
        }
    }
    
    // Real-time attack simulation
    simulateAttack(type) {
        switch(type) {
            case 'phishing':
                return this.simulatePhishing();
            case 'bruteforce':
                return this.simulateBruteForce();
            case 'sqli':
                return this.simulateSQLi();
            default:
                return null;
        }
    }
    
    simulatePhishing() {
        // Create fake phishing page overlay
        const overlay = document.createElement('div');
        overlay.innerHTML = `
            <div style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;display:flex;align-items:center;justify-content:center;">
                <div style="background:white;padding:30px;border-radius:10px;max-width:500px;">
                    <h3>Security Alert: Phishing Simulation</h3>
                    <p>This is a simulated phishing page. What would you do?</p>
                    <button onclick="this.parentElement.parentElement.remove()">Report Phishing</button>
                    <button onclick="this.parentElement.parentElement.remove()">Enter Credentials</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        
        return {
            type: 'phishing',
            timestamp: Date.now(),
            user_action: 'pending'
        };
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.deception = new DeceptionClient();
    
    // Send fingerprint to server
    fetch('/api/register-fingerprint', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(window.deception.fingerprint)
    });
});
