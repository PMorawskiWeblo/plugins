# Dostosowywanie Koła Fortuny

## 📐 Rozmiar segmentów

W pliku: `assets/js/frontend.js` linia ~259

```javascript
var radius = Math.min(centerX, centerY) - 10;
```

**Jak zmienić rozmiar segmentów:**

- `- 10` = większe segmenty (wypełniają prawie całe koło)
- `- 20` = mniejsze segmenty
- `- 0` = segmenty wypełniają 100% canvas
- `- 50` = bardzo małe segmenty (jak było wcześniej)

**Zalecane:** `-10` do `-15` dla optymalnego wyglądu

---

## ✍️ Stylowanie tekstów

W pliku: `assets/js/frontend.js` linia ~300-314

### 1. **Grubość obrysu tekstu**

```javascript
ctx.lineWidth = 3;
```

- `2` = cieńszy obrys
- `3` = standardowy (aktualny)
- `4-5` = gruby obrys

### 2. **Rozmiar czcionki - Nazwa nagrody**

```javascript
ctx.font = 'bold 20px Arial';
```

Możesz zmienić:

- `20px` → `22px` lub `24px` (większa czcionka)
- `20px` → `18px` (mniejsza czcionka)
- `'Arial'` → `'Verdana'`, `'Georgia'`, itp. (inna czcionka)

### 3. **Rozmiar czcionki - Punkty**

```javascript
ctx.font = 'bold 18px Arial';
```

- `18px` → `20px` (większa czcionka)
- `18px` → `16px` (mniejsza czcionka)

### 4. **Odległość tekstu od centrum**

```javascript
var textDistance = radius - 50;
```

- `radius - 50` = tekst bliżej centrum
- `radius - 30` = tekst bliżej krawędzi
- `radius - 40` = pośrodku (zalecane)

**Im mniejsza wartość odejmowania, tym tekst bliżej krawędzi!**

### 5. **Pozycja pionowa tekstów**

```javascript
ctx.strokeText(nameText, textDistance, -5); // Nazwa (-5 = wyżej)
ctx.strokeText(pointsText, textDistance, 25); // Punkty (25 = niżej)
```

**Pozycje Y (pionowe):**

- Liczby ujemne (`-5`, `-10`) = tekst wyżej
- Liczby dodatnie (`25`, `30`) = tekst niżej
- `0` = na środku segmentu

---

## 🎨 Kolory tekstu (automatyczne)

Kolory tekstu są dobierane automatycznie na podstawie jasności tła:

- **Jasne tła** (żółty, różowy) → czarny tekst z białym obrysem
- **Ciemne tła** (granatowy, zielony) → biały tekst z czarnym obrysem

Funkcja: `getTextColorForBackground()` w linii ~247

---

## 📏 Rozmiar canvas koła

W plikach:

- `includes/class-loyalty-program-shortcodes.php` linia ~499
- `assets/js/frontend.js` linia ~463

```html
<canvas width="483" height="483"></canvas>
```

Aktualny rozmiar: **483x483px** (dopasowany do grafiki wheel_bg.png)

---

## 🖼️ Grafiki tła

W pliku: `assets/css/frontend.css` linia ~229

```css
.loyalty-wheel-wrapper {
  background-image: url('../img/wheel_bg.png');
}
```

Możesz zmienić na inną grafikę, np.:

- `wheel_bg_gold.png`
- `wheel_bg_blue.png`

---

## 💡 Przykłady dostosowań

### Większe segmenty + większy tekst

```javascript
// Linia ~259
var radius = Math.min(centerX, centerY) - 5;

// Linia ~302
ctx.font = 'bold 24px Arial';

// Linia ~311
ctx.font = 'bold 20px Arial';

// Linia ~306
var textDistance = radius - 35;
```

### Tekst bliżej krawędzi

```javascript
// Linia ~306
var textDistance = radius - 30; // Było: radius - 50
```

### Grubszy obrys tekstu

```javascript
// Linia ~301
ctx.lineWidth = 5; // Było: 3
```

---

## 🔧 Testowanie zmian

Po każdej zmianie:

1. Zapisz plik JavaScript
2. Wyczyść cache przeglądarki (Ctrl+Shift+R lub Cmd+Shift+R)
3. Odśwież stronę z kołem fortuny

---

## ❓ Najczęstsze pytania

**Q: Jak zwiększyć rozmiar całego koła?**
A: Zmień szerokość/wysokość w CSS `.loyalty-wheel-wrapper` (linia ~226 w frontend.css)

**Q: Tekst nie mieści się w segmencie?**
A: Zmniejsz rozmiar czcionki lub zwiększ `textDistance`

**Q: Segmenty są za małe?**
A: Zmniejsz wartość odejmowania w `radius` (np. z `-10` na `-5`)

**Q: Jak zmienić czcionkę na inną?**
A: W `ctx.font` zmień `'Arial'` na np. `'Verdana'`, `'Georgia'`, `'Tahoma'`
