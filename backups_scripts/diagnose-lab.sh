#!/bin/bash
echo "=== COSMIC-OSINT-LAB DIAGNOSTIC ==="
echo "Timestamp: $(date)"
echo ""

echo "1. Current directory:"
pwd
echo ""

echo "2. Directory contents:"
ls -la
echo ""

echo "3. PHP files:"
find . -name "*.php" -type f -exec ls -la {} \;
echo ""

echo "4. Web server test:"
echo "   Creating test file..."
cat > web-test.html << 'TEST'
<html><body><h1>Test Page</h1></body></html>
TEST
echo "   Test file created: web-test.html"
echo ""

echo "5. Network status:"
ifconfig | grep -A1 "inet" | grep -v "127.0.0.1" | head -5
echo ""

echo "6. Running processes on port 8080:"
netstat -tulpn 2>/dev/null | grep :8080 || lsof -i:8080 2>/dev/null || echo "No process on port 8080"
echo ""

echo "7. File permissions:"
stat ai-dashboard.php 2>/dev/null || echo "ai-dashboard.php not found"
echo ""

echo "8. Quick fix - creating symbolic links:"
ln -sf $(pwd)/ai-dashboard.php ai-dashboard-link.php 2>/dev/null && echo "Created symlink: ai-dashboard-link.php"
ln -sf $(pwd)/redteam-dashboard.php redteam-dashboard-link.php 2>/dev/null && echo "Created symlink: redteam-dashboard-link.php"
echo ""

echo "9. Access URLs:"
echo "   Main page: http://102.2.220.165:8080/index.html"
echo "   AI Dashboard: http://102.2.220.165:8080/ai-dashboard.php"
echo "   Test file: http://102.2.220.165:8080/web-test.html"
