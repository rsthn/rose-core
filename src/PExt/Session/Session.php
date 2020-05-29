<?php
/*
**	Rose\Ext\Session
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

use Rose\Session;

Expr::register('session::open', function ($args)
{
	return Session::open();
});

Expr::register('session::close', function ($args)
{
	return Session::close();
});

Expr::register('session::destroy', function ($args)
{
	return Session::destroy();
});

Expr::register('session::clear', function ($args)
{
	return Session::clear();
});

Expr::register('session::name', function ($args)
{
	return Session::$sessionName;
});

Expr::register('session::id', function ($args)
{
	return Session::$sessionId;
});

Expr::register('session::isopen', function ($args)
{
	return Session::$sessionOpen;
});

Expr::register('session::data', function ($args)
{
	return Session::$data;
});
