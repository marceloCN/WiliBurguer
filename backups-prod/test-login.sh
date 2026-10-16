#!/bin/bash
set -e
COOKIE_JAR=$(mktemp)
TOKEN=$(curl -s -c "$COOKIE_JAR" https://burguerwilly.com/login | sed -n 's/.*name="csrf-token" content="\([^"]*\)".*/\1/p')
echo "CSRF token: $TOKEN"
curl -s -o /dev/null -w "HTTP %{http_code}\n" -b "$COOKIE_JAR" -X POST -d "_token=$TOKEN&usuario=test&password=test" https://burguerwilly.com/login
rm -f "$COOKIE_JAR"
