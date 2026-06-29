#!/bin/bash
set -e

# Initialize MySQL data directory if needed
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "Initializing MySQL data directory..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

# Start MySQL in background
echo "Starting MySQL..."
mysqld_safe --skip-syslog &
MYSQL_PID=$!

# Wait for MySQL to be ready
for i in {30..0}; do
    if mysqladmin ping --silent 2>/dev/null; then
        break
    fi
    sleep 1
done

if [ "$i" = 0 ]; then
    echo "MySQL failed to start"
    exit 1
fi

# Create database and user if this is first run
if [ ! -f "/var/lib/mysql/.initialized" ]; then
    echo "Setting up database..."
    mysql -u root <<-EOSQL
        CREATE DATABASE IF NOT EXISTS student_system;
        CREATE USER IF NOT EXISTS 'student_user'@'localhost' IDENTIFIED BY 'student_pass';
        GRANT ALL PRIVILEGES ON student_system.* TO 'student_user'@'localhost';
        FLUSH PRIVILEGES;
EOSQL

    # Import schema
    mysql -u root student_system < /docker-entrypoint-initdb.d/db.sql 2>/dev/null || true

    touch /var/lib/mysql/.initialized
    echo "Database setup complete."
fi

# Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground
