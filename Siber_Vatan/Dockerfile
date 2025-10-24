# PHP 8.2 ve Apache içeren resmi imajı kullan
FROM php:8.2-apache

# 1. ADIM: Sistem kütüphanelerini güncelle ve kur
# pdo_sqlite için libsqlite3-dev gerekir
# mbstring ve iconv için de kütüphaneler ekleniyor
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libonig-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. ADIM: Artık PHP eklentilerini kurabiliriz
RUN docker-php-ext-install pdo pdo_sqlite mbstring iconv

# Web sunucusunun ana dizinini çalışma dizini yap
WORKDIR /var/www/html

# Proje dosyalarının tamamını (PHP, lib, .db) konteynere kopyala
COPY . .

# İzinleri ayarlayacak olan giriş betiğini kopyala ve çalıştırılabilir yap
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Konteyner başladığında bu betiği çalıştır
ENTRYPOINT ["entrypoint.sh"]

# Betiğimiz, Apache'yi başlatmak için bu varsayılan komutu çağıracak
CMD ["apache2-foreground"]