# Fast Forms — katalog logów

Plik `fast-forms-debug.log` jest zapisywany tylko gdy w `wp-config.php` ustawiono:

```php
define( 'FF_DEVELOPER_DEBUG', true );
```

## Ochrona HTTP

- **Apache:** plik `.htaccess` w tym katalogu blokuje dostęp (`Require all denied`).
- **nginx:** dodaj regułę w konfiguracji serwera:

```nginx
location ~* /wp-content/plugins/fast-forms/logs/ {
    deny all;
    return 404;
}
```

Nie commituj plików `.log` do repozytorium.

## Pliki uploadów formularzy

Pliki z formularzy są zapisywane w `wp-content/uploads/` (domyślnie pod `fast-forms/`) i **nie powinny** być dostępne bezpośrednio z internetu.

- **Apache:** wtyczka tworzy `.htaccess` z `Require all denied` w katalogach uploadów.
- **nginx:** dodaj regułę (dostosuj ścieżkę, jeśli używasz własnego wzorca uploadu):

```nginx
location ~* /wp-content/uploads/fast-forms/ {
    deny all;
    return 404;
}
```

Pobieranie plików odbywa się wyłącznie przez panel WordPress (link chroniony nonce + uprawnienie `manage_fast_forms`).
