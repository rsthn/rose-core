<?php
/*
**	Rose\Ext\Image
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

use Rose\Errors\ArgumentError;
use Rose\Expr;
use Rose\Image;

/* ****************** */
function getImage ($args)
{
	$img = $args->get(1);
	if (\Rose\typeOf($img) != 'Rose\\Image') throw new ArgumentError('Image object was expected');
	return $img;
}

Expr::register('image::load', function ($args)
{
	return new Image ($args->get(1));
});

Expr::register('image::save', function ($args)
{
	$img = getImage($args);
	$img->save ($args->{2}, $args->{3});
	return null;
});

Expr::register('image::output', function ($args)
{
	$img = getImage($args);
	$img->output ($args->{2});
	return null;
});

Expr::register('image::data', function ($args)
{
	$img = getImage($args);
	return $img->data ($args->{2}, $args->{3} == 'true', $args->{4} == 'true');
});

Expr::register('image::width', function ($args)
{
	$img = getImage($args);
	return $img->width ($args->{2}, $args->{3} == 'false' ? false : true);
});

Expr::register('image::height', function ($args)
{
	$img = getImage($args);
	return $img->height ($args->{2}, $args->{3} == 'false' ? false : true);
});

Expr::register('image::resize', function ($args)
{
	$img = getImage($args);
	return $img->resize ($args->get(2), $args->get(3), $args->{4} == 'false' ? false : true);
});

Expr::register('image::scale', function ($args)
{
	$img = getImage($args);
	return $img->scale ($args->get(2), $args->{3}, $args->{4} == 'false' ? false : true);
});

Expr::register('image::fit', function ($args)
{
	$img = getImage($args);
	return $img->fit ($args->get(2), $args->get(3), $args->{4} == 'true' ? true : false);
});

Expr::register('image::cut', function ($args)
{
	$img = getImage($args);
	return $img->cut ($args->get(2), $args->get(3), $args->get(4), $args->get(5));
});

Expr::register('image::crop', function ($args)
{
	$img = getImage($args);
	return $img->crop ($args->get(2), $args->get(3), $args->get(4), $args->get(5));
});

Expr::register('image::smartcut', function ($args)
{
	$img = getImage($args);
	return $img->smartCut ($args->get(2), $args->get(3), $args->get(4), $args->get(5), $args->{6} == 'true' ? true : false);
});
