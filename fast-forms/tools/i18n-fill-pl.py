#!/usr/bin/env python3
"""Fill Polish translations in fast-forms-pl_PL.po."""

from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PO_PATH = ROOT / "languages" / "fast-forms-pl_PL.po"
BAK_PATH = ROOT / "languages" / "fast-forms-pl_PL.po.bak"

PL: dict[str, str] = {
    "Buduj formularze drag & drop, zbieraj zgłoszenia i zarządzaj nimi w panelu WordPress.": "Buduj formularze drag & drop, zbieraj zgłoszenia i zarządzaj nimi w panelu WordPress.",
    "Submission": "Zgłoszenie",
    "Form": "Formularz",
    "Submitted at": "Data wysłania",
    "Status": "Status",
    "New": "Nowe",
    "Read": "Odczytane",
    "Archived": "Zarchiwizowane",
    "You do not have permission.": "Brak uprawnień.",
    "Access denied": "Brak dostępu",
    "Invalid request.": "Nieprawidłowe żądanie.",
    "Error": "Błąd",
    "The file does not exist.": "Plik nie istnieje.",
    "The file is not available.": "Plik nie jest dostępny.",
    "Not found": "Nie znaleziono",
    "The form does not exist.": "Formularz nie istnieje.",
    "Name": "Imię / nazwa",
    "Form ID": "ID formularza",
    "Email": "E-mail",
    "Phone": "Telefon",
    "reCAPTCHA score": "Score reCAPTCHA",
    "Schema version": "Wersja schemy",
    "Yes": "Tak",
    "No": "Nie",
    "Forms": "Formularze",
    "Add new form": "Dodaj nowy formularz",
    "Form submissions": "Przesłane formularze",
    "Global settings": "Ustawienia globalne",
    "Form manager": "Manager formularzy",
    "You do not have permission to view this page.": "Nie masz uprawnień do wyświetlenia tej strony.",
    "Settings": "Ustawienia",
    "All forms": "Wszystkie formularze",
    "All statuses": "Wszystkie statusy",
    "Search": "Szukaj",
    "Search submissions…": "Szukaj zgłoszeń…",
    "From": "Od",
    "To": "Do",
    "Date from": "Data od",
    "Date to": "Data do",
    "Submission status": "Status zgłoszenia",
    "Save the entry to update the status (click “Update” above).": "Zapisz wpis, aby zaktualizować status (przycisk „Aktualizuj” powyżej).",
    "Summary": "Podsumowanie",
    "Answers": "Odpowiedzi",
    "No saved answers.": "Brak zapisanych odpowiedzi.",
    "Field": "Pole",
    "Answer": "Odpowiedź",
    "Select a form before exporting.": "Wybierz formularz przed eksportem.",
    "Export submissions to CSV": "Eksport zgłoszeń do CSV",
    "Download submissions for the selected form as CSV (UTF-8, semicolon separator).": "Pobierz zgłoszenia wybranego formularza w formacie CSV (UTF-8, separator średnik).",
    "required": "wymagane",
    "— Select form —": "— Wybierz formularz —",
    "Export CSV": "Eksportuj CSV",
    "Invalid form.": "Nieprawidłowy formularz.",
    "Could not generate the CSV file.": "Nie udało się wygenerować pliku CSV.",
    "Entry ID": "ID zgłoszenia",
    "Could not save the form schema.": "Nie udało się zapisać schemy formularza.",
    "The form has been saved.": "Formularz został zapisany.",
    "New submission: {form:title}": "Nowe zgłoszenie: {form:title}",
    "Submission confirmation": "Potwierdzenie zgłoszenia",
    "Thank you for submitting the form.": "Dziękujemy za przesłanie formularza.",
    "The form has been submitted. Thank you!": "Formularz został wysłany. Dziękujemy!",
    "Add new": "Dodaj nowy",
    "Edit form": "Edytuj formularz",
    "New form": "Nowy formularz",
    "View form": "Zobacz formularz",
    "Search forms": "Szukaj formularzy",
    "No forms found.": "Nie znaleziono formularzy.",
    "No forms found in trash.": "Nie znaleziono formularzy w koszu.",
    "Form name": "Nazwa formularza",
    "Add new submission": "Dodaj nowe zgłoszenie",
    "View submission": "Zobacz zgłoszenie",
    "New submission": "Nowe zgłoszenie",
    "Search submissions": "Szukaj zgłoszeń",
    "No submissions found.": "Nie znaleziono zgłoszeń.",
    "No submissions found in trash.": "Nie znaleziono zgłoszeń w koszu.",
    "All submissions": "Wszystkie zgłoszenia",
    "Submission — %s": "Zgłoszenie — %s",
    "Submission #%1$d — %2$s": "Zgłoszenie #%1$d — %2$s",
    "The file is too large.": "Plik jest zbyt duży.",
    "Could not save file: %s": "Nie udało się zapisać pliku: %s",
    "The form is unavailable.": "Formularz jest niedostępny.",
    "Your session has expired. Refresh the page and try again.": "Sesja wygasła. Odśwież stronę i spróbuj ponownie.",
    "Please correct the errors in the form.": "Popraw błędy w formularzu.",
    "Could not save the submission.": "Nie udało się zapisać zgłoszenia.",
    "This form has already been submitted.": "Ten formularz został już wysłany.",
    "You can submit this form again shortly.": "Możesz wysłać ten formularz ponownie za chwilę.",
    "Too many submission attempts. Please try again later.": "Zbyt wiele prób wysłania formularza. Spróbuj ponownie później.",
    "Anti-spam verification failed. Refresh the page and try again.": "Weryfikacja antyspamowa nie powiodła się. Odśwież stronę i spróbuj ponownie.",
    "Could not verify reCAPTCHA. Please try again.": "Nie udało się zweryfikować reCAPTCHA. Spróbuj ponownie.",
    "Anti-spam verification failed.": "Weryfikacja antyspamowa nie powiodła się.",
    "Your submission was blocked as suspicious. Please try again later.": "Twoje zgłoszenie zostało zablokowane jako podejrzane. Spróbuj ponownie później.",
    "Could not verify Turnstile. Please try again.": "Nie udało się zweryfikować Turnstile. Spróbuj ponownie.",
    "Please enter a valid email address.": "Podaj prawidłowy adres e-mail.",
    "Please enter a valid URL.": "Podaj prawidłowy adres URL.",
    "The value is too short.": "Wartość jest zbyt krótka.",
    "The value is too long.": "Wartość jest zbyt długa.",
    "Please enter a valid number.": "Podaj prawidłową liczbę.",
    "Please enter a valid rating.": "Podaj prawidłową ocenę.",
    "The value is too small.": "Wartość jest zbyt mała.",
    "The value is too large.": "Wartość jest zbyt duża.",
    "An invalid option was selected.": "Wybrano nieprawidłową opcję.",
    "This field is required.": "To pole jest wymagane.",
    "Only one file can be uploaded.": "Można przesłać tylko jeden plik.",
    "You can upload at most %d file(s).": "Można przesłać maksymalnie %d plik(ów).",
    "An error occurred while uploading the file.": "Błąd podczas przesyłania pliku.",
    "This file type is not allowed.": "Niedozwolony typ pliku.",
    "Select at least %d option(s).": "Wybierz co najmniej %d opcję/opcje.",
    "Select at most %d option(s).": "Wybierz maksymalnie %d opcję/opcje.",
    "Upload at least %d file(s).": "Prześlij co najmniej %d plik(ów).",
    "Close": "Zamknij",
    "Sending…": "Wysyłanie…",
    "Please fill in the required fields.": "Uzupełnij wymagane pola.",
    "Maximum size:": "Maksymalny rozmiar:",
    "An error occurred while submitting. Please try again.": "Wystąpił błąd podczas wysyłki. Spróbuj ponownie.",
    "Too many files selected.": "Wybrano zbyt wiele plików.",
    "Upload at least the minimum number of files.": "Prześlij co najmniej wymaganą liczbę plików.",
    "Anti-spam verification error.": "Błąd weryfikacji antyspamowej.",
    "Form configuration error.": "Błąd konfiguracji formularza.",
    "Submission error.": "Błąd wysyłki.",
    "Leave this field empty": "Pozostaw to pole puste",
    "— Select —": "— Wybierz —",
    "Choose files": "Wybierz pliki",
    "Choose file": "Wybierz plik",
    "Remove file": "Usuń plik",
    "No file selected": "Nie wybrano pliku",
    "%1$d of %2$d stars": "%1$d z %2$d gwiazdek",
    "Allowed file types: %s": "Dozwolone typy plików: %s",
    "Maximum file size: %s": "Maksymalny rozmiar pliku: %s",
    "Maximum number of files: %d": "Maksymalna liczba plików: %d",
    "Minimum number of files: %d": "Minimalna liczba plików: %d",
    "All common file types are allowed.": "Dozwolone są wszystkie popularne typy plików.",
    "Open form": "Otwórz formularz",
    "Settings saved.": "Ustawienia zapisane.",
    "Save settings": "Zapisz ustawienia",
    "form slug (post name)": "slug formularza (nazwa wpisu)",
    "numeric form ID": "numeryczne ID formularza",
    "sanitized form title": "znormalizowany tytuł formularza",
    "Row": "Wiersz",
    "Move row up": "Przesuń wiersz w górę",
    "Move row down": "Przesuń wiersz w dół",
    "Loading form…": "Ładowanie formularza…",
    "Could not load the form. Refresh the page and try again.": "Nie udało się wczytać formularza. Odśwież stronę i spróbuj ponownie.",
    "HTML ID": "ID HTML",
    "Default": "Domyślna",
    "Allow multiple files": "Zezwól na wiele plików",
    "Max. number of files": "Maks. liczba plików",
    "Min. number of files": "Min. liczba plików",
    "Choose file button text": "Tekst przycisku wyboru pliku",
    "Layout": "Układ",
    "Vertical": "Pionowy",
    "Horizontal": "Poziomy",
    "Show upload rules": "Pokaż reguły uploadu",
    "Allowed HTML tags: br, strong, em, a, p, span (with href/title/target/rel/class).": "Dozwolone tagi HTML: br, strong, em, a, p, span (z href/title/target/rel/class).",
    "Custom HTML id on the field wrapper. Leave empty for no id. Must be unique within the form.": "Własne id HTML na opakowaniu pola. Zostaw puste, jeśli niepotrzebne. Musi być unikalne w formularzu.",
    "Pre-select this option when the form loads. For single choice fields only one default is allowed.": "Zaznacz tę opcję po załadowaniu formularza. W polach jednokrotnego wyboru dozwolona jest tylko jedna domyślna opcja.",
    "When enabled, visitors can upload more than one file in this field.": "Po włączeniu odwiedzający mogą przesłać więcej niż jeden plik w tym polu.",
    "Maximum number of files that can be uploaded. Leave empty for no limit.": "Maksymalna liczba plików do przesłania. Zostaw puste, aby nie ustawiać limitu.",
    "Minimum number of files required when multiple upload is enabled. Leave empty to use the required flag only (minimum 1).": "Minimalna liczba plików przy wielokrotnym uploadzie. Zostaw puste, aby użyć tylko flagi wymagane (minimum 1).",
    "Custom label on the file upload button. Leave empty for the default text.": "Własna etykieta przycisku wyboru pliku. Zostaw puste dla tekstu domyślnego.",
    "Display options or uploaded files vertically (stacked) or horizontally (in a row).": "Wyświetlaj opcje lub pliki pionowo (jeden pod drugim) lub poziomo (w rzędzie).",
    "Vertical keeps a dropdown. Horizontal displays options in a row (like radio buttons).": "Pionowy zostawia listę rozwijaną. Poziomy wyświetla opcje w rzędzie (jak przyciski radio).",
    "When enabled, shows allowed file types and size limits below the upload button.": "Po włączeniu pokazuje dozwolone typy plików i limity rozmiaru pod przyciskiem uploadu.",
    "Consent text shown next to the checkbox. You can use HTML (e.g. br, a, strong) — see the hint below the field.": "Treść zgody obok checkboxa. Możesz użyć HTML (np. br, a, strong) — zobacz podpowiedź pod polem.",
    "Content": "Treść",
    "Enter information text for form visitors.": "Wpisz tekst informacyjny dla odwiedzających formularz.",
    "Optional heading (label)": "Opcjonalny nagłówek (etykieta)",
    "Information text shown to visitors. This field is display-only and is not saved in submissions.": "Tekst informacyjny wyświetlany odwiedzającym. To pole służy tylko do prezentacji i nie jest zapisywane w zgłoszeniach.",
    "Optional heading displayed above the content when “Hide label” is off.": "Opcjonalny nagłówek nad treścią, gdy opcja „Ukryj etykietę” jest wyłączona.",
    "Do not save submission to database": "Nie zapisuj zgłoszenia w bazie",
    "When enabled, submissions are not stored in the submissions list. Emails are still sent if configured below.": "Po włączeniu zgłoszenia nie trafiają na listę w panelu. E-maile nadal są wysyłane, jeśli skonfigurowano je poniżej.",
    "Redirect page": "Strona przekierowania",
    "If a custom URL is set, it is used instead of the selected page below.": "Jeśli ustawiono własny URL, ma on pierwszeństwo przed wybraną stroną poniżej.",
    "WordPress page to redirect to when no custom URL is set.": "Strona WordPress używana do przekierowania, gdy nie ustawiono własnego URL.",
    "Search pages…": "Szukaj stron…",
    "Select a field, row, or column to edit its settings.": "Wybierz pole, wiersz lub kolumnę, aby edytować ustawienia.",
    "Column settings": "Ustawienia kolumny",
    "Additional CSS class added to the column wrapper on the frontend.": "Dodatkowa klasa CSS na wrapperze kolumny na froncie.",
    "Custom HTML id on the column wrapper. Leave empty for no id.": "Własne HTML id wrappera kolumny. Zostaw puste, jeśli nie potrzebne.",
    "Row settings": "Ustawienia wiersza",
    "Live validation": "Walidowanie w locie",
    "When enabled, the submit button is disabled and faded until all required fields are valid.": "Po włączeniu przycisk wysyłki jest wyłączony i przyciemniony, dopóki wymagane pola nie są poprawnie wypełnione.",
    "Additional CSS class added to the row wrapper on the frontend.": "Dodatkowa klasa CSS na wrapperze wiersza na froncie.",
    "Custom HTML id on the row wrapper. Leave empty for no id.": "Własne HTML id wrappera wiersza. Zostaw puste, jeśli nie potrzebne.",
    "Documentation": "Dokumentacja",
    "Displaying the form": "Wyświetlanie formularza",
    "Paste a shortcode into a page, post, widget, or template. This form’s ready-to-copy shortcodes are also available under Settings → Shortcodes.": "Wklej shortcode na stronie, we wpisie, w widgecie lub szablonie. Gotowe shortcode’y tego formularza są też w Ustawienia → Shortcode’y.",
    "Shortcode attributes": "Atrybuty shortcode",
    "Attribute": "Atrybut",
    "Description": "Opis",
    "Form ID (required).": "ID formularza (wymagane).",
    "Display mode: inline, button, or trigger.": "Tryb wyświetlania: inline, button lub trigger.",
    "Modal button label when display=\"button\".": "Etykieta przycisku modala przy display=\"button\".",
    "CSS classes for the modal button.": "Klasy CSS przycisku modala.",
    "CSS selector of the element that opens the modal when display=\"trigger\".": "Selektor CSS elementu otwierającego modal przy display=\"trigger\".",
    "Example HTML trigger:": "Przykład HTML triggera:",
    "Contact us": "Skontaktuj się",
    "Email merge tags": "Tagi scalające e-mail",
    "Use these placeholders in admin/user email subject and body (Email tab).": "Użyj tych placeholderów w temacie i treści e-maili admina/użytkownika (zakładka E-mail).",
    "Frontend JavaScript event": "Zdarzenie JavaScript na froncie",
    "After a successful AJAX submit, the form element triggers a jQuery event you can listen to:": "Po udanym wysłaniu AJAX element formularza wywołuje zdarzenie jQuery, którego możesz nasłuchiwać:",
    "Developer hooks": "Hooki dla deweloperów",
    "Use WordPress actions and filters to extend Fast Forms — for example CRM integrations (Salesmanago, Mailchimp), webhooks, or custom processing after submit.": "Użyj akcji i filtrów WordPress, aby rozszerzyć Fast Forms — np. integracje CRM (Salesmanago, Mailchimp), webhooki lub własną logikę po wysyłce.",
    "After successful submit": "Po udanym wysłaniu",
    "When “Do not save submission to database” is enabled, $entry_id is 0 but emails and this hook still run.": "Gdy włączone jest „Nie zapisuj zgłoszenia w bazie”, $entry_id wynosi 0, ale e-maile i ten hook nadal działają.",
    "Other filters": "Inne filtry",
    "Enable/disable the honeypot field.": "Włącz/wyłącz pole honeypot.",
    "Rate limit per form and IP.": "Limit wysyłek na formularz i IP.",
    "Maximum upload file size in KB.": "Maksymalny rozmiar pliku w KB.",
    "Allowed MIME types for file fields.": "Dozwolone typy MIME dla pól plików.",
    "REST API submit endpoint": "Endpoint REST API wysyłki",
    "The frontend submits forms via REST (same endpoint for custom integrations):": "Frontend wysyła formularze przez REST (ten sam endpoint dla własnych integracji):",
    "Submissions and SEO": "Zgłoszenia a SEO",
    "Form submissions are stored as private entries. They are not public, not indexed by search engines, and return 404 if accessed directly on the front end. Only logged-in users with permission can view them in the admin.": "Zgłoszenia są zapisywane jako wpisy prywatne. Nie są publiczne, nie są indeksowane przez wyszukiwarki i zwracają 404 przy bezpośrednim wejściu na front. Tylko zalogowani użytkownicy z uprawnieniami widzą je w panelu.",
    "Planned integrations": "Planowane integracje",
    "Built-in connectors (e.g. Salesmanago, webhooks UI) may be added in future versions. Until then, use the ff_form_submitted action to send data to external services.": "Wbudowane konektory (np. Salesmanago, UI webhooków) mogą pojawić się w przyszłych wersjach. Na razie użyj akcji ff_form_submitted, aby wysłać dane do zewnętrznych usług.",
    "Minimum time before the same visitor can submit again.": "Minimalny czas, zanim ten sam odwiedzający będzie mógł wysłać formularz ponownie.",
    "Copy a shortcode and paste it into a page or post to display the form.": "Skopiuj shortcode i wklej go na stronie lub we wpisie, aby wyświetlić formularz.",
    "Reply-To": "Reply-To",
    "Fingerprint": "Odcisk przeglądarki",
    "Form layout is saved when you click “Save form” or “Update”.": "Układ formularza zapisuje się po kliknięciu „Zapisz formularz” lub „Aktualizuj”.",
    "Google reCAPTCHA v3": "Google reCAPTCHA v3",
    "Site key": "Klucz witryny",
    "Secret key": "Klucz tajny",
    "Action name": "Nazwa akcji",
    "Example: %s": "Przykład: %s",
    "Anti-spam protection": "Ochrona antyspamowa",
    "Captcha provider": "Dostawca captcha",
    "Disabled": "Wyłączone",
    "Cloudflare Turnstile": "Cloudflare Turnstile",
    "Get keys from Google reCAPTCHA Admin.": "Klucze uzyskasz w Google reCAPTCHA Admin.",
    "Default: fast_forms_submit": "Domyślnie: fast_forms_submit",
    "Minimum score": "Minimalny wynik",
    "Recommended: 0.5. Lower score blocks more submissions, higher allows more spam.": "Zalecane: 0,5. Niższy wynik blokuje więcej wysyłek, wyższy przepuszcza więcej spamu.",
    "Get keys from Cloudflare dashboard → Turnstile.": "Klucze uzyskasz w panelu Cloudflare → Turnstile.",
    "File uploads": "Przesyłanie plików",
    "Configure where uploaded files from form fields are stored. Path is relative to the WordPress uploads directory.": "Skonfiguruj, gdzie zapisywane są pliki z pól formularza. Ścieżka jest względem katalogu uploads WordPress.",
    "Default upload path": "Domyślna ścieżka uploadu",
    "Available tags:": "Dostępne tagi:",
    "Uninstall": "Deinstalacja",
    "Delete data": "Usuń dane",
    "Remove forms, entries, and uploaded files when uninstalling the plugin": "Usuń formularze, zgłoszenia i pliki przy odinstalowaniu wtyczki",
    "Captcha is disabled. Honeypot and hourly rate limiting are active, but enabling reCAPTCHA or Turnstile is strongly recommended on production sites.": "Captcha jest wyłączona. Honeypot i limit godzinowy są aktywne, ale na produkcji zalecamy włączenie reCAPTCHA lub Turnstile.",
}


