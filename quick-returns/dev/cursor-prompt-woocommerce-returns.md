# Prompt do Cursora — WooCommerce Returns Plugin

Zaprojektuj i przygotuj kompletny szkielet lekkiej, obiektowej wtyczki WordPress / WooCommerce do obsługi **odstąpienia od umowy / zwrotów** dla sklepu internetowego w Polsce.

## Kontekst biznesowy

Wtyczka ma obsługiwać proces zgodny z polskim prawem konsumenckim dla sprzedaży na odległość. Konsument ma prawo odstąpić od umowy zawartej na odległość bez podawania przyczyny, standardowo w terminie 14 dni dla zakupów internetowych.[web:5][web:7]

Sprzedawca po otrzymaniu oświadczenia powinien zwrócić płatności w terminie do 14 dni, ale może wstrzymać się ze zwrotem środków do momentu otrzymania towaru lub potwierdzenia jego odesłania.[page:2]

Wtyczka ma więc służyć przede wszystkim do **złożenia oświadczenia o odstąpieniu i obsługi zgłoszenia**, a nie do automatycznego wykonywania refundu.

## Wymagania techniczne

- WordPress + WooCommerce.
- Architektura obiektowa, czysty podział odpowiedzialności.
- PSR-4 autoload.
- Bez frameworków typu Symfony/Laravel.
- Minimalna liczba zależności.
- Lekka i szybka, assets ładowane wyłącznie tam, gdzie trzeba.
- Frontend bez jQuery, czysty JavaScript.
- Kod gotowy do dalszej rozbudowy.
- Stosuj namespace dla całej wtyczki, np. `Vendor\\WcReturns`.
- Sanitizacja, walidacja, escaped output, nonce i capability checks.
- Wszystkie teksty tłumaczalne.

## Cel funkcjonalny

Stwórz plugin, który realizuje 3-krokowy wizard zwrotu:

1. **Zamówienie**
2. **Produkty**
3. **Potwierdzenie**

## Zachowanie użytkownika

### Gość / niezalogowany

Jeśli użytkownik nie jest zalogowany, w kroku 1 ma zobaczyć formularz z polami:

- numer zamówienia,
- adres e-mail.

Po poprawnej walidacji zamówienia przechodzi do kroku 2.

### Zalogowany klient

Jeśli użytkownik jest zalogowany i otwiera formularz z poziomu widoku konkretnego zamówienia w „Moje konto”, ma zostać od razu przeniesiony do kroku 2, z pominięciem kroku 1.

## Wejścia do formularza

Plugin ma udostępniać:

### 1. Shortcode pełnego formularza

`[wc_returns_form]`

Renderuje pełny wizard zwrotu.

### 2. Shortcode przycisku triggera

`[wc_returns_trigger text="Zgłoś zwrot" class="btn btn-primary" mode="manual_select"]`

Wymagania:

- edytowalny tekst przycisku,
- edytowalna klasa / klasy CSS,
- możliwość przekazania trybu działania,
- kliknięcie otwiera modal z formularzem zwrotów.

### 3. Trigger po klasach CSS

W ustawieniach pluginu ma być opcja podania listy klas CSS, np.:

- `.open-return-modal`
- `.js-return-trigger`

Kliknięcie dowolnego elementu pasującego do tych selektorów ma otwierać modal formularza przez event delegation.

## Tryby wyboru produktów

Wtyczka ma wspierać 2 tryby:

### Tryb A: „Zwróć zamówienie”

- gdy użytkownik jest zalogowany, wszystkie produkty w zamówieniu są domyślnie zaznaczone,
- użytkownik nadal może zmieniać ilości, jeśli w pozycji było więcej niż 1 sztuka.

### Tryb B: „Zgłaszam zwrot”

- użytkownik sam wybiera produkty do zwrotu,
- domyślnie nic nie musi być zaznaczone.

Zaimplementuj to przez jawny `selection_mode` w stanie formularza.

## Widok kroku 2 — lista produktów

W kroku „Produkty” pokaż listę pozycji zamówienia. Każda pozycja ma zawierać:

- checkbox zaznaczenia produktu,
- miniaturę produktu,
- nazwę produktu,
- SKU, jeśli istnieje,
- EAN, jeśli istnieje jako meta,
- cenę za sztukę,
- informację o liczbie kupionych sztuk,
- select lub input ilości zwracanych sztuk, ale tylko do maksymalnej kupionej ilości,
- pole „Powód zwrotu” jako select,
- pole „Komentarz” jako textarea opcjonalne.

Na dole widoku ma być:

- liczba zaznaczonych produktów,
- suma sztuk,
- szacowana wartość zgłoszenia,
- komunikat informacyjny, że ostateczna kwota zwrotu zostanie potwierdzona przez sklep,
- główny przycisk wysłania zgłoszenia.

## UX / UI

