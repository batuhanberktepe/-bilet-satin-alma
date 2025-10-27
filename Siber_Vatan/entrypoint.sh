 1. ADIM (EKSİK OLAN): ÖNCE KLASÖRÜN sahibini ve iznini ayarla
# Apache (www-data) kullanıcısının bu klasöre yeni dosyalar (-wal, -shm) oluşturabilmesi gerekir.
chown www-data:www-data $DB_DIR
chmod 775 $DB_DIR

# 2. ADIM: SONRA DOSYANIN sahibini ve iznini ayarla
chown www-data:www-data $DB_FILE
chmod 664 $DB_FILE

# Dockerfile'da belirtilen asıl komutu (apache2-foreground) çalıştır
exec "$@"
