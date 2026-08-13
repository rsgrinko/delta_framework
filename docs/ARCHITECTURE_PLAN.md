# План архитектурного рефакторинга Delta Framework

**Статус:** утверждён к реализации
**Дата составления:** 2026-08-13
**Базовая ревизия:** `d6f8738`
**Выбранная стратегия:** Вариант B — собственное ядро по PSR-стандартам, эволюционно, по этапам

Документ описывает **что** и **в каком порядке** делать. Это план, а не отчёт о выполненной работе:
на момент составления ни один этап не начат.

---

## 1. Зачем этот рефакторинг

Delta позиционируется как **универсальный фреймворк**, но в текущем виде не является переиспользуемым:
поставить его на новый проект можно только копированием репозитория с последующим вырезанием чужой
предметной области (диалоги, профили, конкретная админка). Плюс ядро не поддаётся автотестированию —
единственный существующий тест `tests/UserTest.php` не тестирует ничего (`assertTrue(true)`), потому что
любое обращение к модели тянет за собой реальный коннект к MySQL и глобальное состояние.

Цель рефакторинга — сделать так, чтобы:

1. Ядро (`src/`) можно было подключить в новый проект через `composer require` и не тащить с ним демо-сайт.
2. Любой контроллер и сервис можно было покрыть юнит-тестом без БД, сессии и веб-сервера.
3. Сквозные вещи (авторизация, права, CSRF, DDoS, UTM, логирование ошибок) существовали ровно в одном
   месте, а не копипастой по файлам.
4. Секреты и настройки окружения жили вне кода и вне git.

---

## 2. Диагноз: 7 корневых дефектов

Ниже — первопричины, а не симптомы. Все подтверждены по коду с указанием файлов и строк.

### Д1. Нет абстракции HTTP — ни запроса, ни ответа

`Router::execute()` (`core/lib/Core/Models/Router.class.php:91`) вызывает `call_user_func_array($callback, $params)`
и на этом его работа заканчивается — возвращаемое значение экшена никуда не идёт. Поэтому контроллер обязан
сам работать с суперглобалями и сам формировать вывод: `$_REQUEST`/`$_FILES`/`$_SESSION` напрямую
(`App.class.php:181-207`, `412-414`, `436-446`), `header('Location: …')` россыпью (12 вызовов в `App.class.php`),
`echo` + `die()` в `outputJson()` (`App.class.php:256-261`).

Следствия: экшен нельзя вызвать из теста (он пишет в вывод и убивает процесс), нельзя вклиниться middleware,
нельзя единообразно управлять заголовками/буферизацией/кодами ответа, нельзя переиспользовать логику экшена
в CLI или в обработчике очереди.

**Это первопричина минимум половины остальных дефектов.**

### Д2. Глобальное состояние вместо явных зависимостей

- `global $USER, $twig` — в 14 методах `App.class.php` (например `:41`, `:79`, `:118`, `:225`).
- `DataBase::getInstance()` (`Database/DataBase.php:58-64`) — статический синглтон без сеттера; конструктор
  жёстко читает константы `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASSWORD` (`DataBase.php:48-49`).
- `Registry` — статическое key-value хранилище, через которое передаётся текущий маршрут.
- Модель `User` почти целиком статическая (`isAuthorized()`, `getCurrentUserId()`, `logout()`, `isOnline()`).

Граф зависимостей нигде не выражен; подменить реализацию нечем. Отсюда невозможность тестов.

### Д3. Конфигурация как константы, вычисляемые при загрузке

`core/config.php` — 236 строк `define()` под `if (!defined(...))`. Константу нельзя переопределить после
объявления, замокать в тесте, перечитать или провалидировать. Окружения различаются только фактом наличия
`config.local.php`. `Core\SystemConfig::getValue()` — обёртка над `constant()`, то есть фасад без реализации
за ним.

Отдельно: в `core/config.php` и `phinx.php` закоммичены похожие на настоящие секреты — пароль БД, токен
Telegram-бота, `CRYPTO_KEY`.

### Д4. Две несогласованные системы автозагрузки

`composer.json` объявляет PSR-4 `"Core\\": "core/lib/"`, но физические файлы называются `*.class.php`, поэтому
composer-автозагрузчик находит только 5 файлов из 50 (те, что без суффикса: `DataBase.php`,
`DatabaseDriverInterface.php`, `DatabaseException.php`, `Drivers/MySqlDriver.php`). Остальное подхватывает
второй автозагрузчик в `core/bootstrap.php:94-108`, матчащий по `str_starts_with($class, 'Core')` — то есть
по префиксу строки, а не по namespace (класс `Corexyz\Foo` тоже попадёт в эту ветку).

Следствие: IDE, Psalm и composer видят структуру проекта иначе, чем рантайм; единственная диагностика при
промахе — обычный `Class not found`.

Побочно: есть два разных класса `Cache` — `Core\Helpers\Cache` (`Helpers/Cache.class.php`, реально используется
в `bootstrap.php:111`) и `Core\Helpers\Cache\Cache` (`Helpers/Cache/Cache.class.php`, с драйверной
архитектурой, нигде не подключён). Namespace `Core\Helpers\Cache` при этом одновременно является и именем
класса, и именем пакета — прямой конфликт имён.

### Д5. Нет ни одной сквозной точки: ни middleware, ни обработчика ошибок, ни контроля доступа

- Проверка `$USER->isAdmin() === false` скопирована в каждый файл админки отдельно: `admin/cacheInfo.php:27`,
  `admin/users.php:29`, `admin/threads.php:27`, `admin/groups.php:27`, `admin/phpcmd.php:28` и т.д. При
  добавлении новой страницы её легко забыть.
- UTM-трекинг, сброс кэша, logout и DDoS-защита зашиты прямо в `core/bootstrap.php:113-144`, то есть
  выполняются до маршрутизации и вне её — на любом запросе, включая 404 и статику, проходящую через PHP.
- `set_exception_handler` / `set_error_handler` / `register_shutdown_function` не зарегистрированы нигде.
  Исключение из любого экшена всплывает в PHP-рантайм мимо `Log::logToFile()`, и поведение на проде зависит
  от `display_errors` в php.ini, а не от логики фреймворка.

### Д6. Слои смешаны внутри моделей

`Core\Models\User` (1040 строк) одновременно является: записью БД, репозиторием, сервисом регистрации
(`create()` — валидация уникальности, генерация verification-кода, назначение роли, отправка письма),
менеджером сессии и cookie (`authorize()`, `securityAuthorize()`, `logout()` пишут 6 ключей `$_SESSION` и три
`setcookie()`), и точкой отправки почты.

Транзакций в прикладном коде нет ни одной, хотя API есть (`DataBase::startTransaction()`). Самый рискованный
случай — `User::create()`: `INSERT` пользователя → отдельный `INSERT` роли → отправка письма; падение на
втором шаге оставляет в базе пользователя без роли.

### Д7. Ядро не отделено от приложения (ключевое для «универсального фреймворка»)

`Core\App` с диалогами, профилем и аватарками лежит **внутри** `core/lib/` — то есть внутри того, что
объявлено ядром. Туда же примешаны `templates/` и `assets/` конкретного сайта и `admin/` конкретной админки.
Границы «фреймворк / приложение» не существует ни на уровне каталогов, ни на уровне composer-пакетов.

Пока эта граница не проведена, универсальность остаётся декларацией.

### Д8 (обнаружено при составлении плана). Веб-консоль выполнения PHP существует в репозитории

`admin/phpcmd.php` — рабочая страница «Командная PHP строка», выполняющая произвольный код из `$_REQUEST['query']`
и умеющая сохранять сниппеты в `d_user_meta` (`admin/phpcmd.php:33-46`). Доступ ограничен проверкой
`$USER->isAdmin()` в самом файле (`:28`) — то есть той самой копипастой из Д5. Ранее в `CLAUDE.md` было
записано, что этого файла в репозитории нет; это утверждение неверно. Файл — фактический RCE-эндпоинт по
дизайну и должен быть удалён (см. Этап 0).