def unescape_po(value: str) -> str:
    return value.replace("\\n", "\n").replace("\\t", "\t").replace('\\"', '"').replace("\\\\", "\\")


def escape_po(value: str) -> str:
    return value.replace("\\", "\\\\").replace('"', '\\"').replace("\n", "\\n")


def parse_po_blocks(content: str) -> list[dict[str, str]]:
    blocks: list[dict[str, str]] = []
    current: dict[str, str] = {"comments": "", "msgid": "", "msgstr": ""}

    for line in content.splitlines():
        if line.startswith("#"):
            if current.get("msgid") or current.get("msgctxt"):
                blocks.append(current)
                current = {"comments": "", "msgid": "", "msgstr": ""}
            current["comments"] += line + "\n"
            continue

        if line.startswith("msgctxt "):
            if current.get("msgid"):
                blocks.append(current)
                current = {"comments": "", "msgid": "", "msgstr": ""}
            current["msgctxt"] = unescape_po(line[8:-1])
            continue

        if line.startswith("msgid "):
            if current.get("msgid"):
                blocks.append(current)
                comments = current.get("comments", "")
                current = {"comments": comments, "msgid": "", "msgstr": ""}
            current["msgid"] = unescape_po(line[7:-1])
            continue

        if line.startswith("msgstr "):
            current["msgstr"] = unescape_po(line[8:-1])
            continue

        if line.startswith('"'):
            chunk = unescape_po(line[1:-1])
            if "msgstr" in current and current.get("_in_msgstr"):
                current["msgstr"] += chunk
            elif current.get("msgid") is not None and current.get("_in_msgid"):
                current["msgid"] += chunk
            continue

        if line.strip() == "":
            if current.get("msgid") is not None:
                blocks.append(current)
            current = {"comments": "", "msgid": "", "msgstr": ""}

    if current.get("msgid") is not None:
        blocks.append(current)

    return blocks


