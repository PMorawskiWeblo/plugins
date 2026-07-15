#!/usr/bin/env python3
"""Convert Polish msgids to English source strings in fast-forms plugin."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

REPLACEMENTS = {
    # Frontend Assets
    "'Zamknij'": "'Close'",
    "'Wysyłanie…'": "'Sending…'",
    "'Uzupełnij wymagane pola.'": "'Please fill in the required fields.'",
    "'To pole jest wymagane.'": "'This field is required.'",
    "'Niedozwolony typ pliku.'": "'This file type is not allowed.'",
    "'Plik jest zbyt duży.'": "'The file is too large.'",
    "'Maksymalny rozmiar:'": "'Maximum size:'",
    "'Wystąpił błąd podczas wysyłki. Spróbuj ponownie.'": "'An error occurred while submitting. Please try again.'",
    "'Formularz został wysłany. Dziękujemy!'": "'The form has been submitted. Thank you!'",
    # Validator
    "'Podaj prawidłowy adres e-mail.'": "'Please enter a valid email address.'",
    "'Podaj prawidłowy adres URL.'": "'Please enter a valid URL.'",
    "'Wartość jest zbyt krótka.'": "'The value is too short.'",
    "'Wartość jest zbyt długa.'": "'The value is too long.'",
    "'Podaj prawidłową liczbę.'": "'Please enter a valid number.'",
    "'Podaj prawidłową ocenę.'": "'Please enter a valid rating.'",
    "'Wartość jest zbyt mała.'": "'The value is too small.'",
    "'Wartość jest zbyt duża.'": "'The value is too large.'",
    "'Wybrano nieprawidłową opcję.'": "'An invalid option was selected.'",
    "'Można przesłać tylko jeden plik.'": "'Only one file can be uploaded.'",
    "'Można przesłać maksymalnie %d plik(ów).'": "'You can upload at most %d file(s).'",
    "'Błąd podczas przesyłania pliku.'": "'An error occurred while uploading the file.'",
    # EntryPresenter
    "'Formularz'": "'Form'",
    "'ID formularza'": "'Form ID'",
    "'Imię / nazwa'": "'Name'",
    "'E-mail'": "'Email'",
    "'Telefon'": "'Phone'",
    "'Data wysłania'": "'Submitted at'",
    "'Score reCAPTCHA'": "'reCAPTCHA score'",
    "'Wersja schemy'": "'Schema version'",
    "'Tak'": "'Yes'",
    "'Nie'": "'No'",
    "'Nowe'": "'New'",
    "'Odczytane'": "'Read'",
    "'Zarchiwizowane'": "'Archived'",
    # EntrySaver
    "'Zgłoszenie — %s'": "'Submission — %s'",
    "'Zgłoszenie #%1$d — %2$s'": "'Submission #%1$d — %2$s'",
    "'Nie udało się zapisać pliku: %s'": "'Could not save file: %s'",
    # RestApi
    "'Nie udało się zapisać schemy formularza.'": "'Could not save the form schema.'",
    "'Formularz został zapisany.'": "'The form has been saved.'",
    # CsvExporter
    "'Nieprawidłowy formularz.'": "'Invalid form.'",
    "'Nie udało się wygenerować pliku CSV.'": "'Could not generate the CSV file.'",
    "'ID zgłoszenia'": "'Entry ID'",
    # EntryFileDownload
    "'Brak uprawnień.'": "'You do not have permission.'",
    "'Brak dostępu'": "'Access denied'",
    "'Nieprawidłowe żądanie.'": "'Invalid request.'",
    "'Błąd'": "'Error'",
    "'Plik nie jest dostępny.'": "'The file is not available.'",
    "'Nie znaleziono'": "'Not found'",
    # FormSettingsStorage
    "'Nowe zgłoszenie: {form:title}'": "'New submission: {form:title}'",
    "'Potwierdzenie zgłoszenia'": "'Submission confirmation'",
    "'Dziękujemy za przesłanie formularza.'": "'Thank you for submitting the form.'",
    # EntryAdmin
    "'Zgłoszenie'": "'Submission'",
    # SubmitRestApi
    "'Formularz jest niedostępny.'": "'The form is unavailable.'",
    "'Sesja wygasła. Odśwież stronę i spróbuj ponownie.'": "'Your session has expired. Refresh the page and try again.'",
    "'Popraw błędy w formularzu.'": "'Please correct the errors in the form.'",
    "'Nie udało się zapisać zgłoszenia.'": "'Could not save the submission.'",
    # SubmitLimiter
    "'Ten formularz został już wysłany.'": "'This form has already been submitted.'",
    "'Możesz wysłać ten formularz ponownie za chwilę.'": "'You can submit this form again shortly.'",
    "'Zbyt wiele prób wysłania formularza. Spróbuj ponownie później.'": "'Too many submission attempts. Please try again later.'",
    # Menu
    "'Formularze'": "'Forms'",
    "'Dodaj nowy formularz'": "'Add new form'",
    "'Przesłane formularze'": "'Form submissions'",
    "'Ustawienia globalne'": "'Global settings'",
    "'Manager formularzy'": "'Form manager'",
    "'Nie masz uprawnień do wyświetlenia tej strony.'": "'You do not have permission to view this page.'",
    # PluginLinks
    "'Ustawienia'": "'Settings'",
    # EntryListFilters
    "'Wszystkie formularze'": "'All forms'",
    "'Wszystkie statusy'": "'All statuses'",
    "'Szukaj'": "'Search'",
    "'Szukaj zgłoszeń…'": "'Search submissions…'",
    "'Od'": "'From'",
    "'Do'": "'To'",
    # RecaptchaVerifier
    "'Weryfikacja antyspamowa nie powiodła się. Odśwież stronę i spróbuj ponownie.'": "'Anti-spam verification failed. Refresh the page and try again.'",
    "'Nie udało się zweryfikować reCAPTCHA. Spróbuj ponownie.'": "'Could not verify reCAPTCHA. Please try again.'",
    "'Weryfikacja antyspamowa nie powiodła się.'": "'Anti-spam verification failed.'",
    "'Twoje zgłoszenie zostało zablokowane jako podejrzane. Spróbuj ponownie później.'": "'Your submission was blocked as suspicious. Please try again later.'",
    # FormPostType
    "'Dodaj nowy'": "'Add new'",
    "'Edytuj formularz'": "'Edit form'",
    "'Nowy formularz'": "'New form'",
    "'Zobacz formularz'": "'View form'",
    "'Szukaj formularzy'": "'Search forms'",
    "'Nie znaleziono formularzy.'": "'No forms found.'",
    "'Nie znaleziono formularzy w koszu.'": "'No forms found in trash.'",
    "'Wszystkie formularze'": "'All forms'",
    "'Nazwa formularza'": "'Form name'",
    # EntryPostType
    "'Dodaj nowe zgłoszenie'": "'Add new submission'",
    "'Zobacz zgłoszenie'": "'View submission'",
    "'Nowe zgłoszenie'": "'New submission'",
    "'Szukaj zgłoszeń'": "'Search submissions'",
    "'Nie znaleziono zgłoszeń.'": "'No submissions found.'",
    "'Nie znaleziono zgłoszeń w koszu.'": "'No submissions found in trash.'",
    "'Wszystkie zgłoszenia'": "'All submissions'",
    # entry-detail.php
    "'Status zgłoszenia'": "'Submission status'",
    "'Zapisz wpis, aby zaktualizować status (przycisk „Aktualizuj” powyżej).'": "'Save the entry to update the status (click “Update” above).'",
    "'Podsumowanie'": "'Summary'",
    "'Odpowiedzi'": "'Answers'",
    "'Brak zapisanych odpowiedzi.'": "'No saved answers.'",
    "'Pole'": "'Field'",
    "'Odpowiedź'": "'Answer'",
    # manager.php
    "'Wybierz formularz przed eksportem.'": "'Select a form before exporting.'",
    "'Eksport zgłoszeń do CSV'": "'Export submissions to CSV'",
    "'Pobierz zgłoszenia wybranego formularza w formacie CSV (UTF-8, separator średnik).'": "'Download submissions for the selected form as CSV (UTF-8, semicolon separator).'",
    "'wymagane'": "'required'",
    "'— Wybierz formularz —'": "'— Select form —'",
    "'Data od'": "'Date from'",
    "'Data do'": "'Date to'",
    "'Eksportuj CSV'": "'Export CSV'",
}

JS_REPLACEMENTS = {
    "'Plik jest zbyt duży.'": "'The file is too large.'",
    "'Wybierz co najmniej '": "'Select at least '",
    "'opcję.'": "'option.'",
    "'opcji.'": "'options.'",
    "'Wybierz maksymalnie '": "'Select at most '",
    "'Wybrano zbyt wiele plików.'": "'Too many files selected.'",
    "'Wysyłanie…'": "'Sending…'",
    "'Błąd wysyłki.'": "'Submission error.'",
    "'Popraw błędy w formularzu.'": "'Please correct the errors in the form.'",
    "'Wystąpił błąd podczas wysyłki.'": "'An error occurred while submitting.'",
    "'Błąd weryfikacji antyspamowej.'": "'Anti-spam verification error.'",
    "'Formularz został wysłany.'": "'The form has been submitted.'",
    "'Błąd konfiguracji formularza.'": "'Form configuration error.'",
    "'Uzupełnij wymagane pola.'": "'Please fill in the required fields.'",
    "'To pole jest wymagane.'": "'This field is required.'",
    "'Tekst przycisku'": "'Submit button text'",
    "'Tekst ładowania'": "'Loading text'",
}


def apply_replacements(content: str, mapping: dict[str, str]) -> str:
    for old, new in mapping.items():
        content = content.replace(old, new)
    return content


def main() -> None:
    php_dirs = [ROOT / "src", ROOT / "templates"]
    for directory in php_dirs:
        for path in directory.rglob("*.php"):
            original = path.read_text(encoding="utf-8")
            updated = apply_replacements(original, REPLACEMENTS)
            if updated != original:
                path.write_text(updated, encoding="utf-8")
                print(f"updated {path.relative_to(ROOT)}")

    for rel in [
        "assets/public/js/form.js",
        "assets/admin/js/form-builder.js",
    ]:
        path = ROOT / rel
        original = path.read_text(encoding="utf-8")
        updated = apply_replacements(original, JS_REPLACEMENTS)
        if updated != original:
            path.write_text(updated, encoding="utf-8")
            print(f"updated {rel}")


if __name__ == "__main__":
    main()