---

## 3. Целевая архитектура

```
delta/
├── src/                        ← ФРЕЙМВОРК (будущий composer-пакет delta/framework)
│   ├── Http/
│   │   ├── Request.php         иммутабельный объект запроса (query/post/files/cookies/headers/session)
│   │   ├── Response.php        status + headers + body, JsonResponse, RedirectResponse
│   │   ├── Kernel.php          handle(Request): Response
│   │   ├── UploadedFile.php    валидация MIME/расширения/размера в одном месте
│   │   └── Middleware/         SessionMiddleware, AuthMiddleware, AdminMiddleware,
│   │                           CsrfMiddleware, DDosMiddleware, UtmMiddleware
│   ├── Routing/
│   │   ├── Router.php          dispatch(Request): Response
│   │   ├── Route.php           паттерн, метод(ы), middleware, имя
│   │   └── RouteGroup.php      префикс + общий набор middleware (для /admin)
│   ├── Container/
│   │   ├── Container.php       PSR-11 (~150 строк, autowiring по рефлексии)
│   │   └── ServiceProvider.php регистрация сервисов пачками
│   ├── Config/
│   │   ├── Config.php          репозиторий значений: get('database.host')
│   │   └── Env.php             чтение .env
│   ├── Database/
│   │   ├── Connection.php      бывший DataBase, без синглтона
│   │   ├── QueryBuilder.php
│   │   └── Repository.php      базовый репозиторий
│   ├── Console/
│   │   ├── Command.php
│   │   └── Kernel.php          bin/delta cron:run, queue:work, migrate
│   ├── View/
│   │   └── TwigRenderer.php    Twig как сервис, а не global $twig
│   ├── Error/
│   │   └── ErrorHandler.php    set_exception_handler + shutdown, вывод по APP_ENV
│   └── Support/                Cache, Log, Mail, Files, Thumbs, Zip, Sanitize, Pagination
├── app/                        ← ПРИЛОЖЕНИЕ (демо-сайт на Delta)
│   ├── Controllers/            SiteController, AuthController, DialogController,
│   │                           ProfileController, UserController
│   ├── Controllers/Admin/      UserAdminController, ThreadAdminController, …
│   ├── Services/               AuthService, RegistrationService, DialogService, ProfileService
│   ├── Repositories/           UserRepository, DialogRepository, MessageRepository, …
│   ├── Entities/               User, Dialog, Message, Post, Role, File (только данные)
│   └── Jobs/                   задачи очереди (бывший MQTasks)
├── config/                     app.php, database.php, mail.php, telegram.php, cache.php
├── resources/views/            бывший templates/
├── routes/                     web.php, admin.php, api.php
├── public/                     ← DOCUMENT_ROOT
│   ├── index.php               единственная точка входа
│   ├── assets/
│   └── uploads/
├── bin/delta                   CLI-точка входа
├── tests/                      Unit/ + Integration/
├── .env / .env.example
└── db/                         миграции Phinx (как есть)
```

Ключевые контракты ядра:

```php
// src/Http/Kernel.php
public function handle(Request $request): Response
{
    return $this->pipeline
        ->send($request)
        ->through($this->globalMiddleware)   // Session → Utm → DDos → Auth → Csrf
        ->then(fn (Request $r) => $this->router->dispatch($r));
}

// public/index.php целиком
$app = require __DIR__ . '/../bootstrap/app.php';
$app->get(Kernel::class)->handle(Request::capture())->send();

// app/Controllers/ProfileController.php — экшен не знает ни про глобали, ни про die()
public function updatePassword(Request $request): Response
{
    $result = $this->auth->changePassword(
        $request->user(),
        $request->post('currentPassword'),
        $request->post('newPassword'),
        $request->post('confirmPassword'),
    );

    return Response::redirect('/profile')->withFlash($result->type(), $result->message());
}
```

