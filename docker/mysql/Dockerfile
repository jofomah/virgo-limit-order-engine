# Use the official MySQL image from Docker Hub
FROM mysql:9.3

# Expose MySQL default port (can be changed if needed)
EXPOSE 3306

# Health check to verify MySQL is running
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 \
    CMD mysqladmin ping -u root -p${MYSQL_ROOT_PASSWORD} || exit 1