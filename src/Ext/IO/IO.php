<?php
/*
**	Rose\Ext\IO
**
**	Copyright (c) 2019-2020, RedStar Technologies, All rights reserved.
**	https://rsthn.com/
**
**	THIS LIBRARY IS PROVIDED BY REDSTAR TECHNOLOGIES "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
**	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
**	PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL REDSTAR TECHNOLOGIES BE LIABLE FOR ANY
**	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
**	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
**	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
**	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
**	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Rose\Ext;

use Rose\Expr;
use Rose\IO\File;
use Rose\IO\Directory;
use Rose\IO\Path;
use Rose\Gateway;

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
	return Path::is_file ($args->get(1));
});

Expr::register('path::is_dir', function ($args)
{
	return Path::is_dir ($args->get(1));
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
	return File::touch($args->get(1));
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
	return Directory::readFiles ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/')->files;
});

Expr::register('dir::dirs', function ($args)
{
	return Directory::readDirs ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/')->dirs;
});

Expr::register('dir::entries', function ($args)
{
	return Directory::read ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/', 0, Directory::READ_FILES | Directory::READ_DIRS);
});

Expr::register('dir::files:recursive', function ($args)
{
	return Directory::readFiles ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/')->files;
});

Expr::register('dir::dirs:recursive', function ($args)
{
	return Directory::readDirs ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/')->dirs;
});

Expr::register('dir::entries:recursive', function ($args)
{
	return Directory::read ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/', 0, Directory::READ_FILES | Directory::READ_DIRS);
});

Expr::register('dir::remove', function ($args)
{
	Directory::remove($args->get(1), \Rose\bool($args->{2}));
	return null;
});
