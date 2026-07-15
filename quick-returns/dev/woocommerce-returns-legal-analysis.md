# Wtyczka zwrotów WooCommerce — analiza wymagań prawnych i funkcjonalnych

## Cel

Celem wtyczki jest obsługa **odstąpienia od umowy zawartej na odległość** dla sklepu WooCommerce w sposób zgodny z polskim prawem, lekki technicznie i wygodny dla klienta. Konsument, który zawarł umowę na odległość lub poza lokalem przedsiębiorstwa, ma prawo odstąpić od niej bez podawania przyczyny.[web:5][page:1]

Dla sklepu internetowego standardowy termin na odstąpienie wynosi 14 dni.[web:7][page:1]

## Co jest prawnie wymagane

### 1. Prawo do odstąpienia

Wtyczka musi być projektowana wokół procesu „odstąpienia od umowy”, a nie wyłącznie „zwrotu”, ponieważ to właśnie to uprawnienie wynika z ustawy o prawach konsumenta dla sprzedaży internetowej.[web:5][page:1]

Klient powinien mieć możliwość złożenia oświadczenia o odstąpieniu bez konieczności podawania przyczyny.[web:5][page:1]

### 2. Termin

Dla zakupów internetowych termin wynosi 14 dni, liczony dla towarów od otrzymania rzeczy.[web:7][web:10]

Wtyczka powinna więc umieć zweryfikować datę doręczenia lub co najmniej datę realizacji/ukończenia zamówienia i zostawić administratorowi możliwość ręcznej korekty zasad kwalifikacji, bo WooCommerce nie zawsze ma stuprocentowo pewną datę odbioru przez klienta.[web:7][web:10]

### 3. Skutek prawny złożenia oświadczenia

Po otrzymaniu oświadczenia sprzedawca ma obowiązek zwrócić konsumentowi wszystkie dokonane płatności, w tym koszt dostarczenia towaru, nie później niż w terminie 14 dni od otrzymania oświadczenia.[page:2]

Przedsiębiorca może jednak wstrzymać się ze zwrotem pieniędzy do chwili otrzymania rzeczy z powrotem albo dowodu jej odesłania, w zależności od tego, co nastąpi wcześniej.[page:2]

To oznacza, że wtyczka powinna rozdzielać etapy: **zgłoszenie odstąpienia**, **oczekiwanie na towar / potwierdzenie nadania**, **akceptacja i rozliczenie**. Sam formularz nie powinien automatycznie oznaczać zwrotu środków jako wykonanego.[page:2]

### 4. Odesłanie towaru

Konsument ma obowiązek odesłać towar niezwłocznie, nie później niż w terminie 14 dni od dnia złożenia oświadczenia.[page:2]

W komunikatach końcowych i e-mailach wtyczka powinna więc jasno przekazywać instrukcję dalszych kroków, termin odesłania i adres zwrotu, jeśli sklep go udostępnia.[page:2]

### 5. Wyłączenia prawa odstąpienia

Nie każdy produkt może być objęty prawem odstąpienia. UOKiK wskazuje m.in. na towary nieprefabrykowane wykonywane według specyfikacji konsumenta, towary szybko psujące się, rzeczy zapieczętowane, których po otwarciu nie można zwrócić ze względów higienicznych lub zdrowotnych, oraz niektóre inne kategorie ustawowe.[web:6]

Wtyczka powinna więc umożliwiać wykluczanie produktów lub kategorii z procesu odstąpienia oraz pokazywanie komunikatu z uzasadnieniem braku kwalifikacji.[web:6]

### 6. Dane osobowe

Formularz dla niezalogowanego klienta będzie przetwarzał dane identyfikujące zamówienie, co najmniej numer zamówienia i adres e-mail. To oznacza konieczność ograniczenia zakresu danych do minimum, zabezpieczenia endpointów oraz wpisania procesu do polityki prywatności sklepu.[page:2]

Od strony UX warto dodać checkbox z potwierdzeniem zapoznania się z informacją o przetwarzaniu danych, ale sama zgodność RODO zależy też od treści polityki prywatności i podstaw przetwarzania poza wtyczką.[page:2]

## Co nie powinno być „na sztywno”

Nie zakładałbym na sztywno, że każdy zwrot jest zawsze „odstąpieniem bez powodu” dla wszystkich zamówień. W praktyce sklepy często mieszają proces odstąpienia i zwykłych zwrotów handlowych, ale prawnie to różne rzeczy, więc wtyczka powinna mieć tryb podstawowy: **ustawowe odstąpienie od umowy**, oraz opcjonalnie tryb rozszerzony: **polityka zwrotów sklepu**.[web:5][web:6]

Nie zakładałbym też automatycznego liczenia pełnej kwoty do zwrotu wyłącznie na froncie. Ostateczna kwota rozliczenia powinna być wyliczana po stronie serwera na podstawie pozycji zamówienia, ilości, kosztów wysyłki i reguł sklepu.[page:2]

## Wymagania funkcjonalne z Twojego opisu

### 1. Shortcody i wejścia do procesu

