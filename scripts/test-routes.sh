#!/bin/bash
# Voter Backend API Test Script
# Usage: ./scripts/test-routes.sh [base_url]

set -e

BASE="${1:-http://localhost:8000/api}"
PASS=0
FAIL=0

# Ensure clean database
echo "Running migrate:fresh..."
cd "$(dirname "$0")/.." && php artisan migrate:fresh --force --quiet 2>/dev/null
echo ""

green() { printf "\033[32m%s\033[0m\n" "$1"; }
red()   { printf "\033[31m%s\033[0m\n" "$1"; }
bold()  { printf "\033[1m%s\033[0m\n" "$1"; }

assert_status() {
  local expected=$1 actual=$2 label=$3
  if [ "$actual" = "$expected" ]; then
    green "  ✓ $label (HTTP $actual)"
    PASS=$((PASS + 1))
  else
    red "  ✗ $label — expected $expected, got $actual"
    FAIL=$((FAIL + 1))
  fi
}

json_field() {
  echo "$1" | jq -r "$2" 2>/dev/null || echo ""
}

# ─────────────────────────────────────────────
bold "1. Health Check"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/health")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 200 "$STATUS" "GET /health"

# ─────────────────────────────────────────────
bold "2. Register Admin"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin User","email":"admin@test.com","password":"Password1!","password_confirmation":"Password1!","role":"admin"}')
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /register (admin)"
ADMIN_TOKEN=$(json_field "$BODY" ".token")
echo "  Token: ${ADMIN_TOKEN:0:20}..."

# ─────────────────────────────────────────────
bold "3. Register Voter"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"Voter One","email":"voter1@test.com","password":"Password1!","password_confirmation":"Password1!","role":"voter","matric_number":"STU001"}')
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /register (voter)"
VOTER_TOKEN=$(json_field "$BODY" ".token")

# ─────────────────────────────────────────────
bold "4. Register Second Voter"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"Voter Two","email":"voter2@test.com","password":"Password1!","password_confirmation":"Password1!","role":"voter","matric_number":"STU002"}')
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /register (voter 2)"
VOTER2_TOKEN=$(json_field "$BODY" ".token")

# ─────────────────────────────────────────────
bold "5. Login Admin"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"Password1!"}')
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 200 "$STATUS" "POST /login (admin)"
ADMIN_TOKEN=$(json_field "$BODY" ".token")

# ─────────────────────────────────────────────
bold "6. Login Voter"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"voter1@test.com","password":"Password1!"}')
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 200 "$STATUS" "POST /login (voter)"
VOTER_TOKEN=$(json_field "$BODY" ".token")

# ─────────────────────────────────────────────
bold "7. Get Auth User"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/user" \
  -H "Authorization: Bearer $ADMIN_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 200 "$STATUS" "GET /user"
echo "  User: $(json_field "$BODY" ".name") ($(json_field "$BODY" ".role"))"

# ─────────────────────────────────────────────
bold "8. Create Election (admin)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/elections" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Student Council 2025","description":"Annual election","start_time":"2026-08-05T09:00:00","end_time":"2026-12-31T17:00:00"}')
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /elections"
ELECTION_ID=$(json_field "$BODY" ".election.id")
echo "  Election ID: $ELECTION_ID"

# ─────────────────────────────────────────────
bold "9. Open Election (admin)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X PUT "$BASE/elections/$ELECTION_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"open"}')
STATUS=$(echo "$RESP" | tail -1)
assert_status 200 "$STATUS" "PUT /elections/$ELECTION_ID (open)"

# ─────────────────────────────────────────────
bold "10. Create Positions (admin)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/positions" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"election_id\":$ELECTION_ID,\"title\":\"Class Representative\",\"description\":\"Main class rep\"}")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /positions (Class Rep)"
POS1_ID=$(json_field "$BODY" ".position.id")

RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/positions" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"election_id\":$ELECTION_ID,\"title\":\"Treasurer\",\"description\":\"Handles funds\"}")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /positions (Treasurer)"
POS2_ID=$(json_field "$BODY" ".position.id")

