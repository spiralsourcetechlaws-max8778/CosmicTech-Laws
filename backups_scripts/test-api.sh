#!/bin/bash
echo "Testing Red Team API..."
echo ""

echo "1. Testing API status:"
curl -s "http://127.0.0.1:8080/redteam-api.php?action=status" | python3 -m json.tool 2>/dev/null || curl -s "http://127.0.0.1:8080/redteam-api.php?action=status"
echo ""

echo "2. Testing TTP library:"
curl -s "http://127.0.0.1:8080/redteam-api.php?action=ttp" | python3 -m json.tool 2>/dev/null || curl -s "http://127.0.0.1:8080/redteam-api.php?action=ttp" | head -100
echo ""

echo "3. Testing with no action (should show error):"
curl -s "http://127.0.0.1:8080/redteam-api.php" | python3 -m json.tool 2>/dev/null || curl -s "http://127.0.0.1:8080/redteam-api.php"
echo ""

echo "✅ API test completed!"
