# Fakturownia by Weblo - WooCommerce integration

Integracja WooCommerce z Fakturownia:

- wystawianie faktur do zamówień,
- wystawianie korekt do zwrotów,
- wysyłka dokumentów e-mailem,
- operacje masowe,
- logi błędów i narzędzia debugowania.

## Wymagania

- WordPress + WooCommerce,
- konto w Fakturownia,
- domena Fakturownia (np. `twojsklep.fakturownia.pl`),
- token API z Fakturownia.

## Instalacja

1. Wgraj folder wtyczki do:
   - `wp-content/plugins/fakturownia`
2. Włącz wtyczkę w WordPressie.
3. Przejdź do:
   - `WooCommerce -> Ustawienia -> Integracja -> Fakturownia by Weblo`

## Szybka konfiguracja

1. Zakładka **Connection**:
   - wpisz domenę Fakturownia (bez `https://`),
   - wpisz token API,
   - opcjonalnie ustaw `Company / department ID`,
   - kliknij **Test connection**.
2. Po udanym teście odblokują się pozostałe zakładki.
3. Ustaw:
   - statusy wyzwalające faktury,
   - automatyczne korekty po zwrocie,
   - tryb korekt (`difference` / `full`),
   - szablony e-maili WooCommerce.

## Najważniejsze funkcje

- **Metabox zamówienia**
  - ręczne wystawienie faktury,
  - ręczne wystawienie korekty,
  - wysyłka e-maila,
  - pobieranie PDF.

- **Automatyzacje**
  - auto-faktura po zmianie statusu zamówienia,
  - auto-korekta po zwrocie.

- **Operacje masowe**
  - wystawianie brakujących faktur,
  - wystawianie brakujących korekt,
  - progress bar i możliwość zatrzymania.

- **Logi**
  - logi błędów integracji w tabeli DB,
  - `debug.log` (opcjonalny, limit 2 MB),
  - przyciski czyszczenia logów.

## Shortcode

Na stronie podglądu zamówienia możesz użyć:

- `[weblo_fakturownia_invoice_pdf]`
- `[weblo_fakturownia_invoice_pdf text="Pobierz fakturę"]`
- `[weblo_fakturownia_invoice_pdf text="Pobierz PDF" order_id="123"]`

Shortcode generuje podpisany link do pobrania PDF bez potrzeby logowania do Fakturownia.

## Tłumaczenia

Pliki tłumaczeń są w:

- `languages/`

Text domain:

- `weblo-fakturownia`

## Rozwiązywanie problemów

1. **Brak zakładek po wpisaniu API**
   - uruchom **Test connection**,
   - upewnij się, że `Connection` zwraca sukces.

2. **Nie tworzy faktury**
   - sprawdź czy zamówienie ma pozycje i dane klienta,
   - sprawdź metabox (czytelny komunikat błędu + szczegóły).

3. **Nie wysyła e-maila**
   - sprawdź konfigurację SMTP WordPress,
   - sprawdź ustawienia "Send from WooCommerce".

4. **Nadal widzisz stare błędy w logach**
   - użyj przycisku **Clear database logs** w zakładce Logs.

## Dla developera

Dokumentacja techniczna:

- `DEVELOPER.md`

