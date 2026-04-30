<p align="center">
	<a href="https://packagist.org/packages/rsthn/rose-core"><img src="https://poser.pugx.org/rsthn/rose-core/downloads" alt="Total Downloads"></a>
	<a href="https://packagist.org/packages/rsthn/rose-core"><img src="https://poser.pugx.org/rsthn/rose-core/v" alt="Latest Stable Version"></a>
	<a href="https://packagist.org/packages/rsthn/rose-core"><img src="https://poser.pugx.org/rsthn/rose-core/license" alt="License"></a>
</p>

# About Rose

Rose is a framework designed aiming to the highest-level of abstraction possible, this is achieved by using an internal expression language (dialect of Lisp) to code entire APIs and systems. This effectively allows the developer to **never write any host language code** when building core logic.

The host language is the language in which the `rose-core` was built, for the repository you're viewing right now, that would be PHP.

In the future we're planning to build ports of Rose and its extensions for other host languages such as Python, NodeJS or whatever hot crazy lang may be available in the future. And when that happens, all of your current code written for Rose will **continue to work seamlessly** in the new host language (given that all required extensions are _of course_ ported to the new host language as well).

And _that_ dear readers, is the power of Rose.

<br/>

# Requirements

Rose targets **PHP 8.0+**. The following PHP extensions are used by the framework:

### Required (always)

| Extension | Used for |
|---|---|
| `mbstring` | Unicode-aware string operations across `Text` / `Strings` |
| `json` | `json:parse` / `json:dump`, response serialization |
| `curl` | HTTP client (`request:*`) |
| `openssl` | `openssl:*`, certificate/PKI handling, `crypto:random-bytes` |
| `hash` | `crypto:hash` / `crypto:hmac` and related digests |
| `zlib` | `gz:compress`, `gz:deflate`, `gz:inflate`, `gz:decompress` |
| `gd` | All `image:*` operations |
| `simplexml` | `xml:parse`, `xml:simplify` |
| `session` | PHP-native session storage backend (bundled in PHP) |
| `date` | `DateTime` / timezone handling (bundled in PHP) |

### Optional — database drivers

A driver extension is required **only** if its driver is selected via the `driver` field in the `[Database]` section of `system.conf`:

| Driver value | PHP extension |
|---|---|
| `mysql` / `mysqli` | `mysqli` |
| `postgres` | `pgsql` |
| `sqlserver` | `sqlsrv` |
| `odbc` | `odbc` |

If you don't use the `db:*` expressions, none of these are needed.

<br/>

# Installation

As any other package of the modern web, this one should be installed using a package manager. In this case, we're on Packagist and you can install Rose using composer.

**However** this is `rose-core`, and should not be used alone. Please use one of our pre-made project skeletons instead:

|Project Type|Package URL|Description|
|------------|-----------|-----------|
|API|[https://github.com/rsthn/rose-api](https://github.com/rsthn/rose-api)|Deploy flexible and secure APIs in the blink of an eye with Rose's internal API extension, Wind.

<br/>

# Expression Functions

Rose comes with a powerful expression evaluator (located in the `Expr` class), based on a lisp-like (or clojure-like if you would) language variation known as Violet. There are several built-in functions and you can find all of them in the [Documentation](./docs/README.md).

<br/>

# Configuration

Rose loads its configuration from `conf/system.conf` at startup, with environment-specific overrides layered on top. Every section and field consumed by the framework — `Gateway`, `Session`, `Locale`, `Database`, `imports`, `endpoints`, and more — is documented in the [Configuration Reference](./docs/CONFIG.md).

<br/>

# FAQ

**Q: If there is no need to write host-language code, what happens when I need something new that is not doable using current functions?**

A: Given such case, you're free to build your own Rose-extensions in the host-language, and use it in any expression. Read our extensions documentation for more information.
