#!/bin/bash

# Test Customers API on Staging
# Replace YOUR_PASSWORD_HERE with actual password

echo "Testing Customers API..."
echo ""

# Step 1: Login
echo "Step 1: Login to get token..."
curl -s -X POST https://staging.kinvoice.ng/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@khaninvoice.com",
    "password": "YOUR_PASSWORD_HERE"
  }' > /tmp/login_response.json

echo "Login response saved to /tmp/login_response.json"
cat /tmp/login_response.json
echo ""
echo ""

# Step 2: Get customers (replace TOKEN_HERE with token from login response)
echo "Step 2: Fetching customers..."
echo "Run this command manually with the token from above:"
echo ""
echo 'curl -X GET https://staging.kinvoice.ng/api/v1/customers \'
echo '  -H "Accept: application/json" \'
echo '  -H "Authorization: Bearer YOUR_TOKEN_HERE"'
