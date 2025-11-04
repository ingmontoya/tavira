#!/bin/bash

API_BASE="https://demo.staging.tavira.com.co/api"
EMAIL="mauricio.montoyav@gmail.com"
PASSWORD="password"

echo "=== Testing Multi-Guest Visits API ==="
echo ""

# Step 1: Login
echo "1. Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -c /tmp/tavira-cookies.txt \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

echo "Login Response:"
echo "$LOGIN_RESPONSE" | jq '.' 2>/dev/null || echo "$LOGIN_RESPONSE"
echo ""

# Step 2: Create a visit with multiple guests
echo "2. Creating visit with 3 guests (2 with vehicles, 1 without)..."
CREATE_RESPONSE=$(curl -s -X POST "$API_BASE/visits" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -b /tmp/tavira-cookies.txt \
  -d '{
    "valid_from": "2025-11-04T10:00",
    "valid_until": "2025-11-04T18:00",
    "visit_reason": "API Test - Multi-guest visit with vehicles",
    "guests": [
      {
        "guest_name": "API Test Guest 1",
        "document_type": "CC",
        "document_number": "1111111111",
        "phone": "+57 300 1111111",
        "vehicle_plate": "TEST123",
        "vehicle_color": "Azul"
      },
      {
        "guest_name": "API Test Guest 2",
        "document_type": "CE",
        "document_number": "2222222222",
        "phone": "+57 310 2222222",
        "vehicle_plate": "TEST456",
        "vehicle_color": "Verde"
      },
      {
        "guest_name": "API Test Guest 3",
        "document_type": "Pasaporte",
        "document_number": "PASS333333",
        "phone": "+57 320 3333333",
        "vehicle_plate": "",
        "vehicle_color": ""
      }
    ]
  }')

echo "Create Visit Response:"
echo "$CREATE_RESPONSE" | jq '.' 2>/dev/null || echo "$CREATE_RESPONSE"
echo ""

# Extract visit ID
VISIT_ID=$(echo "$CREATE_RESPONSE" | jq -r '.id' 2>/dev/null)

if [ "$VISIT_ID" != "null" ] && [ -n "$VISIT_ID" ]; then
  echo "Visit created successfully with ID: $VISIT_ID"
  echo ""

  # Step 3: Get visit details
  echo "3. Fetching visit details to verify guests..."
  DETAILS_RESPONSE=$(curl -s -X GET "$API_BASE/visits/$VISIT_ID" \
    -H "Accept: application/json" \
    -H "X-Requested-With: XMLHttpRequest" \
    -b /tmp/tavira-cookies.txt)

  echo "Visit Details:"
  echo "$DETAILS_RESPONSE" | jq '.' 2>/dev/null || echo "$DETAILS_RESPONSE"
  echo ""

  # Verify guests
  GUEST_COUNT=$(echo "$DETAILS_RESPONSE" | jq '.guests | length' 2>/dev/null)
  echo "Number of guests: $GUEST_COUNT"

  if [ "$GUEST_COUNT" = "3" ]; then
    echo "✅ SUCCESS: Visit created with 3 guests as expected!"
    echo ""
    echo "Guest details:"
    echo "$DETAILS_RESPONSE" | jq '.guests' 2>/dev/null
  else
    echo "❌ ERROR: Expected 3 guests but got $GUEST_COUNT"
  fi
else
  echo "❌ ERROR: Failed to create visit"
  echo "Response: $CREATE_RESPONSE"
fi

echo ""
echo "=== Test Complete ==="
