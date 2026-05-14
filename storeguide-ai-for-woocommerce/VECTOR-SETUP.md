# StoreGuide AI - konfiguracja Vector Store (Pinecone)

Ten dokument pokazuje najprostszy start z semantic retrieval.

## 1. Co jest już gotowe w pluginie

- Opcja `Enable semantic retrieval integration (optional)` w zakładce `Knowledge & Index`.
- Pola konfiguracyjne:
  - `Pinecone index host`
  - `Pinecone API Key`
  - `Pinecone namespace` (opcjonalnie)
  - `Embedding model`
  - `Embedding API Key` (opcjonalnie)
- Automatyczne wysyłanie wektorów przy indeksowaniu dokumentów.
- Automatyczne usuwanie wektora przy usunięciu dokumentu.
- Fallback: klasyczny SQL retrieval działa zawsze, nawet gdy vector store jest niedostępny.

## 2. Załóż Pinecone

1. Wejdź na [https://www.pinecone.io/](https://www.pinecone.io/).
2. Utwórz konto i zaloguj się.
3. Utwórz nowy index:
   - metric: `cosine`
   - dimension:
     - `1536` dla `text-embedding-3-small`
     - `3072` dla `text-embedding-3-large`
4. Skopiuj:
   - `Index host`
   - `API key`

## 3. Ustawienia w WordPress

Przejdź: `StoreGuide AI -> Knowledge & Index`

1. Włącz: `Enable semantic retrieval integration (optional)`.
2. `Semantic top-K results`: ustaw 3-5 na start.
3. Wklej `Pinecone index host` (bez `https://`).
4. Wklej `Pinecone API Key`.
5. Opcjonalnie ustaw namespace (np. `storeguide-main`).
6. `Embedding model`: zostaw `text-embedding-3-small` na start.
7. `Embedding API Key`:
   - możesz wpisać osobny klucz OpenAI,
   - albo zostawić puste, wtedy plugin użyje głównego klucza providera OpenAI.
8. Zapisz ustawienia.

## 4. Reindeks

1. W tej samej zakładce kliknij `Run Index Batch` wielokrotnie aż indeks się dokończy.
2. Jeśli masz włączony background worker, plugin będzie dalej indeksował partiami.

## 5. Szybki test poprawności

1. Zadaj 3 pytania produktowe sformułowane naturalnie (nie słowo-w-słowo z tytułu).
2. Sprawdź, czy wyniki są trafniejsze niż wcześniej.
3. Zadaj to samo pytanie ponownie:
   - odpowiedź powinna być szybsza dzięki cache Q&A.

## 6. Ustawienia optymalizacji kosztów

Zalecane na start:

- `results_limit`: 3-5
- `semantic_top_k`: 3-5
- `cache_enabled`: ON
- `cache_ttl_minutes`: 1440
- `cache_max_entries`: 1000+
- Manual FAQ: włączone dla powtarzalnych pytań.

## 7. Typowe problemy

- Brak wyników semantic:
  - sprawdź host i API key Pinecone,
  - sprawdź zgodność dimension z embedding modelem.
- Słaba trafność:
  - zwiększ `semantic_top_k` do 7-10,
  - upewnij się, że indeksowanie przeszło przez większość dokumentów.
- Duże koszty:
  - zmniejsz `semantic_top_k`,
  - trzymaj niski `results_limit`,
  - wzmacniaj FAQ + cache.
