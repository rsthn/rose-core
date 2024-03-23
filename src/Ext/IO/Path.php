<?php

namespace Rose\Ext;

use Rose\Expr;
use Rose\IO\Path;

Expr::register('path:fsroot', function ($args) {
    return Path::fsroot();
});

Expr::register('path:cwd', function ($args) {
    return Path::cwd();
});

Expr::register('path:basename', function ($args) {
    return Path::basename ($args->get(1));
});

Expr::register('path:extname', function ($args) {
    return Path::extname ($args->get(1));
});

Expr::register('path:name', function ($args) {
    return Path::name ($args->get(1));
});

Expr::register('path:normalize', function ($args) {
    return Path::normalize ($args->get(1));
});

Expr::register('path:dirname', function ($args) {
    return Path::dirname ($args->get(1));
});

Expr::register('path:resolve', function ($args) {
    return Path::resolve ($args->get(1));
});

Expr::register('path:append', function ($args) {
    $args->shift();
    return Path::append (...$args->__nativeArray);
});

Expr::register('path:join', function ($args) {
    $args->shift();
    return Path::append (...$args->__nativeArray);
});

Expr::register('path:is-file', function ($args) {
    return Path::isFile ($args->get(1));
});

Expr::register('path:is-dir', function ($args) {
    return Path::isDir ($args->get(1));
});

Expr::register('path:is-link', function ($args) {
    return Path::isLink ($args->get(1));
});

Expr::register('path:exists', function ($args) {
    return Path::exists ($args->get(1));
});

Expr::register('path:chmod', function ($args) {
    return Path::chmod ($args->get(1), octdec($args->get(2)));
});

Expr::register('path:chdir', function ($args) {
    return Path::chdir ($args->get(1));
});

Expr::register('path:rename', function ($args) {
    return Path::rename ($args->get(1), $args->get(2));
});

Expr::register('path:symlink', function ($args) {
    return Path::symlink ($args->get(1), $args->get(2));
});

Expr::register('path:link', function ($args) {
    return Path::link ($args->get(1), $args->get(2));
});

Expr::register('path:tempnam', function ($args) {
    return Path::tempnam($args->has(1) ? $args->get(1) : '');
});

Expr::register('path:temp', function ($args) {
    return Path::temp();
});