def read_po_string_from_lines(lines: list[str], start: int) -> tuple[str, int]:
    first = lines[start]
    if first.startswith("msgid "):
        value = unescape_po(first[7:-1])
        i = start + 1
        key = "msgid"
    elif first.startswith("msgstr "):
        value = unescape_po(first[8:-1])
        i = start + 1
        key = "msgstr"
    else:
        return "", start

    while i < len(lines) and lines[i].startswith('"'):
        value += unescape_po(lines[i][1:-1])
        i += 1

    return value, i


def load_entries(path: Path) -> dict[str, str]:
    lines = path.read_text(encoding="utf-8").splitlines()
    entries: dict[str, str] = {}
    i = 0
    while i < len(lines):
        if lines[i].startswith("msgid "):
            msgid, i = read_po_string_from_lines(lines, i)
            if i < len(lines) and lines[i].startswith("msgstr "):
                msgstr, i = read_po_string_from_lines(lines, i)
                if msgid and msgstr:
                    entries[msgid] = msgstr
            continue
        i += 1
    return entries


def format_msg(kind: str, value: str) -> list[str]:
    escaped = escape_po(value)
    if len(escaped) <= 76:
        return [f'{kind} "{escaped}"' if value else f'{kind} ""']
    lines = [f'{kind} ""']
    for idx in range(0, len(escaped), 76):
        lines.append(f'"{escaped[idx : idx + 76]}"')
    return lines