# ─────────────────────────────────────────────
bold "11. Create Candidates (admin)"
# ─────────────────────────────────────────────
# Position 1 candidates
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/candidates" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"position_id\":$POS1_ID,\"name\":\"Alice Johnson\",\"manifesto\":\"Better facilities\"}")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /candidates (Alice)"
CAND1_ID=$(json_field "$BODY" ".candidate.id")

RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/candidates" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"position_id\":$POS1_ID,\"name\":\"Bob Smith\",\"manifesto\":\"More events\"}")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /candidates (Bob)"
CAND2_ID=$(json_field "$BODY" ".candidate.id")

# Position 2 candidates
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/candidates" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"position_id\":$POS2_ID,\"name\":\"Charlie Brown\",\"manifesto\":\"Transparent budget\"}")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /candidates (Charlie)"
CAND3_ID=$(json_field "$BODY" ".candidate.id")

RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/candidates" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"position_id\":$POS2_ID,\"name\":\"Diana Prince\",\"manifesto\":\"Financial literacy\"}")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /candidates (Diana)"
CAND4_ID=$(json_field "$BODY" ".candidate.id")

# ─────────────────────────────────────────────
bold "12. List Elections (voter)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/elections" \
  -H "Authorization: Bearer $VOTER_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 200 "$STATUS" "GET /elections"
echo "  Count: $(echo "$BODY" | jq 'length')"

# ─────────────────────────────────────────────
bold "13. Show Election (voter)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/elections/$ELECTION_ID" \
  -H "Authorization: Bearer $VOTER_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 200 "$STATUS" "GET /elections/$ELECTION_ID"
echo "  Positions: $(echo "$BODY" | jq '.positions | length')"

# ─────────────────────────────────────────────
bold "14. List Positions (voter)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/positions" \
  -H "Authorization: Bearer $VOTER_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
assert_status 200 "$STATUS" "GET /positions"

# ─────────────────────────────────────────────
bold "15. List Candidates (voter)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/candidates" \
  -H "Authorization: Bearer $VOTER_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
assert_status 200 "$STATUS" "GET /candidates"

# ─────────────────────────────────────────────
bold "16. Check Eligibility (voter 1)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/eligibility?position_id=$POS1_ID" \
  -H "Authorization: Bearer $VOTER_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 200 "$STATUS" "GET /eligibility (position 1)"
echo "  Eligible: $(json_field "$BODY" ".eligible")"

# ─────────────────────────────────────────────
bold "17. Cast Vote (voter 1 → Alice)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/votes" \
  -H "Authorization: Bearer $VOTER_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"position_id\":$POS1_ID,\"candidate_id\":$CAND1_ID}")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 201 "$STATUS" "POST /votes (voter 1 → Alice)"

# ─────────────────────────────────────────────
bold "18. Duplicate Vote (expect 409/422)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/votes" \
  -H "Authorization: Bearer $VOTER_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"position_id\":$POS1_ID,\"candidate_id\":$CAND2_ID}")
STATUS=$(echo "$RESP" | tail -1)
assert_status 422 "$STATUS" "POST /votes (duplicate — rejected)"

# ─────────────────────────────────────────────
bold "19. Wrong Candidate for Position (expect 422)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/votes" \
  -H "Authorization: Bearer $VOTER_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"position_id\":$POS1_ID,\"candidate_id\":$CAND3_ID}")
STATUS=$(echo "$RESP" | tail -1)
assert_status 422 "$STATUS" "POST /votes (wrong position → rejected)"

# ─────────────────────────────────────────────
bold "20. Voter 2 Casts Votes"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/votes" \
  -H "Authorization: Bearer $VOTER2_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"position_id\":$POS1_ID,\"candidate_id\":$CAND2_ID}")
STATUS=$(echo "$RESP" | tail -1)
assert_status 201 "$STATUS" "POST /votes (voter 2 → Bob)"

RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/votes" \
  -H "Authorization: Bearer $VOTER2_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"position_id\":$POS2_ID,\"candidate_id\":$CAND4_ID}")
