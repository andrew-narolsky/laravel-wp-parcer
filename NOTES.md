# Project Notes

## Що це

Laravel 13.8 адмін-панель для парсингу WordPress сайтів по REST API.

## Запуск

```bash
cd docker && make up       # запустити контейнери
cd docker && make exec     # зайти в workspace (bash)
```

Artisan і composer — тільки всередині контейнера:
```bash
docker compose -f docker/docker-compose.yml exec --user=laradock workspace php artisan ...
```

## Структура

```
app/Http/Controllers/Admin/
    DashboardController.php   — /admin
    SiteController.php        — /admin/sites (CRUD + тест підключення)
    ParseController.php       — /admin/parse

app/Models/
    Site.php                  — name, url, login, password, is_active

resources/views/admin/
    sites/index|create|edit.blade.php
    parse/index.blade.php
```

## Як працює парсинг

`ParseController::parse()`:
1. Отримує `site_id` + `url`
2. Витягує slug з URL
3. Шукає `/wp-json/wp/v2/pages?slug={slug}` → якщо немає, `/wp-json/wp/v2/posts?slug={slug}`
4. Basic Auth: `Http::withBasicAuth($site->login, $site->password)`
5. Повертає: `id, slug, link, title, content, excerpt, date, modified, status, type`

## TODO

### Вставка лінки і збереження назад

1. Fetch — вже є
2. Modify — вставити лінку в `content.rendered`. **Уточнити куди:** початок, кінець, після першого `</p>`, або замінити anchor text
3. Push back — `PUT /wp-json/wp/v2/pages/{id}` або `posts/{id}` з полем `content`. Авторизація та ж. Тип (post/page) вже визначається при парсингу.

### Інше
- Зберігання результатів парсингу в БД
- Масовий парсинг кількох сторінок
