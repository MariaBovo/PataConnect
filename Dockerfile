# Use official PHP 8.2 CLI base image (Debian Bookworm)
FROM php:8.2-cli-bookworm

# Prevent prompt interaction during package installation
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies, SQLite3, Python 3, and python3-venv
RUN apt-get update && apt-get install -y --no-install-recommends \
    sqlite3 \
    libsqlite3-dev \
    python3 \
    python3-pip \
    python3-venv \
    python3-dev \
    build-essential \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy dependency definition files first for caching
COPY composer.json ./
COPY requirements.txt ./

# Install Composer dependencies (if any exist)
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist --no-dev || true

# Set up Python virtual environment and install requirements
RUN python3 -m venv .venv && \
    .venv/bin/pip install --default-timeout=1000 --no-cache-dir --upgrade pip setuptools wheel && \
    .venv/bin/pip install --default-timeout=1000 --no-cache-dir -r requirements.txt

# Copy the rest of the application files
COPY . .

# Ensure storage and analytics directories exist and are writable
RUN mkdir -p storage analytics/artifacts && \
    chmod -R 777 storage analytics/artifacts

# Expose the application port
EXPOSE 1945

# Start the PHP built-in server routing through index.php
CMD ["php", "-S", "0.0.0.0:1945", "index.php"]
