# Project Notes

## Що це

Laravel 13.8 / PHP 8.3 адмін-панель для масового публікування посилань на WordPress сайти через REST API (Basic Auth).
Основний флоу: імпорт сайтів і лінків з CSV → черга → публікація.

## Запуск

```bash
cd docker && make up       # запустити контейнери
cd docker && make exec     # зайти в workspace (bash)
```

Artisan і composer — тільки всередині контейнера:
```bash
docker compose -f docker/docker-compose.yml exec --user=laradock workspace php artisan ...
```

Контейнери: `cms-workspace` (PHP-FPM + supervisord queue worker), `cms-nginx`, `cms-mariadb`, `cms-memcached`.

## Структура

```
app/Http/Controllers/Admin/
    DashboardController.php     — GET /admin
    SiteController.php          — /admin/sites (CRUD + CSV import)
    LinkController.php          — /admin/links (CRUD + CSV import + publish)

app/Models/
    Site.php    — name, url, login, password, is_active
    Link.php    — site_id, title, url, wp_url, anchor, text, type, is_active

app/Jobs/
    CheckSiteConnectionJob.php   — тест WP Basic Auth після збереження сайту
    ImportSitesFromCsvJob.php    — масовий upsert сайтів
    ImportLinksFromCsvJob.php    — масовий upsert лінків + авто-диспатч PublishLinkJob
    PublishLinkJob.php           — публікує лінк на WP, зберігає wp_url

app/Services/
    WordPressClient.php          — testConnection, publishPost, updateFrontPage

resources/views/admin/
    sites/index|create|edit.blade.php
    links/index|create|edit.blade.php
    dashboard.blade.php
    components/searchable-select.blade.php   — кастомний select з пошуком
```

## Маршрути

| Маршрут | Дія |
|---|---|
| GET /admin | Dashboard |
| GET/POST /admin/sites | CRUD сайтів |
| POST /admin/sites/import | Імпорт сайтів з CSV |
| GET/POST /admin/links | CRUD лінків |
| POST /admin/links/import | Імпорт лінків з CSV |
| POST /admin/links/{link}/publish | Поставити лінк у чергу на публікацію |

## Як працює публікація

**Link.type = `post`** — `WordPressClient::publishPost()`:
- POST `/wp-json/wp/v2/posts` з `title`, `content`, `status=publish`
- Створює новий пост

**Link.type = `homepage`** — `WordPressClient::updateFrontPage()`:
- GET `/wp-json/wp/v2/settings` → отримує `page_on_front`
- GET `/wp-json/wp/v2/pages/{id}` → читає існуючий контент
- POST `/wp-json/wp/v2/pages/{id}` → дописує контент в кінець

Після публікації `PublishLinkJob` зберігає `wp_url` і виставляє `is_active=true`.

## CSV-імпорт лінків

Очікувані колонки: `Referring page URL, Destination, Target URL, Anchor, Content`

- `Referring page URL` → шукає `site_id` по полю `url` таблиці `sites`
- `Destination=home` → `type=homepage`, решта → `type=post`
- Підставляє `<a href="Target URL">Anchor</a>` в Content (перше входження), обгортає в `<p>`
- upsert по `(site_id, url, anchor)`, chunk по 500
- Одразу диспатчить `PublishLinkJob` для кожного нового/оновленого лінка

## БД

Таблиця `sites`: унікальний `url`.  
Таблиця `links`: унікальний індекс `(site_id, url, anchor)`, поле `wp_url` — URL опублікованого поста/сторінки на WP.

## TODO

- Зберігати результат `CheckSiteConnectionJob` в БД (колонка `connection_status` або `last_checked_at`)
- Фільтрація лінків за сайтом / статусом / типом
- Обробка failed jobs (retry, повідомлення)
- Bulk publish / bulk delete лінків через UI