Chcę prosty, lekki, nowoczesny interfejs podobny do klasycznego 3-step wizard.

Wymagania UI:

- modal oraz możliwość renderu inline,
- loadery / skeletony podczas pobierania danych,
- disabled state na buttonach podczas requestu,
- czytelne błędy inline,
- success state z numerem zgłoszenia,
- możliwość edycji etykiet kroków i tekstów przycisków,
- CSS namespacowane prefiksem np. `.wcr-`.

## Wymagania prawne i biznesowe

Uwzględnij w architekturze:

- standardowe 14 dni na odstąpienie dla zakupów online,[web:7]
- złożenie oświadczenia bez obowiązku podawania przyczyny,[web:5]
- informację, że klient powinien odesłać towar w ciągu 14 dni od złożenia oświadczenia,[page:2]
- informację, że sklep może wstrzymać zwrot środków do czasu otrzymania rzeczy lub dowodu nadania,[page:2]
- możliwość wykluczenia produktów / kategorii z prawa odstąpienia, np. personalizowanych lub higienicznych po otwarciu.[web:6]

Nie twórz automatycznego refundu jako części MVP.

## Architektura pluginu

Przygotuj strukturę katalogów podobną do tej:

```text
woocommerce-returns/
├── woocommerce-returns.php
├── composer.json
├── uninstall.php
├── src/
│   ├── Core/
│   ├── Admin/
│   ├── Frontend/
│   ├── Domain/
│   ├── Infrastructure/
│   └── Support/
├── templates/
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
└── languages/
```

Oczekuję od Ciebie:

1. wygenerowania pełnej architektury plików,
2. przygotowania klas bazowych,
3. przygotowania bootstrapu pluginu,
4. rejestracji shortcode’ów,
5. rejestracji assets z warunkowym enqueue,
6. implementacji modala,
7. implementacji endpointów AJAX lub REST do:
   - lookupu zamówienia,
   - pobrania pozycji,
   - zapisania zgłoszenia,
8. zapisania zgłoszenia jako custom post type lub w dedykowanej warstwie repozytorium,
9. przygotowania prostego panelu ustawień,
10. przygotowania szablonów frontowych.

## Model danych

Dla MVP użyj custom post type `shop_return_request`.

Zapisuj co najmniej:

- numer zgłoszenia,
- ID zamówienia,
- ID klienta,
- e-mail klienta,
- status zgłoszenia,
- listę pozycji,
- ilości,
- powody zwrotu,
- komentarze,
- sumy,
- datę zgłoszenia,
- notatki administratora,
- źródło otwarcia formularza.

Statusy minimum:

- `new`
- `awaiting_shipment`
- `received`
- `approved`
- `rejected`
- `refunded`
- `closed`

## Ustawienia admina

Dodaj stronę ustawień pluginu z polami:

- tekst triggera,
- domyślne klasy triggera,
- lista selektorów CSS otwierających modal,
- lista powodów zwrotu,
- adres zwrotu,
- treść komunikatu po zgłoszeniu,
- treści e-maili,
- wykluczone produkty,
- wykluczone kategorie,
- reguła kwalifikacji terminu,
- opcja automatycznego zaznaczania wszystkich produktów.

## Standard kodu

Wymagania jakościowe:

- SRP i separacja warstw,
- brak logiki HTML w kontrolerach,
- widoki w `templates/`,
- helper do renderowania templatek,
- wszystkie dane wejściowe walidowane,
- brak bezpośredniego zaufania do danych z frontu,
- serwer liczy podsumowanie,
- gotowość do unit-testów i dalszego refaktoru.

## Wydajność

Zadbaj o to, aby:

- CSS i JS ładowały się tylko na stronach, gdzie formularz jest potrzebny,
- lookup zamówienia miał ochronę nonce i prosty rate limit,
- JS był modularny i mały,
- nie używać ciężkich bibliotek UI,
- dane produktów pobierać dopiero po identyfikacji zamówienia,
- plugin był bezpieczny dla sklepów z większą liczbą zamówień.

## Co ma być wygenerowane

Przygotuj wynik w formie kompletnego planu implementacji i kodu startowego dla MVP.

Oczekuję:

- listy plików,
- zawartości najważniejszych plików,
- klas PHP,
- przykładowych templatek,
- przykładowego JS do obsługi modala i kroków,
- przykładowego CSS,
- opisu hooków WooCommerce / WordPress, które wykorzystasz,
- listy dalszych kroków po MVP.

## Dodatkowe założenia

- Styl kodu: produkcyjny, nie tutorialowy.
- Nie używaj zbędnych komentarzy.
- Kod ma być gotowy do dalszego rozwijania.
- Jeśli czegoś brakuje, podejmij rozsądne decyzje architektoniczne i jasno je opisz.
- Priorytet: lekkość, bezpieczeństwo, zgodność z WooCommerce i łatwa rozbudowa.
