# Fast Forms

Wtyczka WordPress do budowy formularzy drag & drop, zbierania zgłoszeń i zarządzania nimi w panelu administracyjnym.

**Autor:** [Weblo](https://weblo.pl/)  
**Wymagania:** WordPress 6.0+, PHP 8.0+

## Funkcje

- Builder formularzy (jQuery UI) w kokpicie WordPress
- Shortcode `[smart_form]` — tryby: `inline`, `button`, `trigger`
- Walidacja pól po stronie klienta i serwera
- Upload plików z walidacją typu i rozmiaru
- E-maile z merge tagami (`{all_fields}`, `{field:name}`, `{form:title}` itd.)
- reCAPTCHA v3 (ustawienia globalne)
- Lista zgłoszeń z filtrami i wyszukiwarką
- Eksport CSV (Manager formularzy)
- Limity wysyłki (tylko raz, cooldown, fingerprint)

## Instalacja

1. Skopiuj folder `fast-forms` do `wp-content/plugins/`
2. Aktywuj wtyczkę w **Wtyczki**
3. Przejdź do **Formularze** w menu kokpitu

## Shortcode

```
[smart_form id="123" display="inline"]
[smart_form id="123" display="button" button_text="Otwórz formularz"]
[smart_form id="123" display="trigger" trigger=".moj-przycisk"]
```

## Uprawnienia

Wtyczka dodaje capability `manage_fast_forms` rolom **Administrator** i **Redaktor**.

## Ustawienia globalne

**Formularze → Ustawienia globalne**

- reCAPTCHA v3 (site key, secret key, action, minimalny score)
- Opcja usuwania danych przy odinstalowaniu wtyczki

## Debug

W `fast-forms.php` można włączyć log developerski:

```php
define( 'FF_DEVELOPER_DEBUG', true );
```

Log zapisywany jest w `logs/fast-forms-debug.log` (domyślnie wyłączone).

## Odinstalowanie

Domyślnie dane (formularze, zgłoszenia) **pozostają** w bazie po usunięciu wtyczki. Aby usunąć wszystko, zaznacz opcję w ustawieniach globalnych przed odinstalowaniem.

## Struktura

```
fast-forms/
├── src/           # Klasy PHP (PSR-4)
├── assets/        # CSS i JS (admin + frontend)
├── templates/     # Szablony admin i frontend
├── languages/     # Tłumaczenia (text domain: fast-forms)
└── dev/           # Dokumentacja wdrożenia
```

## Tłumaczenia

Text domain: `fast-forms`  
Pliki `.po` / `.mo` umieść w katalogu `languages/`.

## Licencja

Własnościowa — Weblo.