**Обратная совместимость на время миграции.** `Router` продолжает принимать старые callable-строки вида
`'\Core\App::method'`. Адаптер оборачивает такой вызов в `ob_start()` и превращает вывод + перехваченные
`header()` в `Response`. Благодаря этому маршруты переводятся на новый стиль **по одному**, а не все сразу,
и сайт остаётся рабочим после каждого коммита.

---

## 4. Этапы

Каждый этап — отдельная ветка и отдельный мерж, самоценен и оставляет сайт рабочим.
Оценки — в человеко-днях, при работе одного человека.

### Этап 0. Гигиена и снятие шума — 0.5 дня

Подготовка почвы: убрать из репозитория то, что не должно мешать рефакторингу и не должно быть в docroot.

- [ ] **Удалить `admin/phpcmd.php`** — веб-консоль выполнения PHP (см. Д8). Проверить на всех окружениях,
      что файл не остался на диске вне git.
- [ ] Убрать из репозитория и добавить в `.gitignore`: `test.php`, `testMvc.php`, `testWait.php`, `webApp.php`,
      `meshtasticForward.php`, `composer.phar`, `.idea/`.
- [ ] Удалить мёртвый код: `ExternalServices/TelegramActions.class.php` + `ExternalServices/Telegram.class.php`
      (второй используется только первым), `ExternalServices/RequestOLD.class.php`,
      `templates/index_lll.twig`, `core/lib/Core/Helpers/Cache/` (неподключённый дубль `Cache`),
      маршруты `/test` в `routes.php` и метод `App::test()`.
- [ ] Вынести секреты из `core/config.php` и `phinx.php` в `config.local.php` (временно, до Этапа 2),
      **сменить утёкшие значения**: пароль БД, токен Telegram-бота, `CRYPTO_KEY`.
- [ ] `core/bootstrap.php:166-169`: `'debug' => DEBUG`, раскомментировать `'cache' => CACHE_DIR` под
      `DEBUG === false`.

**Критерий готовности:** `git status` чистый, сайт открывается, в админке нет пункта «Командная PHP строка».

### Этап 1. Единая автозагрузка — 0.5 дня

Механический, но разблокирующий этап: без него IDE, Psalm и composer работают вслепую (Д4).

- [ ] `git mv` всех `core/lib/**/*.class.php` → `*.php` (46 файлов). Массово, скриптом, одним коммитом.
- [ ] Удалить `spl_autoload_register` из `core/bootstrap.php:94-108`.
- [ ] `composer dump-autoload -o`, прогнать `vendor/bin/psalm` и зафиксировать новый baseline ошибок.

**Критерий готовности:** ни один файл не подключается вручную; `php -l` по всем файлам чист; сайт и админка
открываются; `vendor/bin/phpunit tests` зелёный.

**Риск:** классы, чьё имя не совпадает с путём, перестанут грузиться молча → проверять обход всех страниц
сайта и админки вручную + `curl` по каждому маршруту из `routes.php`.

### Этап 2. Config + .env вместо констант — 1-2 дня

Снимает Д3.

- [ ] `src/Config/Env.php` — парсер `.env` (без внешних зависимостей либо `vlucas/phpdotenv`).
- [ ] `src/Config/Config.php` — `get('database.host', $default)`, источник — файлы `config/*.php`,
      читающие `env()`.
- [ ] Создать `config/app.php`, `config/database.php`, `config/mail.php`, `config/telegram.php`,
      `config/cache.php`; `.env.example` с полным перечнем ключей и без значений.
- [ ] `core/config.php` временно остаётся и определяет старые константы **из** `Config` — это
      back-compat-слой, чтобы не переписывать 200+ обращений разом.
- [ ] `SystemConfig::getValue()` проксирует в `Config`, старая сигнатура сохраняется.
- [ ] Ввести `APP_ENV` (`local`/`dev`/`prod`) и `APP_DEBUG`; на них завязать Twig debug/cache и вывод ошибок.
- [ ] Обновить `phinx.php` на чтение из `Config`.

