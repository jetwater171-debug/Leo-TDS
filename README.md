[English version](README.en.md)

```
                            Yellow TDS
    _            __     __  _ _             __          __  _
   | |           \ \   / / | | |            \ \        / / | |
   | |__  _   _   \ \_/ /__| | | _____      _\ \  /\  / /__| |__
   | '_ \| | | |   \   / _ \ | |/ _ \ \ /\ / /\ \/  \/ / _ \ '_ \
   | |_) | |_| |    | |  __/ | | (_) \ V  V /  \  /\  /  __/ |_) |
   |_.__/ \__, |    |_|\___|_|_|\___/ \_/\_/    \/  \/ \___|_.__/
           __/ |
          |___/             https://yellowweb.top

If you like this script, PLEASE DONATE!
USDT TRC20: TKeNEVndhPSKXuYmpEwF4fVtWUvfCnWmra
Bitcoin: bc1qqv99jasckntqnk0pkjnrjtpwu0yurm0qd0gnqv
Ethereum: 0xBC118D3FDE78eE393A154C29A4545c575506ad6B
```

# Yellow TDS - Документация (RU)

## Что это

Yellow TDS - PHP-приложение для маршрутизации трафика по правилам кампании: `white`/`black`/`trafficback`, с логированием кликов, лидами, постбэками и UI для управления.

## Актуальные требования

- PHP `>= 8.2`
- Расширения PHP: `curl`, `sqlite3`
- Рабочий HTTPS на домене
- Права записи для папки проекта (создаются/обновляются `db`, `logs`, `cache`, сессии)

Проверки выполняются в `debug.php` при каждом запросе.

## Быстрый старт

1. Разверните содержимое папки `fromfolder/` на хостинге.
2. Откройте `settings.php` и минимум задайте:
- `adminPassword`
- `adminDomain` (опционально)
- `dbConnection` (имя SQLite-файла в `db/`)
- `debug` (`false` для production)
- `maxMindKey` (если нужен автоапдейт GeoLite2)
3. Откройте `https://your-domain/admin/` и авторизуйтесь.
4. Создайте кампанию, заполните домены, white/black, фильтры, постбэки, сохраните.

## Основной поток

- `index.php` -> `tds.php` -> выбор действия (`white`, `black`, `trafficback`)
- `core.php` собирает параметры клика: IP, GEO, ISP, OS, browser, UA, query params
- `next.php` и `send.php` обрабатывают переходы по шагам воронки и отправку форм
- `postback.php` принимает S2S-статусы и обновляет лиды

## Где хранятся данные

SQLite в `db/<dbConnection>`.

Основные таблицы:
- `campaigns` - кампании и их JSON-настройки
- `clicks` - разрешенные клики и лиды
- `blocked` - отфильтрованные клики
- `trafficback` - клики без подходящей кампании
- `common` - общие настройки UI

Схема: `db/db.sql`.

## Админка

Основные страницы:
- `admin/index.php` - список кампаний
- `admin/campsettings.php` - настройки кампании
- `admin/clicks.php` - allowed/blocked/leads/trafficback
- `admin/statistics.php` - агрегированные таблицы

Ключевые блоки настройки кампании:
- Domains
- Safe page (white): `folder`, `redirect`, `curl`, `error`
- Money page (black): multi-step flows (`steps[]`, folder/redirect на каждом шаге)
- Filters (query-builder, AND/OR группы)
- Scripts (backfix, replace transit/landing, lazy images)
- Statistics (timezone, tables/columns/grouping)
- Postbacks (входящий + исходящий S2S)
- API key для `phpconnect.php`

## Интеграции

### JS connect

Подключение скрипта:

```html
<script src="https://your-domain/js/index.php"></script>
```

`js/index.php` может:
- вернуть JS с подменой контента
- показать iframe
- отдать meta-redirect
- обработать JS-check сценарий

### PHP API

Эндпойнт: `phpconnect.php`

Ограничения:
- только `POST`
- `User-Agent` должен содержать `YellowTDS`
- тело: JSON с `api_key` и параметрами (`tds_ua`, `tds_ref`, `tds_ip`, ...)

Пример клиента есть в `phpclient.php`.

### Postback

Эндпойнт: `postback.php`

Обязательные параметры:
- `clickid`
- `status`
- `payout`

Опционально:
- `currency` (по умолчанию `USD`, есть конвертация в `currency.php`)

## UTP (Universal Thank You Page)

Если `settings.php -> useUTP = true`, после лида используется `thankyou/index.php`.

UTP:
- выбирает/генерирует шаблон
- переводит текст через `thankyou/translator.php`
- кеширует страницы в `thankyou/cache/`
- подставляет макросы (`{NAME}`, `{PHONE}`, `{CLICKID}`)

## Логи и обслуживание

Логи пишутся в `logs/<subdir>/`.

Часто используемые:
- `logs/error`
- `logs/login`
- `logs/postback`
- `logs/trace` (при `debug=true`)

Обновление GeoLite2: `bases/update.php` (использует `maxMindKey`).

## Важно по безопасности

- Сразу смените `adminPassword`.
- Ограничьте `adminDomain`, если админка должна открываться только с одного домена.
- Держите `debug=false` на бою.
- Не храните репозиторий с рабочими ключами/паролями в публичном доступе.
- Если используете nginx, закройте прямой доступ к SQLite-файлу.

## Примечания по текущей версии

- Источник правды для кампаний - UI + SQLite, не ручной `settings.php` кампаний.
- Автоапдейтер в `admin/autoupdate.php` частично заготовлен (копирование файлов сейчас отключено TODO).
- В некоторых dev-конфигах в репозитории есть тестовые папки (`black`, `white`, `student`) и они не обязательны для production.

