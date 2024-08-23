<?php

require('vendor/autoload.php');

use Rose\Main;
use Rose\Text;
use Rose\DateTime;
use Rose\Locale;
use Rose\Arry;
use Rose\Map;
use Rose\Math;
use Rose\Regex;
use Rose\Configuration;
use Rose\Cookies;
use Rose\Gateway;
use Rose\Resources;
use Rose\Strings;
use Rose\Session;
use Rose\Expr;

Main::$CORE_DIR = './test';
Main::cli(dirname(__FILE__), true);

$grandTotal = 0;
$grandFailed = 0;
$failed = [];
$total = 0;

function test ($index, $value)
{
	global $failed, $grandFailed;
	global $total, $grandTotal;

	$total++;
	$grandTotal++;

	if ($value === true)
	{
		echo "\x1B[92m#\x1B[0m";
	}
	else
	{
		echo "\x1B[91m#\x1B[0m";
		$failed[] = $index;
		$grandFailed++;
	}
}

function title ($value)
{
	global $failed, $grandFailed;
	global $total, $grandTotal;
	
	echo "\n";

	if ($total != 0)
	{
		echo "\x1B[90mPassed (" . $total . ")";
	}

	if (count($failed) != 0)
	{
		echo " | \x1B[91mFailed (" . count($failed) . ") : ";

		foreach ($failed as $i)
			echo $i . " ";
	}

	if ($total != 0)
	{
		echo "\x1B[0m\n";
		echo "\n";
	}

	$failed = [];
	$total = 0;

	if (!$value)
	{
		echo "\x1B[97mCompleted: " . $grandTotal . " tests, " . $grandFailed . " failed.";
		echo "\x1B[0m\n";
	
		if ($grandFailed != 0)
			exit(1);

		exit(0);
	}

	echo "\x1B[97mClass: ".$value."\x1B[0m\n";
}

function mustThrow ($callback)
{
	try {
		$callback ();
		return false;
	}
	catch (\Throwable $e) {
		return true;
	}
}

// *****************************************************************************
title('Main');

$a = new Arry();
$c = new Configuration();

test(  1, Rose\typeOf('hi') === 'primitive' );
test(  2, Rose\typeOf('hi', true) === 'string' );
test(  3, Rose\typeOf(null) === 'primitive' );
test(  4, Rose\typeOf(null, true) === 'null' );
test(  5, Rose\typeOf(4) === 'primitive' );
test(  6, Rose\typeOf(4, true) === 'int' );
test(  7, Rose\typeOf(-4.5) === 'primitive' );
test(  8, Rose\typeOf(-4.5, true) === 'number' );
test(  9, Rose\typeOf(true) === 'primitive' );
test( 10, Rose\typeOf(true, true) === 'bool' );
test( 11, Rose\typeOf(false) === 'primitive' );
test( 12, Rose\typeOf(false, true) === 'bool' );
test( 13, Rose\typeOf(function(){}) === 'function' );
test( 14, Rose\typeOf(function(){}, true) === 'function' );
test( 15, Rose\typeOf([]) === 'primitive' );
test( 16, Rose\typeOf([], true) === 'array' );
test( 17, Rose\typeOf($a) === 'Rose\\Arry' );
test( 18, Rose\isString(null) === false );
test( 19, Rose\isString('null') === true );
test( 20, Rose\bool('false') === false );
test( 21, Rose\bool(false) === false );
test( 22, Rose\bool(0) === false );
test( 23, Rose\bool('true') === true );
test( 24, Rose\bool(true) === true );
test( 25, Rose\bool(12) === true );
test( 26, mustThrow(function () { Rose\raiseWarning('WARN'); }));
test( 27, mustThrow(function () { Rose\raiseError('ERR'); }));
test( 28, Rose\isSubTypeOf($c, 'Rose\\Map'));
test( 29, Rose\mstime() != 0);

// *****************************************************************************
title('Text');
$text = 'HELLO wOrlD';

test(  1, 'wOrlD' === Text::substring($text, 6) );
test(  2, 'wOrlD' === Text::substring($text, 6, 20) );
test(  3, 'HELLO WORLD' === Text::toUpperCase($text) );
test(  4, 'hello world' === Text::toLowerCase($text) );
test(  7, 7 === Text::indexOf($text, 'Or') );
test(  8, 7 === Text::indexOf($text, 'Or', 4) );
test(  9, false === Text::indexOf($text, 'X') );
test( 10, 7 === Text::indexOf($text, 'Or') );
test( 11, false === Text::indexOf($text, 'X') );
test( 12, 7 === Text::lastIndexOf($text, 'Or') );
test( 13, 7 === Text::lastIndexOf($text, 'Or', 5) );
test( 14, false === Text::lastIndexOf($text, 'Or', -5) );
test( 15, 11 === Text::length($text) );
test( 16, 'LLO wOrl' === Text::trim($text, 'HEOD') );
test( 17, true === Text::startsWith($text, 'HEL') );
test( 18, false === Text::startsWith($text, 'HEX') );
test( 19, true === Text::endsWith($text, 'lD') );
test( 20, false === Text::endsWith($text, 'LD') );
test( 21, 'DlrOw OLLEH' === Text::reverse($text) );
test( 22, 'HEO wOrlD' === Text::replace('L', '', $text) );
test( 24, '["H","E","L","L","O"," ","w","O","r","l","D"]' === (string)Text::split('', $text) );
test( 25, '["HE","","O wOrlD"]' === (string)Text::split('L', $text) );