**Критерий готовности:** ни одного секрета в git; смена окружения — правкой `.env`, а не кода;
`vendor/bin/phinx migrate -e dev` работает.

### Этап 3. HTTP-ядро: Request / Response / Kernel + ErrorHandler — 3-5 дней

Снимает Д1 и половину Д5. Самый важный этап всего плана.

- [ ] `src/Http/Request.php`: `capture()` из суперглобалей, доступ к query/post/files/cookies/headers/method/path,
      `isAjax()`, `user()`.
- [ ] `src/Http/Response.php` + `JsonResponse` + `RedirectResponse`, метод `send()`.
- [ ] `src/Http/UploadedFile.php` — **единственная** точка валидации загрузок: whitelist расширений,
      проверка реального MIME через `finfo`, лимит размера. Закрывает найденную в аудите неограниченную
      загрузку файлов в `File::saveFile()`.
- [ ] `src/Routing/Router.php`: `dispatch(Request): Response`, поддержка HTTP-методов (сейчас метод запроса не
      учитывается вообще), именованных маршрутов, адаптер для старых строковых callable.
- [ ] `src/Error/ErrorHandler.php`: `set_exception_handler` + `set_error_handler` +
      `register_shutdown_function`; всё пишется в `Log`, наружу — 500-страница на `prod`, трейс на `local`.
- [ ] `src/Http/Kernel.php` + `bootstrap/app.php`.
- [ ] Перенести docroot в `public/`: `index.php`, `assets/`, `uploads/` → `public/`; обновить `.htaccess`
      и конфиг nginx на dev-хосте. Побочный эффект: любые `*.php` в корне репозитория перестают быть
      исполняемыми через веб.
- [ ] Перевести на `Request`/`Response` первые контроллеры: `SiteController` (index, info),
      `AuthController` (login, authorize, logout). Остальное пока идёт через адаптер.

**Критерий готовности:** каждый маршрут отвечает тем же HTML/JSON, что и до этапа (сверка `curl` до/после);
искусственно брошенное исключение из экшена попадает в `core/log`, а не в вывод.

**Риск:** смена docroot ломает пути в шаблонах и в `File::saveFile()` (`$_SERVER['DOCUMENT_ROOT'] . '/uploads'`).
Отдельный подпункт: заменить все обращения к `DOCUMENT_ROOT` на константы из `Config`.

### Этап 4. Middleware-конвейер, единый контроль доступа, CSRF — 3-5 дней

Снимает остаток Д5.

- [ ] `src/Http/Pipeline.php` + интерфейс `Middleware` (в стиле PSR-15).
- [ ] Вынести из `core/bootstrap.php:113-144` в middleware: `SessionMiddleware`, `UtmMiddleware`,
      `DDosMiddleware`, `AuthMiddleware` (создание текущего пользователя вместо `global $USER`).
- [ ] `CsrfMiddleware`: синхронайзер-токен в сессии, проверка на каждом POST, хелпер `csrf_field()`
      для Twig; добавить токен во все формы в `templates/`.
- [ ] `AdminMiddleware` — одна проверка прав вместо копипасты в 10 файлах админки.
- [ ] Группа маршрутов `/admin` в `routes/admin.php`; страницы `admin/*.php` переводятся в
      `app/Controllers/Admin/*` по одной, общие `admin/inc/header.php`/`footer.php` становятся Twig-шаблонами.
      Устраняется двойная инициализация `User` в `admin/inc/bootstrap.php:24-33`.
- [ ] Cookie авторизации: `HttpOnly`, `Secure`, `SameSite=Lax` (сейчас — старая позиционная сигнатура
      `setcookie()` без флагов, `User.class.php:612-614,644-646,752-754`).
- [ ] Отдельный путь входа через Telegram-виджет (`admin/login.php:45-68`) переносится в общий
      `AuthController` как один из провайдеров, а не как вторая независимая ветка аутентификации.

