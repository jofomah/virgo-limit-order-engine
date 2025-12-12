#!/bin/sh
set -e

# FAILED IF NOT PROVIDED
: "${MYSQL_ROOT_PASS:?MYSQL_ROOT_PASS is required}"

TEST_USER="virgo_db_test_user"
TEST_PASS="TestPassword123!"

# Run the DB setup script
MYSQL_ROOT_PASS="$MYSQL_ROOT_PASS" \
TEST_USER="$TEST_USER" \
TEST_PASS="$TEST_PASS" \
./scripts/setup-test-db.sh
