FROM php:8.2-cli

# Install poppler + GD dependencies
RUN apt-get update && apt-get install -y \
    poppler-utils \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

# Install GD extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

WORKDIR /app
COPY . /app

CMD ["php", "-S", "0.0.0.0:10000"]