**Критерий готовности:** новая admin-страница без `AdminMiddleware` физически недоступна; POST без CSRF-токена
отклоняется; проверок `isAdmin()` внутри страниц не осталось.

### Этап 5. DI-контейнер, сервисы и репозитории, первые настоящие тесты — 5-7 дней

Снимает Д2 и Д6.

- [ ] `src/Container/Container.php` (PSR-11, autowiring по рефлексии) + `ServiceProvider`.
- [ ] `Connection` вместо синглтона `DataBase` — инстанс живёт в контейнере; `DataBase::getInstance()`
      остаётся временным фасадом поверх контейнера.
- [ ] `TwigRenderer` как сервис; `global $twig` исчезает.
- [ ] Разделить `User` (1040 строк) на: `Entities\User` (данные), `Repositories\UserRepository` (SQL),
      `Services\AuthService` (сессия, cookie, вход/выход), `Services\RegistrationService` (регистрация,
      верификация, письмо). Аналогично `Dialog`, `Posts`, `File`, `Roles`.
- [ ] **Транзакции** вокруг связанных записей: регистрация пользователя (user + role + meta),
      создание диалога с первым сообщением, операции MQ.
- [ ] Безопасность паролей: `password_hash()`/`password_verify()` (bcrypt) с прозрачной миграцией старых
      md5-хешей при следующем успешном входе; `hash_equals()` вместо `==` в сравнении токенов
      (`User.class.php:684,697`).
- [ ] Починить инвалидацию кэша в `User::update()` — ключ не совпадает с ключами `getAllUserData()`
      (спящий баг, выстрелит при включении `USE_CACHE`).
- [ ] Тесты: `tests/Unit/` на `AuthService`, `RegistrationService`, `Router`, `Config`, `Pipeline` —
      с подменёнными репозиториями, без БД. Заменить пустой `tests/UserTest.php`.
- [ ] Подключить прогон тестов и Psalm в CI как блокирующие.

**Критерий готовности:** `global` не встречается в `app/`; юнит-тесты проходят без запущенного MySQL;
покрытие ключевых сервисов ≥ 60%.

### Этап 6. Разделение framework / app, Console, надёжная очередь — 5-7 дней

Снимает Д7.

- [ ] Физически развести `src/` (ядро) и `app/` (демо-приложение) по целевой структуре из раздела 3.
- [ ] `composer.json`: PSR-4 `Delta\ → src/` и `App\ → app/`. Ядро выделяется в отдельный репозиторий/пакет
      `delta/framework`, текущий репозиторий становится приложением-скелетом.
- [ ] `bin/delta` + `src/Console`: команды `cron:run`, `queue:work`, `migrate`, `cache:clear`,
      `make:controller`. Заменяет `core/cron.php` c его `exec()`-порождением процессов.
- [ ] MQ: устранить race condition при взятии задачи (`MQ::execute()`, `MQ.class.php:510-573` — классический
      check-then-act) через `SELECT … FOR UPDATE` в транзакции или `GET_LOCK()`.
- [ ] MQ: заменить подсчёт воркеров парсингом `ps -awx` (`MQ.class.php:761`, Linux-only, падает молча) на
      хранение PID/heartbeat в БД; добавить активный супервизор вместо пассивного `searchAndFixStuckTasks()`.
- [ ] Архивация/чистка `d_threads_history` (315k строк, ~27 МБ, никогда не чистится).
- [ ] Определить канонический Telegram-клиент (`Telegram2` — входящий webhook, `TelegramSender` — исходящие),
      свести к одному HTTP-слою.
- [ ] `README.md` фреймворка: установка, скелет, роутинг, middleware, контейнер, консоль.

**Критерий готовности:** `composer create-project delta/skeleton` поднимает пустой рабочий проект без
диалогов, профилей и админки демо-сайта.

---

## 5. Сводная таблица

