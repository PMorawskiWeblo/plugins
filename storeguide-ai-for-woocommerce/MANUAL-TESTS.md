# StoreGuide AI for WooCommerce - Testy manualne

Ten dokument zawiera checklistę testów manualnych przed wydaniem.

## 1) Przygotowanie środowiska

- [ ] WordPress + WooCommerce aktywne.
- [ ] Wtyczka `StoreGuide AI for WooCommerce` aktywna.
- [ ] Co najmniej kilka produktów w sklepie (w tym produkt prosty i wariantowy).
- [ ] Co najmniej 1 strona i 1 wpis blogowy (jeśli testujesz indeks treści).
- [ ] Skonfigurowany provider AI (OpenAI/OpenRouter/Custom) z poprawnym API key.

## 2) Panel admin - podstawowe

- [ ] Wejście na `admin.php?page=storeguide-ai` działa bez błędu uprawnień.
- [ ] Zmiana zakładek działa poprawnie.
- [ ] `Save Settings` zapisuje ustawienia tylko dla aktywnej zakładki.
- [ ] Po zapisie widoczny komunikat sukcesu.
- [ ] `Test Connection` w zakładce providerów zwraca poprawny status.

## 3) Widget chat - podstawowe

- [ ] Widget widoczny na froncie, gdy wtyczka jest włączona.
- [ ] Otwieranie/zamykanie widgetu działa.
- [ ] Wiadomości użytkownika i asystenta mają poprawny styl (chmurki, odstępy).
- [ ] Typing indicator (3 kropki) pojawia się podczas oczekiwania na odpowiedź.
- [ ] Historia rozmowy utrzymuje się podczas przejścia między podstronami (sesja przeglądarki).
- [ ] Po pełnym zamknięciu przeglądarki historia znika.

## 4) REST API i bezpieczeństwo

- [ ] Request do `/wp-json/storeguide-ai/v1/chat` bez `X-WP-Nonce` zwraca `403`.
- [ ] Request z nieprawidłowym nonce zwraca `403`.
- [ ] Gdy asystent jest wyłączony w ustawieniach, endpoint zwraca `403`.
- [ ] Pusta wiadomość zwraca `400`.
- [ ] Wiadomość > 1200 znaków zwraca `400` (`message_too_long`).
- [ ] Frontend blokuje wpisanie wiadomości > 1200 znaków (walidacja po stronie UI).

## 5) Intencje i wyniki

- [ ] Zapytanie produktowe zwraca produkty.
- [ ] Zapytanie nieproduktowe (np. kontakt) nie zwraca kart produktowych.
- [ ] Zapytanie o kupony nie pokazuje produktów, jeśli nie ma intencji produktowej.
- [ ] Zapytanie o promocje zwraca produkty na wyprzedaży.
- [ ] Zapytanie o najtańsze i najdroższe produkty działa.
- [ ] Zapytanie o najpopularniejsze produkty działa.
- [ ] Zapytanie o produkty poniżej ceny (np. `ponizej 100`) działa.
- [ ] `meta.intent` w odpowiedzi API jest zgodne z treścią zapytania.

## 6) Formatowanie odpowiedzi i cen

- [ ] Ceny produktów prostych są czytelne (bez encji HTML).
- [ ] Ceny produktów wariantowych mają format `min - max`.
- [ ] Brak duplikatów lub doklejonych opisów typu `Zakres cen: ...` w UI wyników.

## 7) Knowledge & Index

- [ ] `Run Index Batch` działa i nie kończy się błędem.
- [ ] Progress bar i statystyki indeksowania aktualizują się poprawnie.
- [ ] Indeks produktów działa.
- [ ] Indeks stron/wpisów działa.
- [ ] Po pełnym reindeksie cache Q&A jest czyszczony.

## 8) ACF / meta indeksowanie

- [ ] Włączenie `acf_enabled` dla produktów działa.
- [ ] Włączenie `content_meta_enabled` dla stron/wpisów działa.
- [ ] Tryb auto-detect kluczy (`acf_auto_detect`) działa.
- [ ] Tryb auto-detect dla content (`content_meta_auto_detect`) działa.
- [ ] Auto-include działa: wykryte klucze + ręcznie wpisane klucze (bez duplikatów).
- [ ] Klucze systemowe/meta techniczne nie zaśmiecają indeksu.

## 9) FAQ, cache i uczenie

- [ ] Powtórzone pytanie może zostać obsłużone z cache (szybsza odpowiedź).
- [ ] `Manual FAQ` ma priorytet nad normalną odpowiedzią modelu.
- [ ] `Suggested fixes` mogą nadpisać odpowiedź dla danego pytania.
- [ ] Limity cache (`cache_max_entries`, TTL) działają.

## 10) Kupony i reguły

- [ ] Select2/SelectWoo kuponów działa z wyszukiwaniem po nazwie i ID.
- [ ] Dodawanie/usuwanie reguł kuponowych działa.
- [ ] Zakres dat `from/to` działa poprawnie.
- [ ] Opcja `include conditions` dodaje warunki kuponu do kontekstu.
- [ ] Dla dużej liczby kuponów wyszukiwanie nie zawiesza panelu.

## 11) Worker i wydajność

- [ ] Background worker (co 5 min) działa, gdy opcja jest włączona.
- [ ] Po wyłączeniu opcji worker przestaje się harmonogramować.
- [ ] Lock worker’a zapobiega równoległemu nakładaniu batchy.
- [ ] Duże batch size nie powoduje timeoutów ani krytycznych błędów.

## 12) Logi i analityka

- [ ] Wpisy logów pojawiają się dla sukcesów i błędów.
- [ ] Logi nie zawierają nadmiernie dużych payloadów.
- [ ] Raporty (zero-result, cache hits, top pytania) są spójne z ruchem testowym.

## 13) i18n / język

- [ ] Język wykrywany poprawnie z WPML/Polylang.
- [ ] Fallback językowy działa, gdy język nie jest wspierany.
- [ ] Komunikaty UI i odpowiedzi są spójne językowo.

## 14) Dezaktywacja / uninstall

- [ ] Dezaktywacja czyści harmonogram cron worker’a.
- [ ] Uninstall usuwa opcje wtyczki (w tym QA cache, FAQ options, progress/status).
- [ ] Po reinstallu domyślne opcje inicjalizują się poprawnie.

## 15) Kryteria gotowości do release

- [ ] Wszystkie testy krytyczne przeszły.
- [ ] Brak blockerów bezpieczeństwa.
- [ ] Brak regresji w głównym flow (chat -> retrieval -> odpowiedź).
- [ ] Wydajność akceptowalna na docelowym środowisku.
- [ ] Logi nie pokazują powtarzalnych błędów krytycznych.

---

## Notatki testowe

Data:

Tester:

Środowisko (WP/WC/PHP):

Wynik ogólny:

Uwagi:
