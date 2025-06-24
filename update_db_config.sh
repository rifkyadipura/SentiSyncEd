#!/bin/bash

echo "SentiSyncEd Database Update Script"
echo "=================================="
echo ""

# Source the .env file if it exists
if [ -f .env ]; then
    source .env
    echo "Using database configuration from .env file"
else
    echo "Warning: .env file not found. Using default values."
    DB_NAME="sentisyncdb"
    DB_USER="sentisyncuser"
    DB_PASSWORD="sentisyncpassword"
    DB_HOST="db"
fi

echo "Database name: ${DB_NAME}"
echo ""

# Update the database name in configuration files
echo "1. Updating database configuration in files..."
sed -i "s/CREATE DATABASE IF NOT EXISTS sentisynced_db;/CREATE DATABASE IF NOT EXISTS ${DB_NAME};/" database.sql
sed -i "s/USE sentisynced_db;/USE ${DB_NAME};/" database.sql

# Update koneksi.php with the correct database name
sed -i "s/\$dbname = getenv('DB_NAME') ?: 'sentisynced_db';/\$dbname = getenv('DB_NAME') ?: '${DB_NAME}';/" koneksi.php

# Update run_database.php with the correct database name
sed -i "s/\$dbname = getenv('DB_NAME') ?: 'sentisynced_db';/\$dbname = getenv('DB_NAME') ?: '${DB_NAME}';/" run_database.php

echo "Configuration files updated."
echo ""

# Run database initialization
echo "2. Initializing database..."
echo "Running docker compose exec app php run_database.php"
docker compose exec app php run_database.php
echo ""

# Restart the application
echo "3. Restarting application to apply changes..."
docker compose restart app
echo ""

echo "Database update completed."
echo "You should now be able to register users and use the application."
echo ""
echo "If you still encounter issues, please check the database connection in the application."