| Этап | Содержание | Оценка | Закрывает |
|------|-----------|--------|-----------|
| 0 | Гигиена, удаление `phpcmd.php` и мёртвого кода, ротация секретов | 0.5 д | Д8 |
| 1 | Единая автозагрузка, `*.class.php` → `*.php` | 0.5 д | Д4 |
| 2 | `Config` + `.env`, `APP_ENV`/`APP_DEBUG` | 1-2 д | Д3 |
| 3 | `Request`/`Response`/`Kernel`, `public/` как docroot, `ErrorHandler` | 3-5 д | Д1, часть Д5 |
| 4 | Middleware, единый контроль доступа, CSRF, перенос админки под роутер | 3-5 д | Д5 |
| 5 | DI-контейнер, сервисы/репозитории, транзакции, `password_hash`, тесты | 5-7 д | Д2, Д6 |
| 6 | Разделение `src/`/`app/`, Console, надёжная очередь, документация | 5-7 д | Д7 |
| | **Итого** | **18-27 д** | |

Порядок этапов не произволен: 3 не имеет смысла без 1-2 (иначе новые классы попадут в ту же кашу с
автозагрузкой и константами), 4 технически невозможен без `Request`/`Response` из 3, 5 требует уже
разделённого HTTP-слоя, 6 — уже выделенных сервисов из 5.

---

## 6. Как проверяется каждый этап

Автотестов, которые поймали бы регресс маршрутов и шаблонов, на этапах 0-4 ещё нет — первые реальные тесты
появляются только на Этапе 5. До этого момента приёмка ручная и обязательная:

1. `php -l` по всем изменённым файлам.
2. `vendor/bin/phpunit --testdox tests` и `vendor/bin/psalm` — не хуже зафиксированного baseline.
3. **Сверка ответов `curl` до/после** по каждому маршруту из `routes.php` и по каждой странице админки,
   с cookie jar для авторизованных сценариев.
4. Ручная проверка на `dev.it-stories.ru` сессионно-зависимых сценариев: вход, смена пароля, загрузка
   аватара, отправка сообщения (обычная и AJAX), опрос новых сообщений.
5. Каждый этап — отдельная ветка от `main`, мерж только после пунктов 1-4.

---

## 7. Риски и как их снижаем

| Риск | Снижение |
|------|----------|
| Массовое переименование на Этапе 1 ломает загрузку классов молча | Полный обход маршрутов `curl`-ом до и после; этап делается отдельным коммитом, откат — одним `git revert` |
| Перенос docroot в `public/` (Этап 3) ломает пути к загрузкам и ассетам | Все обращения к `DOCUMENT_ROOT` заменяются на значения из `Config` в рамках того же этапа; проверка отдачи аватаров и вложений |
| Рефакторинг брошен на середине → две архитектуры одновременно | Этапы нарезаны так, что каждый самоценен и мержится отдельно; адаптер старых callable позволяет остановиться после любого этапа без «недостроя» |
| Миграция паролей на bcrypt разлогинит пользователей | Прозрачная миграция при следующем успешном входе: старый md5 проверяется, при совпадении хеш перезаписывается на bcrypt |
| Перенос админки под роутер (Этап 4) ломает рабочие инструменты | Страницы переводятся по одной, старые файлы удаляются только после проверки нового маршрута |

---

## 8. Что сознательно НЕ входит в план

- Переход на Laravel/Symfony целиком — противоречит цели «собственный универсальный фреймворк».
- Замена Twig, смена СУБД, введение полноценной ORM (Doctrine) — `Repository` + `QueryBuilder` достаточно
  для текущего масштаба.
- Фронтенд-сборка (npm/webpack) — серверный рендеринг остаётся сознательным выбором.
- Внешние ключи в БД — отдельная задача, не блокирующая рефакторинг ядра.
- Чистка таблиц-парсеров без префикса (`podslyshano`, `spider`, `bashorg`, `jokes`, `myslo`) — отдельная
  задача уровня данных, кроме `test__table`/`test__table2` (удалить в рамках Этапа 0).
