#!/bin/bash
set -e
cd /opt/burgerwilly

echo 'Eliminando base de datos de produccion...'
docker exec burguerwilly-db psql -U burguer -d postgres -c "DROP DATABASE IF EXISTS burguerwilly WITH (FORCE);"

echo 'Creando base de datos vacia...'
docker exec burguerwilly-db psql -U burguer -d postgres -c "CREATE DATABASE burguerwilly;"

echo 'Restaurando backup local...'
docker exec -i burguerwilly-db psql -U burguer -d burguerwilly < db-local-backup-no-owner-utf8.sql

echo 'Restauracion completada.'
