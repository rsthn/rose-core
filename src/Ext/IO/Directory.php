<?php

namespace Rose\Ext;

use Rose\Expr;
use Rose\IO\Directory;
use Rose\Arry;

Expr::register('dir:create', function ($args) {
    return Directory::create($args->get(1), true);
});

Expr::register('dir:files', function ($args) {
    return Directory::readFiles ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/')?->files ?? new Arry();
});

Expr::register('dir:dirs', function ($args) {
    return Directory::readDirs ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/')?->dirs ?? new Arry();
});

Expr::register('dir:entries', function ($args) {
    return Directory::read ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/', 0, Directory::READ_FILES | Directory::READ_DIRS);
});

Expr::register('dir:files-recursive', function ($args) {
    return Directory::readFiles ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/')?->files ?? new Arry();
});

Expr::register('dir:dirs-recursive', function ($args) {
    return Directory::readDirs ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/')?->dirs ?? new Arry();
});

Expr::register('dir:entries-recursive', function ($args) {
    return Directory::read ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/', 0, Directory::READ_FILES | Directory::READ_DIRS);
});

Expr::register('dir:remove', function ($args) {
    Directory::remove($args->get(1), \Rose\bool($args->{2}));
    return null;
});

Expr::register('dir:remove-recursive', function ($args) {
    Directory::remove($args->get(1), true);
    return null;
});

Expr::register('dir:rmdir', function ($args) {
    Directory::rmdir($args->get(1));
    return null;
});

Expr::register('dir:copy', function ($args) {
    Directory::copy ($args->get(1), $args->get(2), $args->has(3) ? $args->get(3) : true, true);
    return null;
});
