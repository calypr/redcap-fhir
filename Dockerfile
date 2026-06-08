FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Etc/UTC

# Install system dependencies
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone \
    && apt-get update && apt-get install -y --no-install-recommends \
    tzdata \
    apache2 \
    php \
    php-mysql \
    php-curl \
    php-json \
    php-mbstring \
    default-mysql-client \
    curl \
    wget \
    git \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite ssl

# Create REDCap directory
RUN mkdir -p /var/www/redcap

# Download and setup REDCap (using community edition or you can mount your own)
WORKDIR /var/www/redcap

# Copy Apache configuration
COPY docker/apache.conf /etc/apache2/sites-available/redcap.conf
RUN a2ensite redcap.conf && a2dissite 000-default.conf

# Expose port
EXPOSE 80 443

# Start Apache
CMD ["apache2ctl", "-D", "FOREGROUND"]
