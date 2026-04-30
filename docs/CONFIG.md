# Configuration

Rose loads configuration at startup from `conf/system.conf` (relative to the core directory). If a `rose-env` file exists in the working directory — or the `ROSE_ENV` environment variable is set — its value is used as an environment id, and `conf/<env>.conf` is loaded on top of `system.conf`, overriding any matching keys.

The configuration format is a simple INI-style: sections delimited by `[name]`, fields written as `key=value`. The equal sign in values is preserved (only the first one delimits the name). Multi-line values are wrapped in single back-ticks.

```ini
[Gateway]
server_name=example.com
allow_origin=*

[Session]
name=app_session
expires=3600
```

> All boolean-like settings are matched against the literal **string** `'true'` (or `'false'` for the inverse) — the parser is string-based.

The loaded configuration object is exposed to Rose code via `(config)` and individual sections as `(config.<section>.<field>)`.

<br/>

# Sections

## `[Gateway]`

| Field | Purpose |
|---|---|
| `server_name` | Overrides the auto-detected server name when building `Gateway::ep`. Also used as the default cookie domain. |
| `allow_origin` | If set, enables CORS headers. Value `*` echoes back the request `Origin`; any other value is sent verbatim as `Access-Control-Allow-Origin`. Also gates the `SameSite` cookie attribute. |
| `custom_methods` | Extra HTTP methods appended to `Access-Control-Allow-Methods` (only meaningful when `allow_origin` is set). |
| `same_site` | Cookie `SameSite` attribute (`Strict` / `Lax` / `None`). Defaults to `None` when omitted. Only attached to cookies when `allow_origin` is set. |
| `service` | If set, forces every request to be routed through that service (overrides the `srv` request param). Use `wind-3` for the latest version of the Wind service. |
| `banner` | Rose expression evaluated and returned when the Wind handler is invoked with no `f` parameter. |
| `display_errors` | When the value is anything other than `'false'`, uncaught errors are rendered to the response. |
| `access_log` | If `'true'`, requests are written to the access log. |

## `[Session]`

| Field | Purpose |
|---|---|
| `name` | Session name — used as the cookie name and also as the prefix of the `m_<name>` POST/GET param that overrides the session id. |
| `database` | When `'true'`, sessions are stored in the configured database instead of PHP's native session storage. |
| `expires` | Session TTL in seconds (cookie expiration + idle-timeout check via `last_activity`). `0` = browser session. |
| `cookie_path` | Path attribute for the session cookie. Falls back to `Gateway.root + '/'`. |
| `cookie_domain` | Domain attribute for the session cookie. Falls back to `Gateway.server_name`. |

## `[Locale]`

| Field | Purpose |
|---|---|
| `timezone` | IANA timezone or fixed offset (e.g. `+00:00`) installed at startup. Invalid values fall back to `+00:00`. |
| `include_millis` | When `'true'`, `DateTime` values include milliseconds by default. |
| `lang` | Two-letter language code used by the `Strings` subsystem. |
| `numeric`, `time`, `date`, `datetime` | Default format strings for the corresponding `locale:format` types. |
| `DT_*` (any key with `DT_` prefix) | Custom strftime format keyed by the user's `<format-type>` argument to `locale:format`. |
| `NUMERIC*` (any key with `NUMERIC` prefix) | Custom 3-character numeric format (decimal char, decimals count, thousands separator). |
| Any user-defined key | Arbitrary alias resolved when passed as the `format` argument to `locale:format` for `NUMBER`/`INTEGER`/`TIME`/`DATE`/`DATETIME`. |

## `[Database]`

Section consumed wholesale by `Connection::fromConfig()`.

| Field | Purpose |
|---|---|
| `driver` | Driver id — see the table below for supported values. |
| `server` | Database host. |
| `port` | TCP port. |
| `user` | Database username. |
| `password` | Database password. |
| `database` | Database / schema name. |
| `prefix` | Optional prefix prepended to table names. |
| `trace` | When `'true'`, every executed query is logged to `system.log`. |
| `postgres_types` | (PostgreSQL only.) When **not** `'false'`, the driver applies its native PHP type coercion. Defaults to enabled. |

### Supported drivers

| `driver` value | Backend | Required PHP extension |
|---|---|---|
| `mysql` | MySQL / MariaDB (MySQLi) | `mysqli` |
| `mysqli` | MySQL / MariaDB (alias of `mysql`) | `mysqli` |
| `postgres` | PostgreSQL | `pgsql` |
| `sqlserver` | Microsoft SQL Server | `sqlsrv` |
| `odbc` | Generic ODBC connection | `odbc` |

## `[Strings]`

| Field | Purpose |
|---|---|
| `debug` | Stored on the `Strings` singleton's `$debug` flag (used internally to log lookup misses). |

## `[flags]`

| Field | Purpose |
|---|---|
| `file_touch_disabled` | When `'true'`, `File::touch()` is a no-op. |

## `[imports]`

Free-form section. Each `key = template` pair lets `(import key)` resolve a short alias to a real source path. The value is evaluated as a Rose template (text mode), so `(...)` segments inside the value are interpolated against the live config and runtime context before resolution. This is invoked only when the alias has no matching file on disk.

```ini
[imports]
math=lib/math
util=vendor/acme/util
lib/directives=lib/directives.(config.Database.driver).fn
```

After this, `(import "math")` resolves as if you had written `(import "lib/math")`. The `lib/directives` entry is resolved per-driver — e.g. `lib/directives.mysql.fn` when the database driver is `mysql`.

## `[endpoints]`

Free-form section that maps HTTP requests to Wind handlers. Each entry has the form:

```
<METHOD> <path> = <handler>[ <handler>...]
```

- **METHOD** is an HTTP verb (`GET`, `POST`, `PUT`, ...) or `*` to match any method.
- **path** is a path pattern relative to the gateway root. Path variables are written as `{name}` and are exposed to the handler in `params.<name>`.
- **handler** is `<source-path>` or `<source-path>:<function-name>`. When the function name is omitted, the entrypoint (`main` by default) is used. Multiple handlers separated by spaces are invoked in order; the response of the last one is returned.

```ini
[endpoints]
GET /users          = api/users:list
GET /users/{id}     = api/users:get
POST /users         = api/users:create
* /health           = api/health
```

A request to `GET /users/42` invokes `(get (& ctx) (& id "42"))` defined in `api/users.fn`.

### Multiple handlers (middleware)

When a value contains several space-separated handlers, they run in order and share the same `ctx` / `params` arguments. The response of the **last** handler is what gets returned to the client — earlier handlers are typically used as middleware (auth checks, request validation, logging) that either pass through or throw to abort the request.

```ini
[endpoints]
GET /users/{user_id} = lib/handler:auth lib/handler:get_user_info
```

For a request to `GET /users/42`, `lib/handler:auth` runs first (it can throw to short-circuit the response), then `lib/handler:get_user_info` runs and its return value is sent back to the client.

<br/>

# Top-level / meta

- **`config.env`** — public property on the `Configuration` instance itself (not a section). Holds the environment id loaded from the `rose-env` file or `ROSE_ENV` env var; used to layer `<env>.conf` on top of `system.conf`.

<br/>

# Sample `system.conf`

```ini
[Locale]
numeric=.2,
time=%I:%M %p
date=%d/%m/%Y
datetime=%d/%m/%Y %H:%M
timezone=UTC
include_millis=true

[Gateway]
service=wind-3
access_log=false
allow_origin=*
same_site=lax
banner={ platform "rose-core" version (file:read "VERSION") commit (file:read "COMMIT") }
display_errors=false
custom_methods=RESET, PLAY, SOFT-DELETE

[Session]
expires=604800
name=app_session
database=true

[Database]
driver=postgres
server=localhost
port=5432
user=app
password=secret
database=app_db
prefix=
trace=false

[imports]
lib/directives=lib/directives.(config.Database.driver).fn

[endpoints]
GET /users/{user_id} = lib/handler:auth lib/handler:get_user_info
POST /users          = lib/handler:auth lib/handler:create_user
* /health            = lib/health
```