$text = null;
test( 26, '' === Text::substring($text, 6) );
test( 27, '' === Text::substring($text, 6, 20) );
test( 28, '' === Text::toUpperCase($text) );
test( 29, '' === Text::toLowerCase($text) );
test( 32, false === Text::indexOf($text, 'Or') );
test( 33, false === Text::indexOf($text, 'Or', 4) );
test( 34, false === Text::indexOf($text, 'X') );
test( 35, false === Text::indexOf($text, 'Or') );
test( 36, false === Text::indexOf($text, 'X') );
test( 37, false === Text::lastIndexOf($text, 'Or') );
test( 38, false === Text::lastIndexOf($text, 'Or', 5) );
test( 39, false === Text::lastIndexOf($text, 'Or', -5) );
test( 40, 0 === Text::length($text) );
test( 41, '' === Text::trim($text, 'HEOD') );
test( 42, false === Text::startsWith($text, 'HEL') );
test( 43, false === Text::startsWith($text, 'HEX') );
test( 44, false === Text::endsWith($text, 'lD') );
test( 45, false === Text::endsWith($text, 'LD') );
test( 46, '' === Text::reverse($text) );
test( 47, '' === Text::replace('L', '', $text) );
test( 49, '[]' === (string)Text::split('', $text) );
test( 50, '[""]' === (string)Text::split('L', $text) );

$text = '0';
test( 51, '' === Text::substring($text, 6) );
test( 52, '' === Text::substring($text, 6, 20) );
test( 53, '0' === Text::toUpperCase($text) );
test( 54, '0' === Text::toLowerCase($text) );
test( 57, 0 === Text::indexOf($text, '0') );
test( 58, false === Text::indexOf($text, '0', 4) );
test( 59, false === Text::indexOf($text, 'X') );
test( 60, 0 === Text::indexOf($text, '0') );
test( 61, false === Text::indexOf($text, 'X') );
test( 62, 0 === Text::lastIndexOf($text, '0') );
test( 63, false === Text::lastIndexOf($text, 'Or', 5) );
test( 64, false === Text::lastIndexOf($text, 'Or', -5) );
test( 65, 1 === Text::length($text) );
test( 66, '0' === Text::trim($text, 'HEOD') );
test( 67, false === Text::startsWith($text, 'HEL') );
test( 68, true === Text::startsWith($text, '0') );
test( 69, false === Text::endsWith($text, 'lD') );
test( 70, true === Text::endsWith($text, '0') );
test( 71, '0' === Text::reverse($text) );
test( 72, '0' === Text::replace('L', '', $text) );
test( 74, '["0"]' === (string)Text::split('', $text) );
test( 75, '["0"]' === (string)Text::split('L', $text) );
test( 76, '[]' === (string)Text::split('', '') );

// *****************************************************************************
title('DateTime');

DateTime::setTimezone('+4:30');

$a = new DateTime(0, 'UTC', 'UTC');
$b = new DateTime('2022-03-25 13:10:32');
$c = new DateTime(0);
$d = new DateTime('2022-03-25 13:10:32', 'UTC', 'LTZ');

test(  1, '1970-01-01 00:00:00' === (string)$a );
test(  2, '2022-03-25 13:10:32' === (string)$b );
test(  3, '1970-01-01 04:30:00' === (string)$c );
test(  4, '2022-03-25 08:40:32' === (string)$d );
test(  5, 2022 === $b->year );
test(  6, 3 === $b->month );
test(  7, 25 === $b->day );
test(  8, 13 === $b->hour );
test(  9, 10 === $b->minute );
test( 10, 32 === $b->second );
test( 11, 0 === $a->getTimestamp() );
test( 12, 0 === $c->getTimestamp() );
test( 13, -16200 === DateTime::getUnixTimestamp('1970-01-01 00:00:00') );
test( 14, 7*24*60*60 === DateTime::getUnit('WEEK') );
test( 15, '1970-01-09 04:15:00' === (string)$a->add(4, DateTime::HOUR)->add(15, DateTime::MINUTE)->add(15, DateTime::DAY)->add(-1, DateTime::WEEK) );
test( 16, 270 === $a->sub('1970-01-09 04:15:00', DateTime::MINUTE) );
test( 17, '1970-01-09 04:15:00' === $a->format('DATETIME') );
test( 18, 'Fri, 09 Jan 1970 04:15:00 GMT' === $a->format('UTC') );
test( 19, '04:15:00' === $a->format('TIME') );
test( 20, '1970-01-09' === $a->format('DATE') );

