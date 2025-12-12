#!/bin/sh
set -e

TEST_DB="virgo_order_db_test"

# Validate injected variables
MYSQL_ROOT_PASS=${MYSQL_ROOT_PASS:?MYSQL_ROOT_PASS is required}
TEST_USER=${TEST_USER:?TEST_USER is required}
TEST_PASS=${TEST_PASS:?TEST_PASS is required}

echo "-----------------------------------------------"
echo " Creating MySQL Test Database and Test User"
echo " DB:   ${TEST_DB}"
echo " User: ${TEST_USER}"
echo "-----------------------------------------------"

docker compose exec -T vs_database mysql -u root -p"${MYSQL_ROOT_PASS}" <<EOF
DROP DATABASE IF EXISTS ${TEST_DB};
CREATE DATABASE ${TEST_DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

DROP USER IF EXISTS '${TEST_USER}'@'%';
CREATE USER '${TEST_USER}'@'%' IDENTIFIED BY '${TEST_PASS}';

GRANT ALL PRIVILEGES ON ${TEST_DB}.* TO '${TEST_USER}'@'%';
FLUSH PRIVILEGES;
EOF

echo "-----------------------------------------------"
echo " Test database '${TEST_DB}' and user '${TEST_USER}' created successfully."
echo "-----------------------------------------------"
