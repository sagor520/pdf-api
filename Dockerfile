FROM php:8.2-cli

# Poppler install
RUN apt-get update && apt-get install -y poppler-utils

# Working directory
WORKDIR /app

# Copy project files
COPY . /app

# Run PHP server
CMD ["php", "-S", "0.0.0.0:10000"]