// *****************************************************************************
title('Locale');

$L = Locale::getInstance();

test(  1, '1,234.98' === $L->format('NUMBER', 1234.9777, '.2,') );
test(  2, '1,234.97' === $L->format('NUMBER', 1234.9711, '.2,') );
test(  3, '-1,234.97' === $L->format('NUMBER', -1234.9711, '.2,') );
test(  4, '1,235' === $L->format('INTEGER', 1234.9711, '.2,') );
test(  5, '1,234' === $L->format('INTEGER', 1234.477, '.2,') );
test(  6, '04:15 AM' === $L->format('TIME', $a, '%H:%M %p') );
test(  7, '01/09/70' === $L->format('DATE', $a, '%m/%d/%y') );
test(  8, '01/09/1970' === $L->format('DATE', $a, '%m/%d/%Y') );
test(  9, '01/09/1970 04:15 AM' === $L->format('DATETIME', $a, '%m/%d/%Y %H:%M %p') );
test( 10, 'Thu, 08 Jan 1970 23:45:00 GMT' === $L->format('GMT', $a) );
test( 11, '1970-01-08T23:45:00Z' === $L->format('UTC', $a) );
test( 12, '1970-01-09' === $L->format('ISO_DATE', $a) );
test( 13, '04:15:00' === $L->format('ISO_TIME', $a) );
test( 14, '1970-01-09 04:15:00' === $L->format('ISO_DATETIME', $a) );

// *****************************************************************************
title('Arry');

$a = new Arry();
$b = new Arry([1,3,2]);
$c = new Arry(['A','B',1032]);
$d = new Arry([1,[2,3],4]);
$e = new Arry(['Good', 'Day', 'Morning']);
$f = new Arry([1,3,3,3,5]);

test(  1, 0 === $a->length() );
test(  2, 3 === $b->length );
test(  3, mustThrow(function () use (&$a) { $a->get(0); }));
test(  4, 3 === $d->get(1)->get(1) );
test(  5, '[1,[2,3],4]' === (string)$d );
test(  6, '["A","B",1032]' === (string)$c );
test(  7, '[3,2,1]' === (string)$b->sort('DESC') );
test(  8, '[1,2,3]' === (string)$b->sort('ASC') );
test(  9, '["Day","Good","Morning"]' === (string)$e->lsort('ASC') );
test( 10, '["Morning","Good","Day"]' === (string)$e->lsort('DESC') );
test( 11, 'Day' === $e->last() );
test( 12, 'Morning' === $e->first() );
test( 13, 'Good' === $e->at(1) );
test( 14, 1 === $e->indexOf('Good') );
test( 15, true === $e->has(2) );
test( 16, false === $e->has(3) );
test( 17, false === $e->has(-1) );
test( 18, '[1,3,5]' === (string)$f->unique() );
test( 19, '[1,3]' === (string)$f->slice(0, 2) );
test( 20, '[3,3,5]' === (string)$f->slice(-3, 10) );
test( 21, '[[1,3,3],[3,5]]' === (string)$f->slices(3) );
test( 22, '[5,3,3,3,1]' === (string)$f->reverse() );
test( 23, '[]' === (string)$b->clear() );
test( 24, '[]' === (string)$b );
test( 25, '1|3|3|3|5' === $f->join('|') );
test( 26, '1,[2,3],4' === $d->join(',') );
test( 27, 4 === $d->pop() );
test( 28, 1 === $d->shift() );
test( 29, '[[2,3]]' === (string)$d );
test( 30, '[[2,3],1,3,3,3,5]' === (string)$d->merge($f) );
test( 31, '[[2,3]]' === (string)$d );
$d->unshift('A');
test( 32, '["A",[2,3]]' === (string)$d );
$d->push('X');
test( 33, '["A",[2,3],"X"]' === (string)$d );
$d->add('X');
test( 34, '["A",[2,3],"X","X"]' === (string)$d );
$d->remove(1);
test( 35, '["A","X","X"]' === (string)$d );
$d->insertAt(0, 1);
test( 36, '[1,"A","X","X"]' === (string)$d );
$d->insertAt(-3, 1);
test( 36, '[1,"A",1,"X","X"]' === (string)$d );
test( 37, '[1,"A",1,"X","X",1,2]' === (string)$d->append(Arry::fromNativeArray([1,2])) );
test( 38, '[1,1,1,2]' === (string)$d->removeAll('/^[A-Z]/') );
test( 39, '[2]' === (string)$d->filter(function($value) { return $value > 1 ? true : false; }) );
$d->set(0, 'A');
test( 40, '["A",1,1,2]' === (string)$d );
test( 41, '["A",2]' === (string)$d->selectAll('/^[A-Z]|^2/') );
$d->clear()->push(Arry::fromNativeArray([1,2]))->push('X');
$e = $d->replicate();
test( 42, '[[1,2],"X"]' === (string)$e );
$e->get(0)->set(1, 3);
test( 43, '[[1,3],"X"]' === (string)$d );
$e = $d->replicate(true);
test( 44, '[[1,3],"X"]' === (string)$e );
$e->get(0)->set(1, 4);
test( 45, '[[1,3],"X"]' === (string)$d );
test( 46, '{"0":[1,3],"1":"X"}' === (string)$d->selectAllAsMap('//') );
test( 47, '["A!","B!","1032!"]' === (string)$c->map(function($i) { return ((string)$i).'!'; }) );
$c->merge(Arry::fromNativeArray(['X','Y']), true);
test( 48, '["A","B",1032,"X","Y"]' === (string)$c );
test( 49, 5 === $c->length );
test( 50, null === $c->{'@length'});
test( 51, 'X' === $c->{'@3'});
test( 52, true === $c->{'#3'});
test( 53, false === $c->{'#5'});
$c->push('A');
test( 54, 5 === $c->lastIndexOf('A'));
test( 55, false === $c->every(function($i) { return strlen($i) == 1; }));
test( 56, true === $c->every(function($i) { return Regex::_matches('/^[ABXY1]/', (string)$i) === true; }));
test( 57, true === $c->some(function($i) { return $i === 1032; }));
test( 58, true === $c->some(function($i) { return $i === 'Y'; }));
test( 59, false === $c->some(function($i) { return $i === 'Z'; }));

