# virgo-limit-order-engine


Test DB Setup:
MYSQL_ROOT_PASS=$(grep MYSQL_ROOT_PASSWORD .env | cut -d '=' -f2) \
./scripts/run-setup-test-db.sh

OR 
MYSQL_ROOT_PASS=$MYSQL_ROOT_PASSWORD \
./scripts/run-setup-test-db.sh


run seeder:
docker compose exec vs_backend php artisan db:seed --class=UserSeeder