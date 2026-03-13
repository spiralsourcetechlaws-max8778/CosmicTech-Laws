<?php
require_once "includes/security_functions.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>🧪 Payload Tester - COSMIC-OSINT-LAB</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        .panel { border: 2px solid #0f0; padding: 20px; margin: 20px auto; max-width: 800px; }
        h1 { color: #f0f; text-align: center; }
        textarea { width: 100%; height: 200px; background: #111; color: #0f0; border: 1px solid #0f0; padding: 10px; }
        button { background: #000; color: #0f0; border: 2px solid #0f0; padding: 10px 20px; margin: 10px; cursor: pointer; }
        button:hover { background: #0f0; color: #000; }
    </style>
</head>
<body class="cosmic-scroll-body">
    <div class="panel">
        <h1>🧪 PAYLOAD TESTING SANDBOX</h1>
        <p>Test payloads in a safe environment</p>
        
        <textarea id="payload" placeholder="Paste payload code here..."></textarea>
        
        <div style="text-align: center; margin: 20px 0;">
            <button onclick="analyzePayload()">🔍 Analyze Payload</button>
            <button onclick="testInSandbox()">🧪 Test in Sandbox</button>
            <button onclick="clearPayload()">🗑️ Clear</button>
        </div>
        
        <div id="results" style="margin-top: 20px; padding: 10px; border: 1px solid #0f0; min-height: 100px;">
            Results will appear here...
        </div>
    </div>
    
    <script>
        function analyzePayload() {
            const payload = document.getElementById('payload').value;
            const results = document.getElementById('results');
            
            if (!payload.trim()) {
                results.innerHTML = '<p style="color: #f00;">Please enter a payload to analyze</p>';
                return;
            }
            
            // Simple analysis
            const analysis = {
                length: payload.length,
                lines: payload.split('\n').length,
                containsShell: payload.includes('bash') || payload.includes('sh'),
                containsPython: payload.includes('python'),
                containsPowershell: payload.includes('powershell'),
                containsNetwork: payload.includes('socket') || payload.includes('netcat') || payload.includes('nc '),
                suspiciousKeywords: ['reverse', 'shell', 'keylog', 'backdoor', 'persist'].filter(keyword => 
                    payload.toLowerCase().includes(keyword)
                )
            };
            
            let html = '<h3>Analysis Results:</h3>';
            html += `<p>Size: ${analysis.length} characters, ${analysis.lines} lines</p>`;
            html += `<p>Shell commands: ${analysis.containsShell ? '✅ Yes' : '❌ No'}</p>`;
            html += `<p>Python code: ${analysis.containsPython ? '✅ Yes' : '❌ No'}</p>`;
            html += `<p>Powershell code: ${analysis.containsPowershell ? '✅ Yes' : '❌ No'}</p>`;
            html += `<p>Network operations: ${analysis.containsNetwork ? '✅ Yes' : '❌ No'}</p>`;
            html += `<p>Suspicious keywords: ${analysis.suspiciousKeywords.length > 0 ? '⚠️ ' + analysis.suspiciousKeywords.join(', ') : '✅ None found'}</p>`;
            
            results.innerHTML = html;
        }
        
        function testInSandbox() {
            alert('Sandbox testing would require a secure isolated environment.\nThis feature is for demonstration purposes.');
        }
        
        function clearPayload() {
            document.getElementById('payload').value = '';
            document.getElementById('results').innerHTML = 'Results will appear here...';
        }
    </script>
</body>
</html>
