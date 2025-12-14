#!/bin/bash

# API Testing Script for Khan Invoice Flutter Backend
# Server: http://127.0.0.1:8000

TOKEN="2|Bx4jyU5orinuKuJ9sYSkvW61TK7Wk0lMna3ee1TT9f898527"
BASE_URL="http://127.0.0.1:8000/api/v1"

echo "========================================="
echo "Khan Invoice API Testing"
echo "========================================="
echo ""

# Test 1: Get authenticated user
echo "1. Testing GET /auth/user..."
curl -s -X GET "$BASE_URL/auth/user" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'
echo ""
echo "---"
echo ""

# Test 2: Get dashboard stats
echo "2. Testing GET /dashboard..."
curl -s -X GET "$BASE_URL/dashboard" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'
echo ""
echo "---"
echo ""

# Test 3: List invoices
echo "3. Testing GET /invoices..."
curl -s -X GET "$BASE_URL/invoices?per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.data | length'
echo " invoices found"
echo ""
echo "---"
echo ""

# Test 4: List customers
echo "4. Testing GET /customers..."
curl -s -X GET "$BASE_URL/customers?per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.data | length'
echo " customers found"
echo ""
echo "---"
echo ""

# Test 5: List payments
echo "5. Testing GET /payments..."
curl -s -X GET "$BASE_URL/payments?per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.data | length'
echo " payments found"
echo ""
echo "---"
echo ""

# Test 6: Get current subscription
echo "6. Testing GET /subscription..."
curl -s -X GET "$BASE_URL/subscription" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'
echo ""
echo "---"
echo ""

# Test 7: Get available plans
echo "7. Testing GET /plans..."
curl -s -X GET "$BASE_URL/plans" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.plans | length'
echo " plans available"
echo ""
echo "---"
echo ""

echo "========================================="
echo "API Testing Complete!"
echo "========================================="