test( 60, 1032 === $c->find(function($i) { return strlen($i) == 4; }));
test( 61, 'X' === $c->find(function($i) { return $i > 'B'; }));
test( 62, null === $c->find(function($i) { return $i == 'Z'; }));
test( 63, 2 === $c->findIndex(function($i) { return $i === 1032; }));
test( 64, 4 === $c->findIndex(function($i) { return $i === 'Y'; }));
test( 65, null === $c->findIndex(function($i) { return $i === 'Z'; }));

test( 66, (string)(new Arry([ 1,2,[3,4],[5],[],[6,7],8 ]))->flatten() === '[1,2,3,4,5,6,7,8]'  );
test( 67, (string)(new Arry([ 1,2,[3,4],[5],"",6 ]))->flatten() === '[1,2,3,4,5,"",6]'  );
test( 68, (string)(new Arry([ 1,2,[3,[4,5]],[5],6 ]))->flatten() === '[1,2,3,[4,5],5,6]'  );
test( 69, (string)(new Arry([ 1,2,[3,[4,5]],[5],6 ]))->flatten(2) === '[1,2,3,4,5,5,6]'  );
test( 70, (string)(new Arry([ 1,2,[3,[["A"=>"A"],5]],[5],6 ]))->flatten(2) === '[1,2,3,{"A":"A"},5,5,6]'  );


/*
public function sortx ($comparator_fn)
public function forEach ($function)
*/

// *****************************************************************************
title('Map');

$a = new Map();
$b = new Map(['a'=>1,'b'=>3,'c'=>2]);
$c = new Map(['alpha'=>'A','beta'=>'B','gamma'=>'C']);
$d = new Map(['a'=>1,'b'=>3]);

test(  1, 0 === $a->length() );
test(  2, 3 === $b->length );
test(  3, '["a","b","c"]' === (string)$b->keys() );
test(  4, 3 === $b->get('b') );
test(  5, '{}' === (string)$a );
test(  6, '{"a":1,"b":3,"c":2}' === (string)$b );
test(  7, '{"b":3,"c":2,"a":1}' === (string)$b->sort('DESC') );
test(  8, '{"a":1,"c":2,"b":3}' === (string)$b->sort('ASC') );
test(  9, '{"alpha":"A","beta":"B","gamma":"C"}' === (string)$c->ksort('ASC') );
test( 10, '{"gamma":"C","beta":"B","alpha":"A"}' === (string)$c->ksort('DESC') );
test( 11, '[1,2,3]' === (string)$b->values() );
test( 12, '{"name":"Jon","last":"Doe"}' === (string)Map::fromCombination(['name', 'last'], new Arry(['Jon', 'Doe'])));
test( 13, 'C' === $c->get('gamma') );
test( 14, 'beta' === $c->keyOf('B') );
test( 15, true === $c->has('gamma') );
test( 16, false === $c->has('delta') );
test( 17, false === $c->has(-1) );
test( 18, '{}' === (string)$b->clear() );
test( 19, '{}' === (string)$b );
test( 20, '{"a":1,"b":3,"gamma":"C","beta":"B","alpha":"A"}' === (string)$d->merge($c) );
test( 21, '{"a":1,"b":3}' === (string)$d );
$d->c = 4;
test( 22, '{"a":1,"b":3,"c":4}' === (string)$d );
$d->set('c', 9);
test( 23, '{"a":1,"b":3,"c":9}' === (string)$d );
$d->remove(1);
test( 24, '{"a":1,"b":3,"c":9}' === (string)$d );
$d->remove('b');
test( 25, '{"a":1,"c":9}' === (string)$d );
test( 26, '{"a":1}' === (string)$d->removeAll('/^[2-9]/') );
$d->b = 2; $d->c = 3; $d->d = 4;
test( 27, '{"a":1}' === (string)$d->removeAll('/^[bcd]/', true) );
$d->b = 2; $d->c = 3; $d->d = 4;
test( 28, '[2]' === (string)$d->filter(function($value, $key) { return ($value & 1) == 0 && $key != 'd' ? true : false; }) );
test( 29, '{"a":1,"c":3}' === (string)$d->selectAll('/^[13]$/') );
test( 30, '{"c":3}' === (string)$d->selectAll('/^c$/', true) );

