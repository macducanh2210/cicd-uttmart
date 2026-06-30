#!/bin/bash
set -e

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS user_db;
    CREATE DATABASE IF NOT EXISTS product_db;
    CREATE DATABASE IF NOT EXISTS order_db;
    CREATE DATABASE IF NOT EXISTS inventory_db;
    CREATE DATABASE IF NOT EXISTS employee_db;
    CREATE DATABASE IF NOT EXISTS attendance_db;
    CREATE DATABASE IF NOT EXISTS expense_db;
    CREATE DATABASE IF NOT EXISTS customer_db;
    CREATE DATABASE IF NOT EXISTS payment_db;

    CREATE USER IF NOT EXISTS 'user_service'@'%' IDENTIFIED BY '${USER_DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON user_db.* TO 'user_service'@'%';

    CREATE USER IF NOT EXISTS 'product_service'@'%' IDENTIFIED BY '${PRODUCT_DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON product_db.* TO 'product_service'@'%';

    CREATE USER IF NOT EXISTS 'order_service'@'%' IDENTIFIED BY '${ORDER_DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON order_db.* TO 'order_service'@'%';

    CREATE USER IF NOT EXISTS 'inventory_service'@'%' IDENTIFIED BY '${INVENTORY_DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON inventory_db.* TO 'inventory_service'@'%';

    CREATE USER IF NOT EXISTS 'employee_service'@'%' IDENTIFIED BY '${EMPLOYEE_DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON employee_db.* TO 'employee_service'@'%';

    CREATE USER IF NOT EXISTS 'attendance_service'@'%' IDENTIFIED BY '${ATTENDANCE_DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON attendance_db.* TO 'attendance_service'@'%';

    CREATE USER IF NOT EXISTS 'expense_service'@'%' IDENTIFIED BY '${EXPENSE_DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON expense_db.* TO 'expense_service'@'%';

    CREATE USER IF NOT EXISTS 'customer_service'@'%' IDENTIFIED BY '${CUSTOMER_DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON customer_db.* TO 'customer_service'@'%';

    CREATE USER IF NOT EXISTS 'payment_service'@'%' IDENTIFIED BY '${PAYMENT_DB_PASSWORD}';
    GRANT ALL PRIVILEGES ON payment_db.* TO 'payment_service'@'%';

    FLUSH PRIVILEGES;
EOSQL

echo "Importing data into user_db..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" user_db < /docker-entrypoint-initdb.d/sql/user_db.sql

echo "Importing data into product_db..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" product_db < /docker-entrypoint-initdb.d/sql/product_db.sql

echo "Importing data into order_db..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" order_db < /docker-entrypoint-initdb.d/sql/order_db.sql

echo "Importing data into inventory_db..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" inventory_db < /docker-entrypoint-initdb.d/sql/inventory_db.sql

echo "Importing data into employee_db..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" employee_db < /docker-entrypoint-initdb.d/sql/employee_db.sql

echo "Importing data into attendance_db..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" attendance_db < /docker-entrypoint-initdb.d/sql/attendance_db.sql

echo "Importing data into expense_db..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" expense_db < /docker-entrypoint-initdb.d/sql/expense_db.sql

echo "Importing data into customer_db..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" customer_db < /docker-entrypoint-initdb.d/sql/customer_db.sql

echo "Importing data into payment_db..."
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" payment_db < /docker-entrypoint-initdb.d/sql/payment_db.sql

echo "All databases initialized successfully!"
