<?php
/*
**	Rose\Ext\Misc
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

use Rose\DateTime;
use Rose\Expr;

/*
**	datetime::now [target_timezone]
*/
Expr::register('datetime::now', function ($args) {
	return new DateTime('now', $args->length == 2 ? $args->get(1) : null);
});

/*
**	datetime::now:int [target_timezone]
*/
Expr::register('datetime::now:int', function ($args) {
	return (new DateTime('now', $args->length == 2 ? $args->get(1) : null))->getTimestamp();
});

/*
**	datetime::parse <datetime> [target_timezone] [from_timezone]
*/
Expr::register('datetime::parse', function ($args) {
	return new DateTime($args->get(1), $args->length >= 3 ? $args->get(2) : null, $args->length >= 4 ? $args->get(3) : null);
});

/*
**	datetime::int <datetime>
*/
Expr::register('datetime::int', function ($args) {
	return DateTime::getUnixTimestamp($args->get(1));
});

/*
**	datetime::sub <datetime> <datetime> [unit]
*/
Expr::register('datetime::sub', function ($args) {
	$a = new DateTime ($args->get(1));
	$b = new DateTime ($args->get(2));

	$unit = $args->length == 4 ? $args->get(3) : 'SECOND';
	return $a->sub($b, $unit);
});

/*
**	datetime::add <datetime> <value> [unit]
*/
Expr::register('datetime::add', function ($args) {
	$a = new DateTime ($args->get(1));
	$b = $args->get(2);

	$unit = $args->length == 4 ? $args->get(3) : 'SECOND';
	return $a->add($b, $unit);
});