def main() -> None:
    backup = load_entries(BAK_PATH) if BAK_PATH.exists() else {}
    translations = {**backup, **PL}

    lines = PO_PATH.read_text(encoding="utf-8").splitlines()
    out: list[str] = []
    missing: list[str] = []
    skip = {"Fast Forms", "Weblo", "https://weblo.pl/", ""}
    i = 0

    while i < len(lines):
        line = lines[i]

        if line.startswith("msgid "):
            msgid, next_i = read_po_string_from_lines(lines, i)
            i = next_i

            if i >= len(lines) or not lines[i].startswith("msgstr "):
                out.extend(format_msg("msgid", msgid))
                continue

            msgstr, next_i = read_po_string_from_lines(lines, i)
            i = next_i

            if msgid and not msgstr:
                msgstr = translations.get(msgid, "")

            if msgid and not msgstr and msgid not in skip:
                missing.append(msgid)

            out.extend(format_msg("msgid", msgid))
            out.extend(format_msg("msgstr", msgstr))
            continue

        out.append(line)
        i += 1

    PO_PATH.write_text("\n".join(out) + "\n", encoding="utf-8")
    print(f"Missing translations: {len(missing)}")
    for item in missing[:30]:
        print(f"  - {item}")


if __name__ == "__main__":
    main()