Wtyczka powinna mieć co najmniej trzy shortcody:

- `[wc_returns_form]` — pełny formularz / wizard.
- `[wc_returns_trigger]` — przycisk otwierający modal.
- `[wc_returns_order_lookup]` — krok 1 jako osobny formularz, jeśli chcesz rozbić widoki.

Jeśli użytkownik nie jest zalogowany, krok 1 powinien wymagać podania numeru zamówienia i adresu e-mail. To jest zgodne z założeniem minimalnej identyfikacji klienta bez tworzenia osobnego konta klienta.[page:2]

Jeśli użytkownik jest zalogowany i znajduje się w widoku konkretnego zamówienia, powinien od razu przejść do kroku 2 z pobranymi pozycjami zamówienia, bez ponownej identyfikacji.[page:2]

### 2. Trigger z edytowalnym tekstem i klasą

Powinien istnieć shortcode/blok/przycisk z atrybutami:

- `text` — tekst przycisku,
- `class` — klasa/klasy CSS przycisku,
- `mode` — np. `all_items` albo `manual_select`,
- `order_id` — opcjonalnie wymuszone zamówienie.

Dodatkowo panel ustawień powinien pozwolić zdefiniować listę klas CSS, po których kliknięciu JS delegacją otworzy modal formularza zwrotów. Dzięki temu można podpiąć modal do dowolnego elementu motywu lub buildera bez modyfikacji HTML.

### 3. Dwa tryby zaznaczania produktów

Z opisu wynikają dwa scenariusze:

- **„Zwróć zamówienie”** — jeśli klient jest zalogowany, wszystkie produkty w zamówieniu są zaznaczone domyślnie.
- **„Zgłaszam zwrot”** — klient sam wybiera produkt lub produkty.

To najlepiej rozwiązać przez parametr wejścia do modala/wizarda oraz wewnętrzny `selection_mode` zapisany w stanie sesji formularza.

### 4. Widok listy produktów

Każdy produkt w kroku 2 powinien zawierać:

- miniaturę,
- nazwę produktu,
- SKU i/lub EAN, jeśli dostępne,
- cenę za sztukę,
- informację o kupionej ilości,
- możliwość zmiany ilości zwracanych sztuk, ale tylko do liczby zakupionej,
- wybór powodu zwrotu,
- opcjonalny komentarz,
- checkbox zaznaczenia pozycji.

Na końcu ma być podsumowanie zwrotu, w tym liczba pozycji, suma wartości zgłoszonych pozycji i komunikat, że ostateczna kwota zwrotu zostanie potwierdzona przez sklep, jeśli chcesz zachować bezpieczny model prawno-operacyjny.

## Rekomendowana architektura OOP

### Główne moduły

```text
plugin/
├── plugin.php
├── src/
│   ├── Core/
│   │   ├── Plugin.php
│   │   ├── Container.php
│   │   ├── ServiceProviderInterface.php
│   │   └── Assets.php
│   ├── Admin/
│   │   ├── SettingsPage.php
│   │   ├── ReturnRequestListTable.php
│   │   └── MetaBoxes.php
│   ├── Domain/
│   │   ├── ReturnRequest.php
│   │   ├── ReturnItem.php
│   │   ├── EligibilityService.php
│   │   ├── RefundCalculator.php
│   │   └── OrderLookupService.php
│   ├── Infrastructure/
│   │   ├── Repository/
│   │   │   ├── ReturnRequestRepository.php
│   │   │   └── SettingsRepository.php
│   │   ├── PostType/
│   │   │   └── ReturnRequestPostType.php
│   │   └── Security/
│   │       ├── Nonce.php
│   │       └── RateLimiter.php
│   ├── Frontend/
│   │   ├── Shortcodes.php
│   │   ├── ModalController.php
│   │   ├── AjaxController.php
│   │   ├── WizardController.php
│   │   └── ViewModelFactory.php
│   └── Support/
│       ├── Logger.php
│       ├── Helpers.php
│       └── Templates.php
├── templates/
├── assets/
│   ├── js/
│   ├── css/
│   └── img/
└── languages/
```

### Najważniejsze zasady

- Bootstrap tylko w jednym pliku wejściowym i z PSR-4 autoload.
- Brak globali poza funkcją inicjującą plugin.
- Logika kwalifikacji zwrotu i wyliczeń po stronie serwera.
- Front jedynie renderuje stan i wysyła żądania AJAX/REST.
- Szablony HTML trzymane osobno, a nie sklejane w PHP stringami.

## Model danych

Najlżejsza opcja dla MVP to **custom post type** `shop_return_request` plus meta dla danych zgłoszenia. To daje prosty panel administracyjny, historię zmian i integrację z uprawnieniami WordPressa.

Minimalne pola zgłoszenia:

- `request_number`
- `order_id`
- `order_key`
- `customer_id`
- `customer_email`
- `status` (`new`, `awaiting_shipment`, `received`, `approved`, `rejected`, `refunded`, `closed`)
- `submitted_at`
- `items` (tablica JSON)
- `totals` (tablica JSON)
- `consents` (tablica JSON)
- `customer_note`
- `admin_note`
- `source_context` (`shortcode`, `order_view`, `modal_trigger_class`)

