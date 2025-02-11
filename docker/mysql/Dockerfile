# Use the official MySQL image from the Docker Hub
FROM mysql:8.4.4

# Copy all schema creation files to idealise the DB
COPY /apps/csv_db/schema/* /docker-entrypoint-initdb.d/
