#!/bin/bash
echo "COSMIC OSINT LAB Health Check"
echo "=============================="
echo "Time: $(date)"
echo ""
echo "Services:"
ps aux | grep -E "php -S|background" | grep -v grep
echo ""
echo "Web Access:"
curl -s http://127.0.0.1:8080/ | grep -o "<title>[^<]*</title>"
echo ""
echo "Disk Usage:"
du -sh data/ 2>/dev/null
echo ""
echo "Logs:"
tail -5 data/logs/*.log 2>/dev/null | head -20