$d->clear()->set('a', Arry::fromNativeArray([1,2]));
$d->set('b', 'X');
$e = $d->replicate();

test( 31, '{"a":[1,2],"b":"X"}' === (string)$e );
$e->get('a')->set(1, 3);
test( 32, '{"a":[1,3],"b":"X"}' === (string)$d );
$e = $d->replicate(true);
test( 33, '{"a":[1,3],"b":"X"}' === (string)$e );
$e->get('a')->set(1, 4);
test( 34, '{"a":[1,3],"b":"X"}' === (string)$d );

test( 35, '{"gamma":"C!","beta":"B!","alpha":"A!"}' === (string)$c->map(function($i) { return ((string)$i).'!'; }) );
$c->merge(new Map(['a' => 'X', 'b' => 'Y']), true);
test( 36, '{"gamma":"C","beta":"B","alpha":"A","a":"X","b":"Y"}' === (string)$c );

test( 37, 5 === $c->length );
test( 38, null === $c->{'@length'});
test( 39, 'B' === $c->{'@beta'});
test( 40, true === $c->{'#gamma'});
test( 41, false === $c->{'#delta'});
test( 42, false === $c->has('@beta', true));

test( 43, '{"a":"X","b":"Y"}' === (string)$c->removeAll('/^[a-z][a-z]/', true));
test( 44, '{"a":"X","b":"Y","4":"x","5":"y"}' === (string)$c->merge(new Map([ '4' => 'x', '5' => 'y' ]), true));
test( 45, '{"a":"X","b":"Y"}' === (string)$c->removeAll('/^[0-9]/', true));
$c->merge(new Map([ '4' => 'x', '5' => 'y' ]), true);
test( 46, '{"a":"X","b":"Y","4":"Z","5":"y","7":"Z"}' === (string)$c->merge(new Map([ '4' => 'Z', '7' => 'Z' ])));

test( 47, true === $c->every(function($value, $key) { return Text::toUpperCase($value) === 'X' || Text::toUpperCase($value) === 'Y'; }) );
test( 48, false === $c->every(function($value, $key) { return Regex::_matches('/^[A-Za-z]$/', $key); }) );
test( 49, true === $c->some(function($value, $key) { return Regex::_matches('/^[0-9]$/', $key); }) );
test( 50, true === $c->some(function($value, $key) { return $value === 'Y'; }) );
test( 51, false === $c->some(function($value, $key) { return $value === 'Z'; }) );

// {"a":"X","b":"Y","4":"x","5":"y"}
test( 52, 'Y' === $c->find(function($value, $key) { return $value === 'Y'; }) );
test( 53, null === $c->find(function($value, $key) { return $key == 'Z'; }) );
test( 54, 'y' === $c->find(function($value, $key) { return $key == '5'; }) );

test( 55, null === $c->findKey(function($value, $key) { return $value == 'z'; }) );
test( 56, 'b' === $c->findKey(function($value, $key) { return $value === 'Y'; }) );
test( 57, '5' == $c->findKey(function($value, $key) { return $key == '5'; }) );

//public function forEach ($function)


// *****************************************************************************
title('Math');

test(  1, true === Math::inrange(4, -4, 4) );
test(  2, false === Math::inrange(4, -4, -4) );
test(  3, true === Math::inrange(-4, -4, -4) );
test(  4, 4 === Math::abs(4) );
test(  5, 4 === Math::abs(-4) );
test(  6, 4 == Math::round(4.49) );
test(  7, 5 == Math::round(4.51) );
test(  8, -5 == Math::round(-4.51) );
test(  9, 3 == Math::min(3, 5) );
test( 10, -5 == Math::min(3, -5) );
test( 11, 3 == Math::max(3, -5) );
test( 12, 3 == Math::max(3, 1) );
test( 13, 0.5 == Math::clamp(0.5, -1, 1) );
test( 14, 1 == Math::clamp(1.5, -1, 1) );
test( 15, -1 == Math::clamp(-1.5, -1, 1) );


// *****************************************************************************
title('Regex');

