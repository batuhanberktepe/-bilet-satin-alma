#!/bin/sh

# Apache kullanıcısına (www-data) SQLite veritabanı dosyası için sahiplik ver
chown www-data:www-data otobus_bilet.db

# Sahip ve grubun dosyayı okuyup yazabilmesi için izinleri ayarla
chmod 664 otobus_bilet.db

# Dockerfile'da belirtilen asıl komutu (apache2-foreground) çalıştır
exec "$@"