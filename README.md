# Flip-City Beléptető és Foglalási Rendszer

Ez a csomag egy komplex trambulin park beléptető és foglalási rendszert valósít meg Laravel 11 alapon.

## Telepítés

1. Adja hozzá a repository-t a `composer.json`-höz (ha szükséges).
2. Futtassa a telepítést:
```bash
composer require weboldalnet/flip-city
```
3. Publikálja a konfigurációt és az asseteket:
```bash
php artisan vendor:publish --tag=flip-city-config
php artisan vendor:publish --tag=flip-city-assets
```

## Konfiguráció

A csomag beállításait a `config/flip-city.php` fájlban találja. Itt szabályozható:
- `billing_enabled`: Számlázási modul ki-/bekapcsolása.
- `default_rate`: Alapértelmezett óradíj.
- `companion_price`: Kísérő díja (fix összeg/fő).
- `show_profile_booking`: Foglalások megjelenítése a felhasználói profilban.

Bizonyos beállítások az admin felületen is felülírhatók, melyek a `flip_city_settings` táblában tárolódnak.

## Assetek Kezelése

A csomag SCSS és JavaScript fájljai külön-külön is publikálhatók a finomhangoláshoz:

### CSS publikálása
```bash
php artisan vendor:publish --tag=flip-city-css
```
A fájlok a `public/packages/flip-city/css` könyvtárba kerülnek.

### JS publikálása
```bash
php artisan vendor:publish --tag=flip-city-js
```
A fájlok a `public/packages/flip-city/js` könyvtárba kerülnek.

Ha az assetek publikálva vannak, a site oldali nézetek automatikusan a publikált verziókat fogják használni a csomagban lévők helyett (amennyiben a layout megfelelően van konfigurálva).

## Admin Felület
Az adminisztrációs felület a `/flip-city` (vagy a konfigurált prefix) útvonalon érhető el.

## Felhasználói Profil
A felhasználók a `/profile` oldalon érhetik el saját QR-kódjukat és kezelhetik foglalásaikat.