$a = new Regex('');
$b = new Regex('/[A-Z]+/');
$c = new Regex('/^.+?(?P<value>[0-9]+).+$/');
$d = new Regex('/[123]([0-9]+)/');

test(  1, mustThrow(function() use(&$a) { $a->matches('hello'); }) );
test(  2, false === $b->matches('hello') );
test(  3, true === $b->matches('oHe') );
test(  4, true === $c->matches('oH12e') );
test(  5, '12' === $c->matchFirst('oH12e')->get(1) );
test(  6, '12' === $c->matchFirst('oH12e')->value );
test(  7, 'oH12e' === $c->getString('oH12e', 0) );
test(  8, '12' === $c->getString('oH12e', 1) );
test(  9, '["","ello ","orld, ","his is ","ice!"]' === (string)$b->split('Hello World, This is Nice!'));
test( 10, '["ello ","orld, ","his is ","ice!"]' === (string)$b->split('Hello World, This is Nice!', Regex::NO_EMPTY));
test( 11, '!e!o! !ll !d?' === $b->replace('HeLLo! All GOOd?', '!'));
test( 12, '121221' === $d->extract('The number 12 is 12 and looks like 21.'));
test( 13, '2-2-1' === $d->extract('The number 12 is 12 and looks like 21.', '-', 1));
test( 14, '/[123]([0-9]+)/' === (string)$d);
test( 15, '[["123","23"],["211","11"],["390","90"]]' === (string)$d->matchAll('Some numbers are 123 and 211 and also 390.', true, Regex::SET_ORDER));
test( 16, '[["123","211","390"],["23","11","90"]]' === (string)$d->matchAll('Some numbers are 123 and 211 and also 390.', true, Regex::PATTERN_ORDER));
test( 17, '[["123","211","390"],["23","11","90"]]' === (string)$d->matchAll('Some numbers are 123 and 211 and also 390.', true, Regex::PATTERN_ORDER));
test( 18, '[[["123",17],["211",25],["390",38]],[["23",18],["11",26],["90",39]]]' === (string)$d->matchAll('Some numbers are 123 and 211 and also 390.', true, Regex::PATTERN_ORDER | Regex::OFFSET_CAPTURE));

test( 19, mustThrow(function() use(&$a) { Regex::_matches((string)$a, 'hello'); }) );
test( 20, false === Regex::_matches((string)$b, 'hello') );
test( 21, true === Regex::_matches((string)$b, 'oHe') );
test( 22, true === Regex::_matches((string)$c, 'oH12e') );
test( 23, '12' === Regex::_matchFirst((string)$c, 'oH12e')->get(1) );
test( 24, '12' === Regex::_matchFirst((string)$c, 'oH12e')->value );

test( 25, 'oH12e' === Regex::_getString((string)$c, 'oH12e', 0) );
test( 26, '12' === Regex::_getString((string)$c, 'oH12e', 1) );
test( 27, '["","ello ","orld, ","his is ","ice!"]' === (string)Regex::_split((string)$b, 'Hello World, This is Nice!'));
test( 28, '["ello ","orld, ","his is ","ice!"]' === (string)Regex::_split((string)$b, 'Hello World, This is Nice!', Regex::NO_EMPTY));
test( 29, '!e!o! !ll !d?' === Regex::_replace((string)$b, '!', 'HeLLo! All GOOd?'));
test( 30, '121221' === Regex::_extract((string)$d, 'The number 12 is 12 and looks like 21.'));
test( 31, '2-2-1' === Regex::_extract((string)$d, 'The number 12 is 12 and looks like 21.', '-', 1));
test( 32, '/[123]([0-9]+)/' === (string)$d);
test( 33, '[["123","23"],["211","11"],["390","90"]]' === (string)Regex::_matchAll((string)$d, 'Some numbers are 123 and 211 and also 390.', true, Regex::SET_ORDER));
test( 34, '[["123","211","390"],["23","11","90"]]' === (string)Regex::_matchAll((string)$d, 'Some numbers are 123 and 211 and also 390.', true, Regex::PATTERN_ORDER));
test( 35, '[["123","211","390"],["23","11","90"]]' === (string)Regex::_matchAll((string)$d, 'Some numbers are 123 and 211 and also 390.', true, Regex::PATTERN_ORDER));
test( 36, '[[["123",17],["211",25],["390",38]],[["23",18],["11",26],["90",39]]]' === (string)Regex::_matchAll((string)$d, 'Some numbers are 123 and 211 and also 390.', true, Regex::PATTERN_ORDER | Regex::OFFSET_CAPTURE));

// *****************************************************************************
title('Configuration');

$config = Configuration::getInstance();

test(  1, 'Test' === $config?->general?->name );
test(  2, null === $config?->general?->last );
test(  3, null === $config?->something?->name );
test(  4, "\n[general]\nname=Test\n\n[Gateway]\nsame_site=None\nallow_origin=*\n\n" === $config->saveToBuffer());