Jeśli wiesz, że zgłoszeń będzie dużo, docelowo lepsza będzie własna tabela, ale do lekkiej pierwszej wersji CPT jest rozsądnym kompromisem.

## UX i wydajność

### Wydajność

- Ładuj assets tylko tam, gdzie wykryto shortcode, endpoint konta lub aktywny trigger klasowy.
- Użyj jednego małego bundla JS bez jQuery, z delegacją zdarzeń.
- CSS namespacuj np. prefiksem `.wcr-`.
- Dane zamówienia pobieraj lazy dopiero po poprawnej identyfikacji zamówienia.
- Użyj transientów lub lekkiego cache dla ustawień i map powodów zwrotu.

### Bezpieczeństwo

- Nonce dla wszystkich akcji.
- Rate limit dla lookupu numer zamówienia + e-mail.
- Nie ujawniaj, czy numer zamówienia istnieje, jeśli e-mail nie pasuje.
- Sanitizacja i walidacja wszystkich pól.
- Capability checks w panelu admina.

### Loadery i stany

Interfejs powinien mieć co najmniej:

- skeleton/loading dla przejścia między krokami,
- disabled state na przyciskach w trakcie requestu,
- inline errors przy polach,
- stan sukcesu z numerem zgłoszenia,
- stan błędu technicznego bez ujawniania szczegółów środowiska.

## Minimalny flow użytkownika

### Gość

1. Otwiera modal lub stronę zwrotów.
2. Podaje numer zamówienia i e-mail.
3. System weryfikuje zamówienie.
4. Widzi listę kwalifikujących się produktów.
5. Zaznacza produkty lub ma je zaznaczone automatycznie w trybie „zwróć zamówienie”.
6. Uzupełnia ilości, powód, komentarz.
7. Widzi podsumowanie.
8. Składa oświadczenie.
9. Dostaje potwierdzenie i instrukcję dalszych kroków.[page:2]

### Zalogowany użytkownik na stronie zamówienia

1. Klika trigger.
2. Pomijany jest krok identyfikacji.
3. Otwiera się krok 2 z produktami zamówienia.
4. Dalej proces jest taki sam jak wyżej.

## Ustawienia administracyjne

Panel ustawień powinien zawierać:

- teksty przycisków,
- domyślne klasy przycisków,
- listę klas CSS otwierających modal,
- etykiety kroków,
- listę powodów zwrotu,
- adres zwrotu,
- treść komunikatu po zgłoszeniu,
- opcję automatycznego zaznaczenia wszystkich produktów,
- mapę wykluczonych produktów/kategorii,
- regułę terminu kwalifikacji,
- zgodę/pola informacyjne RODO,
- ustawienia e-maili do klienta i administratora.

## Co warto doprecyzować przed implementacją

### Decyzje biznesowe

- Czy obsługujesz wyłącznie konsumenta, czy też firmy/B2B.
- Czy zwrot ma dotyczyć tylko zamówień ze statusem `completed`, czy też `processing`.
- Czy data 14 dni liczona jest od statusu, od daty wysyłki, czy z integracji przewoźnika.
- Czy sklep dopuszcza częściowy zwrot kosztów dostawy przy częściowym odstąpieniu.
- Czy chcesz generować PDF formularza odstąpienia.
- Czy panel admina ma pozwalać na zmianę statusów i eksport CSV.

## Specyfikacja MVP

### Zakres MVP

- OOP + PSR-4.
- Shortcody.
- Modal i strona pełna.
- Lookup po numerze zamówienia i e-mailu.
- Pomijanie kroku 1 dla zalogowanego klienta w widoku zamówienia.
- Lista produktów z miniaturą, nazwą, ceną za sztukę i ilością.
- Powód zwrotu + komentarz.
- Podsumowanie.
- CPT zgłoszeń.
- E-mail potwierdzający.
- Wykluczenia produktów/kategorii.
- Assets ładowane warunkowo.

### Poza MVP

- Integracja z przewoźnikiem i etykietą zwrotną.
- Automatyczny refund w WooCommerce.
- PDF i eksporty.
- Integracja z zewnętrznym CRM/ERP.

## Wniosek projektowy

Najbezpieczniej budować tę wtyczkę jako **system zgłoszenia odstąpienia**, a nie „automat refundów”. Wynika to zarówno z obowiązków ustawowych, jak i z faktu, że przedsiębiorca może wstrzymać zwrot pieniędzy do czasu otrzymania rzeczy lub dowodu jej odesłania.[page:2]

Z perspektywy architektury WordPress/WooCommerce najlepszy będzie lekki plugin OOP z warunkowym ładowaniem assets, serwerową walidacją, krótkim wizardem 3-krokowym i osobnym modułem kwalifikacji prawnej produktów. Taki układ pozwoli później dołożyć własną tabelę, REST API i automatyzacje bez przepisywania całego rdzenia.
