<?php

namespace Rose\Ext;

use Rose\Expr;
use Rose\IO\File;
use Rose\IO\Path;

Expr::register('file:size', function ($args) {
    return File::size($args->get(1));
});

Expr::register('file:dump', function ($args) {
    File::dump($args->get(1));
    return null;
});

Expr::register('file:mtime', function ($args) {
    return File::mtime($args->get(1));
});

Expr::register('file:atime', function ($args) {
    return File::atime($args->get(1));
});

Expr::register('file:touch', function ($args) {
    return File::touch($args->get(1), $args->has(2) ? $args->get(2) : null);
});

Expr::register('file:read', function ($args) {
    return Path::exists($args->get(1)) ? File::getContents($args->get(1)) : null;
});

Expr::register('file:write', function ($args) {
    File::setContents($args->get(1), $args->get(2));
    return null;
});

Expr::register('file:append', function ($args) {
    File::appendContents($args->get(1), $args->get(2));
    return null;
});

Expr::register('file:remove', function ($args) {
    File::remove($args->get(1));
    return null;
});

Expr::register('file:unlink', function ($args) {
    File::unlink($args->get(1));
    return null;
});

Expr::register('file:copy', function ($args) {
    File::copy($args->get(1), $args->get(2));
    return null;
});

Expr::register('file:create', function ($args) {
    return File::create($args->get(1));
});