// *****************************************************************************
title('Cookies');

Cookies::set('name', 'value', 0, 'example.com');
test(  1, 'Set-Cookie: name=value; Domain=example.com; Path=test/; SameSite=None; HttpOnly' === Gateway::$header);

Cookies::remove('name');
test(  2, Regex::_matches('|^Set-Cookie: name=deleted; MaxAge=-1; Expires|', Gateway::$header));

// *****************************************************************************
title('Resources');

$res = Resources::getInstance();
$counter1 = 0;
$counter2 = 0;

$res->register('test', 'value');
$res->registerConstructor('test1', function() use (&$counter1) { $counter1++; return 'value1'; });
$res->registerConstructor('test2', function() use (&$counter2) { $counter2++; return 'value2'; }, true);
$res->test3 = 'value3';

test(  1, $res->exists('test') === true);
test(  2, $res->exists('test1') === true);
test(  3, $res->exists('test2') === true);
test(  4, $res->exists('test', true) === true);
test(  5, $res->exists('test1', true) === false);
test(  6, $res->exists('test2', true) === false);
test(  7, $res->test === 'value');
test(  8, $res->test1 === 'value1' && $res->test1 === 'value1' && $res->test1 === 'value1');
test(  9, $res->test2 === 'value2' && $res->test2 === 'value2' && $res->test2 === 'value2');
test( 10, $res->test3 === 'value3');
$res->unregister('test3');
test( 10, mustThrow(function() use(&$res) { $res->test3 === 'value3'; }));
test( 11, $res->exists('test3') === false);
$res->unregisterConstructor('test2');
test( 12, mustThrow(function() use(&$res) { $res->test2 === 'value2'; }));
test( 13, $res->exists('test2') === false);
test( 14, $counter1 === 1);
test( 15, $counter2 === 3);

// *****************************************************************************
title('Strings');

$str = Strings::getInstance();

test(  1, $str->lang === '.');
test(  2, $str->langFrom === Strings::FROM_NOWHERE);
test(  3, $str->base === './test/strings/');
test(  4, $str->altBase === './test/strings/');
test(  5, $str->langBase === './test/strings/./');
test(  6, $str->altLangBase === './test/strings/./');

$str->setLang('en');

test(  7, $str->base === './test/strings/');
test(  8, $str->altBase === './test/strings/');
test(  9, $str->langBase === './test/strings/en/');
test( 10, $str->altLangBase === './test/strings/en/');

test( 11, mustThrow(function() use(&$str) { $str->test3 === null; }));
test( 12, $str->test->name === 'Jon');
test( 13, $str->test->last === 'Doe');
test( 14, $str->test->info === null);
test( 15, $str->test->record->exists === 'true');
test( 16, $str->test2 === 'Hello_World');
test( 17, $str->test->code === null);
test( 18, $str->{'@test'}->code === 'EN');
test( 19, $str->{'@test2'} === 'Nice!!');

// *****************************************************************************
title('Session');

test(  1, Session::$sessionOpen === false);
test(  2, (string)Session::$data === '{}');
test(  3, Session::$sessionId === '');
test(  4, Session::$validSessionId === false);
test(  5, Session::$sessionName === null);

Configuration::getInstance()->Session = new Map(['name' => 'test']);
Session::init();

test(  6, Session::$sessionOpen === false);
test(  7, (string)Session::$data === '{}');
test(  8, Session::$sessionId === '');
test(  9, Session::$validSessionId === false);
test( 10, Session::$sessionName === 'test');

/*
public static function destroy()
public static function clear ()
public static function open ($createSession=true)
public static function close ($shallow=false)
public static function dbSessionLoad ($createSession)
public static function dbSessionSave ($shallow)
public static function dbSessionDelete ()
*/


// *****************************************************************************
title('Expr');

$data1 = new Map([ 'name' => '', 'name2' => null ]);
$data2 = new Map([ 'name' => 'A', 'name2' => 'B', 'name3' => ['value' => 'C'] ]);
$data3 = new Map([ 'name' => null, 'name2' => 'B' ]);

$t1 = Expr::parseTemplate ('Hello {name}!', '{', '}');
$t2 = Expr::parse ('Hello (name), /**/(name2) /**/and (name3.value).');
$t3 = Expr::compile ('Hello (name), /**/(name2) /**/and (name3.value).');

