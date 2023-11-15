<?php

namespace Rose\Ext;

use Rose\Expr;
use Rose\IO\File;
use Rose\IO\Directory;
use Rose\IO\Path;
use Rose\Gateway;
use Rose\Arry;

/* ****************** */
Expr::register('path::fsroot', function ($args)
{
	return Path::fsroot();
});

Expr::register('path::cwd', function ($args)
{
	return Path::cwd();
});

Expr::register('path::basename', function ($args)
{
	return Path::basename ($args->get(1));
});

Expr::register('path::extname', function ($args)
{
	return Path::extname ($args->get(1));
});

Expr::register('path::name', function ($args)
{
	return Path::name ($args->get(1));
});

Expr::register('path::normalize', function ($args)
{
	return Path::normalize ($args->get(1));
});

Expr::register('path::dirname', function ($args)
{
	return Path::dirname ($args->get(1));
});

Expr::register('path::resolve', function ($args)
{
	return Path::resolve ($args->get(1));
});

Expr::register('path::append', function ($args)
{
	$args->shift();
	return Path::append (...$args->__nativeArray);
});

Expr::register('path::join', function ($args)
{
	$args->shift();
	return Path::append (...$args->__nativeArray);
});

Expr::register('path::is_file', function ($args)
{
	return Path::isFile ($args->get(1));
});

Expr::register('path::is_dir', function ($args)
{
	return Path::isDir ($args->get(1));
});

Expr::register('path::is_link', function ($args)
{
	return Path::isLink ($args->get(1));
});

Expr::register('path::exists', function ($args)
{
	return Path::exists ($args->get(1));
});

Expr::register('path::chmod', function ($args)
{
	return Path::chmod ($args->get(1), octdec($args->get(2)));
});

Expr::register('path::chdir', function ($args)
{
	return Path::chdir ($args->get(1));
});

Expr::register('path::rename', function ($args)
{
	return Path::rename ($args->get(1), $args->get(2));
});

Expr::register('path::symlink', function ($args)
{
	return Path::symlink ($args->get(1), $args->get(2));
});

Expr::register('path::link', function ($args)
{
	return Path::link ($args->get(1), $args->get(2));
});

Expr::register('path::tempnam', function ($args)
{
	return Path::tempnam($args->has(1) ? $args->get(1) : '');
});

Expr::register('path::temp', function ($args)
{
	return Path::temp();
});


/* ****************** */

Expr::register('file::size', function ($args)
{
	return File::size($args->get(1));
});

Expr::register('file::dump', function ($args)
{
	File::dump($args->get(1));
	return null;
});

Expr::register('file::mtime', function ($args)
{
	return File::mtime($args->get(1));
});

Expr::register('file::atime', function ($args)
{
	return File::atime($args->get(1));
});

Expr::register('file::touch', function ($args)
{
	return File::touch($args->get(1), $args->has(2) ? $args->get(2) : null);
});

Expr::register('file::read', function ($args)
{
	return Path::exists($args->get(1)) ? File::getContents($args->get(1)) : null;
});

Expr::register('file::write', function ($args)
{
	File::setContents($args->get(1), $args->get(2));
	return null;
});

Expr::register('file::append', function ($args)
{
	File::appendContents($args->get(1), $args->get(2));
	return null;
});

Expr::register('file::remove', function ($args)
{
	File::remove($args->get(1));
	return null;
});

Expr::register('file::unlink', function ($args)
{
	File::unlink($args->get(1));
	return null;
});

Expr::register('file::copy', function ($args)
{
	File::copy($args->get(1), $args->get(2));
	return null;
});

Expr::register('file::create', function ($args)
{
	return File::create($args->get(1));
});


/* ****************** */
Expr::register('dir::create', function ($args)
{
	return Directory::create($args->get(1), true);
});

Expr::register('dir::files', function ($args)
{
	return Directory::readFiles ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/')?->files ?? new Arry();
});

Expr::register('dir::dirs', function ($args)
{
	return Directory::readDirs ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/')?->dirs ?? new Arry();
});

Expr::register('dir::entries', function ($args)
{
	return Directory::read ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/', 0, Directory::READ_FILES | Directory::READ_DIRS);
});

Expr::register('dir::files-recursive', function ($args)
{
	return Directory::readFiles ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/')?->files ?? new Arry();
});

Expr::register('dir::dirs-recursive', function ($args)
{
	return Directory::readDirs ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/')?->dirs ?? new Arry();
});

Expr::register('dir::entries-recursive', function ($args)
{
	return Directory::read ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/', 0, Directory::READ_FILES | Directory::READ_DIRS);
});

Expr::register('dir::remove', function ($args)
{
	Directory::remove($args->get(1), \Rose\bool($args->{2}));
	return null;
});

Expr::register('dir::remove-recursive', function ($args)
{
	Directory::remove($args->get(1), true);
	return null;
});

Expr::register('dir::rmdir', function ($args)
{
	Directory::rmdir($args->get(1));
	return null;
});

Expr::register('dir::copy', function ($args)
{
	Directory::copy ($args->get(1), $args->get(2), $args->has(3) ? $args->get(3) : true, true);
	return null;
});
