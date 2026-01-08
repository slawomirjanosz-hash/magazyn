# OSTRZEŻENIE O BEZPIECZEŃSTWIE BAZY DANYCH

## NIEBEZPIECZNE POLECENIA - NIGDY NIE UŻYWAJ NA PRODUKCJI:
```bash
php artisan migrate:fresh      # USUWA CAŁĄ BAZĘ DANYCH!
php artisan migrate:refresh    # USUWA WSZYSTKIE DANE!
php artisan migrate:reset      # COFKA WSZYSTKIE MIGRACJE!
php artisan db:wipe            # CZYŚCI CAŁĄ BAZĘ!
```

## BEZPIECZNE POLECENIA:
```bash
php artisan migrate            # Dodaje nowe migracje (nie usuwa danych)
php artisan db:seed            # Dodaje dane testowe (nie usuwa istniejących)
php artisan migrate:status     # Sprawdza status migracji
```

## BACKUP PRZED KAŻDĄ MIGRACJĄ:
```bash
# Zrób backup przed migracją
mysqldump -u root magazyn > backup_$(date +%Y%m%d_%H%M%S).sql

# Potem uruchom migrację
php artisan migrate
```

## KONFIGURACJA ŚRODOWISKA:
- **production**: Blokuje migrate:fresh automatycznie
- **local/development**: Dozwolone dla testów

## CO ZROBIĆ JEŚLI STRACISZ DANE:
1. Przywróć z backupu
2. Sprawdź .env czy APP_ENV=production
3. Nigdy nie uruchamiaj migrate:fresh na produkcji