test(  1, Expr::expand($t1, $data1, 'text') === 'Hello !');
test(  2, Expr::expand($t1, $data2, 'text') === 'Hello A!');
test(  3, Expr::expand($t1, $data3, 'text') === 'Hello !');
test(  4, Expr::expand($t2, $data1, 'text') === 'Hello ,  and .');
test(  5, Expr::expand($t2, $data2, 'text') === 'Hello A, B and C.');
test(  6, Expr::expand($t2, $data3, 'text') === 'Hello , B and .');
test(  7, $t3 ($data1) === 'Hello ,  and .');
test(  8, $t3 ($data2) === 'Hello A, B and C.');
test(  9, $t3 ($data3) === 'Hello , B and .');
test( 10, Expr::eval('(eq? (name) A)', $data2) === '1');
test( 11, Expr::eval('(eq? (name) A)', $data2, 'arg') === true);
test( 12, Expr::eval('(eq? (name) B)', $data2) === '0');
test( 13, Expr::eval('(eq? (name) B)', $data2, 'arg') === false);
test( 14, Expr::eval('(null)', $data2, 'text') === '');
test( 15, Expr::eval('(null)', $data2, 'arg') === null);
test( 16, Expr::eval('(false)', $data2, 'text') === '0');
test( 17, Expr::eval('(false)', $data2, 'arg') === false);
test( 18, Expr::eval('(true)', $data2, 'text') === '1');
test( 19, Expr::eval('(true)', $data2, 'arg') === true);
test( 20, Expr::eval('(eqq? "true" (eq? 1 1))', $data2, 'arg') === false);
test( 21, Expr::eval('(eqq? true (eq? 1 1))', $data2, 'arg') === true);
test( 22, Expr::eval('(all i (# 1 2 3) (even? (i)))', $data2, 'arg') === false);
test( 23, Expr::eval('(all i (# -1 15 3) (odd? (i)))', $data2, 'arg') === true);
test( 24, Expr::eval('(all i (# 2 0 4) (even? (i)))', $data2, 'arg') === true);
test( 25, Expr::eval('(any i (# 2 4 0) (odd? (i)))', $data2, 'arg') === false);
test( 26, Expr::eval('(any i (# 2 4 0) (even? (i)))', $data2, 'arg') === true);
test( 27, Expr::eval('(array:index (# 2 4 0) 2)', $data2, 'arg') === 0);
test( 28, Expr::eval('(array:last-index (# 0 0 2 0 4) 0)', $data2, 'arg') === 3);
test( 29, Expr::eval('(all i (& a 1 b 2 c 3) (even? (i)))', $data2, 'arg') === false);
test( 30, Expr::eval('(all i (& a -1 b 15 c 3) (odd? (i)))', $data2, 'arg') === true);
test( 31, Expr::eval('(all i (& a 2 b 0 c 4) (even? (i)))', $data2, 'arg') === true);
test( 32, Expr::eval('(any i (& a 2 b 4 c 0) (odd? (i)))', $data2, 'arg') === false);
test( 33, Expr::eval('(any i (& a 2 b 4 c 0) (even? (i)))', $data2, 'arg') === true);
test( 34, Expr::eval('(all i (& a 1 b 2 c 3) (in? [a b c] (i#) ))', $data2, 'arg') === true);
test( 35, Expr::eval('(all i (& a -1 b 15 c 3) (in? [x b c] (i#) ))', $data2, 'arg') === false);
test( 36, Expr::eval('(any i (& a 2 b 4 c 0) (in? [x y z] (i#) ))', $data2, 'arg') === false);
test( 37, Expr::eval('(any i (& a 2 y 4 c 0) (in? [x y z] (i#) ))', $data2, 'arg') === true);
test( 38, Expr::eval('(find i (& a 2 y 4 c 0) (eq? (i#) y))', $data2, 'arg') === 4);
test( 39, Expr::eval('(find i (& a 2 y 4 c 0) (gt? (i) 6))', $data2, 'arg') === null);
test( 40, (string)Expr::eval('(find i (# (& v false) (& v true x Name) (&) (&)) (i.v))', $data2, 'arg') === '{"v":true,"x":"Name"}');
test( 41, Expr::eval('(find-index i (& a 2 y 4 c 0) (eq? (i#) y))', $data2, 'arg') === 'y');
test( 42, Expr::eval('(find-index i (& a 2 y 4 c 0) (gt? (i) 6))', $data2, 'arg') === null);
test( 43, Expr::eval('(find-index i (# (& v false) (& v true x Name) (&) (&)) (i.v))', $data2, 'arg') === 1);

test( 44, (string)Expr::eval('(array:flatten (# 1 2 (# 3 4) (# 5 6)) )', $data2, 'arg') === '[1,2,3,4,5,6]');
test( 45, (string)Expr::eval('(array:flatten (# 1 2 (# 3 4 (# 1 1 1)) (# 5 6)) )', $data2, 'arg') === '[1,2,3,4,[1,1,1],5,6]');
test( 46, (string)Expr::eval('(array:flatten 2 (# 1 2 (# 3 4 (# 1 1 1)) (# 5 6)) )', $data2, 'arg') === '[1,2,3,4,1,1,1,5,6]');
test( 47, (string)Expr::eval('(array:flatten 2 (# 1 2 (# 3 4 (& a 1)) (# 5 6)) )', $data2, 'arg') === '[1,2,3,4,{"a":1},5,6]');

// *****************************************************************************
title(null);