STATUS=$(echo "$RESP" | tail -1)
assert_status 201 "$STATUS" "POST /votes (voter 2 → Diana)"

# ─────────────────────────────────────────────
bold "21. My Votes (voter 1)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/votes/mine" \
  -H "Authorization: Bearer $VOTER_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 200 "$STATUS" "GET /votes/mine"
echo "  Votes cast: $(echo "$BODY" | jq 'length')"

# ─────────────────────────────────────────────
bold "22. Results (admin)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/elections/$ELECTION_ID/results" \
  -H "Authorization: Bearer $ADMIN_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
BODY=$(echo "$RESP" | sed '$d')
assert_status 200 "$STATUS" "GET /elections/$ELECTION_ID/results"
echo "$BODY" | jq -r '.results[] | "  \(.position): \(.candidates | map("\(.name)=\(.votes)") | join(", "))"'

# ─────────────────────────────────────────────
bold "23. Voter Cannot Access Admin Routes"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/elections" \
  -H "Authorization: Bearer $VOTER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Hack"}')
STATUS=$(echo "$RESP" | tail -1)
assert_status 403 "$STATUS" "POST /elections (voter → 403)"

RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/positions" \
  -H "Authorization: Bearer $VOTER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Hack"}')
STATUS=$(echo "$RESP" | tail -1)
assert_status 403 "$STATUS" "POST /positions (voter → 403)"

RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/candidates" \
  -H "Authorization: Bearer $VOTER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Hack","position_id":1}')
STATUS=$(echo "$RESP" | tail -1)
assert_status 403 "$STATUS" "POST /candidates (voter → 403)"

# ─────────────────────────────────────────────
bold "24. Unauthenticated Access (expect 401)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/user")
STATUS=$(echo "$RESP" | tail -1)
assert_status 401 "$STATUS" "GET /user (no token → 401)"

RESP=$(curl -s -w "\n%{http_code}" "$BASE/elections")
STATUS=$(echo "$RESP" | tail -1)
assert_status 401 "$STATUS" "GET /elections (no token → 401)"

# ─────────────────────────────────────────────
bold "25. Logout (voter)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/logout" \
  -H "Authorization: Bearer $VOTER_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
assert_status 200 "$STATUS" "POST /logout"

# ─────────────────────────────────────────────
bold "26. Access After Logout (expect 401)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" "$BASE/user" \
  -H "Authorization: Bearer $VOTER_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
assert_status 401 "$STATUS" "GET /user (after logout → 401)"

# ─────────────────────────────────────────────
bold "27. Delete Candidate (admin)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X DELETE "$BASE/candidates/$CAND3_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
assert_status 200 "$STATUS" "DELETE /candidates/$CAND3_ID (no votes — allowed)"

RESP=$(curl -s -w "\n%{http_code}" -X DELETE "$BASE/candidates/$CAND4_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN")
STATUS=$(echo "$RESP" | tail -1)
assert_status 500 "$STATUS" "DELETE /candidates/$CAND4_ID (has votes — blocked)"

# ─────────────────────────────────────────────
bold "28. Close Election (admin)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X PUT "$BASE/elections/$ELECTION_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"closed"}')
STATUS=$(echo "$RESP" | tail -1)
assert_status 200 "$STATUS" "PUT /elections/$ELECTION_ID (closed)"

# ─────────────────────────────────────────────
bold "29. Register Validation Errors"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"","email":"bad","password":"123"}')
STATUS=$(echo "$RESP" | tail -1)
assert_status 422 "$STATUS" "POST /register (validation errors)"

# ─────────────────────────────────────────────
bold "30. Bad Login (expect 422)"
# ─────────────────────────────────────────────
RESP=$(curl -s -w "\n%{http_code}" -X POST "$BASE/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"wrong"}')
STATUS=$(echo "$RESP" | tail -1)
assert_status 422 "$STATUS" "POST /login (wrong password)"

# ─────────────────────────────────────────────
echo ""
bold "════════════════════════════════"
bold " Results: $PASS passed, $FAIL failed"
bold "════════════════════════════════"

if [ "$FAIL" -gt 0 ]; then
  exit 1
fi
