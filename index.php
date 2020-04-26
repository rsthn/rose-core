<?php

require_once ('vendor/autoload.php');

use Rose\Arry;
use Rose\Map;
use Rose\Main;
use Rose\Configuration;
use Rose\Math;
use Rose\Regex;
use Rose\Cookies;
use Rose\DateTime;
use Rose\Strings;
use Rose\Gateway;
use Rose\Resources;
use Rose\Locale;

use Rose\Errors\Error;
use Rose\Errors\ArgumentError;
use Rose\Errors\UndefinedPropertyError;

use Rose\IO\Path;
use Rose\IO\Directory;
use Rose\IO\File;

use Rose\Data\Connection;

class TestClass
{
	public $value = 0;
	public $name = 'VERY GOOD';

	public function A()
	{
		return 'VALUE_A';
	}

	public function B()
	{
		return ++$this->value;
	}

	public function C()
	{
		return $this->name;
	}
};

$test = function()
{
	/* ************ TESTING STRINGS ************** */
	$Strings = Strings::getInstance();
	$Gateway = Gateway::getInstance();
	$Resources = Resources::getInstance();

	assert($Resources->LangSrc == 'CONFIG', 'LangSrc should be CONFIG');
	assert($Gateway->requestParams->lang == 'en', 'requestParams->lang should be en');
	assert($Strings->Def1->Name == 'Def1_Test', 'Strings Def1_Test');
	$Strings->setAltBase('resources/strings/en/');
	assert($Strings->Def2->Name == 'Def2_Test', 'Strings Def2_Test');
	$Strings->setAltBase(null);
	assert($Strings->{'/Def3'} == 'Def3_Test', 'Strings Def3_Test');
	try {
		$Strings->Def4;
		assert(false, 'Error was not raised on Strings->Def4');
	} catch (Error $e) {
	}

	/* ************ TESTING RESOURCES ************** */
	$test_class = new TestClass();

	$Resources->registerConstructor('A', $test_class, 'A');
	$Resources->registerConstructor('B', $test_class, 'B', true);
	$Resources->register('C', 'VALUE_C');

	assert($Resources->A == 'VALUE_A', 'Resources registerConstructor (non-dynamic)');
	assert($Resources->B == '1', 'Resources registerConstructor (dynamic) 1');
	assert($Resources->B == '2', 'Resources registerConstructor (dynamic) 2');
	assert($Resources->B == '3', 'Resources registerConstructor (dynamic) 3');
	assert($Resources->C == 'VALUE_C', 'Resources register');

	// VIOLET => Test XML Strings.

	/* ************ TESTING LOCALE ************** */
	$Locale = Locale::getInstance();
	$x = new DateTime('2020-04-25 01:25');

	assert($Locale->format('NUMBER', 123123.23123) == '123,123.23', 'Locale format NUMBER');
	assert($Locale->format('INTEGER', 3123.23123) == '3,123', 'Locale format NUMBER');
	assert($Locale->format('TIME', $x) == '01:25 AM', 'Locale format TIME');
	assert($Locale->format('DATE', $x) == '25/04/2020', 'Locale format DATE');
	assert($Locale->format('DATETIME', $x) == '25/04/2020 01:25', 'Locale format DATETIME');
	assert($Locale->format('GMT', $x) == 'Sat, 25 Apr 2020 01:25:00 GMT', 'Locale format GMT');
	assert($Locale->format('UTC', $x) == '2020-04-25T01:25:00Z', 'Locale format UTC');
	assert($Locale->format('ISO_DATE', $x) == '2020-04-25', 'Locale format ISO_DATE');
	assert($Locale->format('ISO_TIME', $x) == '01:25:00', 'Locale format ISO_TIME');
	assert($Locale->format('ISO_DATETIME', $x) == '2020-04-25 01:25:00', 'Locale format ISO_TIME');

	/* ********* TESTING CONNECTION ********** */
	$conn = Connection::fromConfig (Configuration::getInstance()->Database);

	if ($conn->execQuery('CREATE TABLE IF NOT EXISTS Test (name varchar(24), value varchar(24))'))
	{
		if ($conn->execScalar('SELECT COUNT(*) FROM Test') == '0')
		{
			if (!$conn->execQuery("INSERT INTO Test VALUES('red', '#ff0000')"))
				throw new Error($conn->getLastError());

			if (!$conn->execQuery("INSERT INTO Test VALUES('blue', '#00ff00')"))
				throw new Error($conn->getLastError());

			if (!$conn->execQuery("INSERT INTO Test VALUES('green', '#0000ff')"))
				throw new Error($conn->getLastError());
		}

		assert($conn->execScalar('SELECT COUNT(DISTINCT name) FROM Test') == '3');

		/*$reader = $conn->execReader('SELECT * FROM Test');
		echo 'Total: ' . $reader->count() . '<br/>';

		while ($reader->remaining())
		{
			$data = $reader->getAssoc();
			echo $reader->getIndex() . ': ' . $data . '<br/>';
		}*/
	}

	$conn->close();
};

Main::initialize($test);

/* ************ TESTING Arry ************** */
$a = new Arry(); 								assert((string)$a == '[]', 'Arry()');
$a = new Arry([1,2,3]); 						assert((string)$a == '[1, 2, 3]', 'Arry([1,2,3])');
$a = new Arry(1,'xx',123); 					assert((string)$a == '[1, "xx", 123]', 'Arry(1,xx,123)');
												assert($a->length()==3, 'Arry.length');
												assert($a->get(0)=='1', 'Arry.get');
$a->set(3, 'hello'); 							assert($a->get(3)=='hello', 'Arry.get');
$a->sort(); 									assert($a->get(0)=='hello', 'Arry.sort(ASC)');
$a->sort('DESC');								assert($a->get(3)=='hello', 'Arry.sort(DESC)');
$a->sortl();									assert($a->get(0)=='1', 'Arry.sortl(ASC)');
$a->sortl('DESC');								assert($a->get(0)=='hello', 'Arry.sortl(DESC)');
$a->sortx( function($a,$b) { return strlen($b)-strlen($a); });	assert($a->get(0)=='hello', 'Arry.sortx()');
												assert($a->first()=='hello', 'Arry.first()');
												assert($a->last()=='1', 'Arry.last()');
												assert($a->last(1)=='xx', 'Arry.last(1)');
												assert($a->at(-3)=='123', 'Arry.at(-2)');
$a->push('world');								assert($a->at(-1)=='world', 'Arry.push(world)');
												assert($a->pop()=='world', 'Arry.pop()');
$a->unshift('world');							assert($a->at(0)=='world', 'Arry.unshift(world)');
												assert($a->shift()=='world', 'Arry.shift()');
$a->add('world');								assert($a->at(-1)=='world', 'Arry.add(world)');
												assert($a->remove(0)=='hello', 'Arry.remove(0)');
$b = $a->selectAllAsMap('/[0-9]/');				assert($b->size==2, 'Arry.selectAllAsMap');
$b = $a->selectAll('/[0-9]/');					assert($b->size==2, 'Arry.selectAll');
$a->removeAll('/[0-9]/');						assert($a->at(0)=='xx', 'Arry.removeAll');
$a->insertAt(1, 'nice');						assert($a->at(1)=='nice', 'Arry.insertAt');
												assert($a->indexOf('world')==2, 'Arry.indexOf');
$b = $a->replicate();							assert((string)$a == (string)$b, 'Arry.replicate');
												assert((string)$a->slice(2) == '["world"]', 'Arry.slice');
												assert((string)$a->slices(2)->at(1)->length() == '1', 'Arry.slices');
												assert($a->merge($a)->length()==6, 'Arry.merge');
$a->concat($a);									assert($a->length()==6, 'Arry.concat');
$b = $a->unique();								assert($a->length()==6 && $b->length()==3, 'Arry.unique');
$b = $a->reverse();								assert($b->at(0) == 'world', 'Arry.reverse');
												assert($a->join(':')=='xx:nice:world:xx:nice:world', 'Arry.join');
$b->clear();									assert($b->size == 0, 'Arry.clear');
try { $b->get(8); assert(false, 'Arry.get expect ArgumentError'); } catch (ArgumentError $e) { }
try { $b->{'asd'}; assert(false, 'Arry.__get expect UndefinedPropertyError'); } catch (UndefinedPropertyError $e) { }


/* ************ TESTING MAP ************** */
$a = new Map(); 												assert((string)$a == '{}', 'Map()');
$a = Map::fromNativeArray(['name'=>'jon']); 					assert((string)$a == '{"name": "jon"}', 'Map(...)');
$a = Map::fromCombination(['last','name'],['beta','alpha']); 	assert((string)$a == '{"last": "beta", "name": "alpha"}', 'Map(...)');
$a->sort(); 													assert((string)$a == '{"name": "alpha", "last": "beta"}', 'Map.sort(ASC)');
$a->sort('DESC');												assert((string)$a == '{"last": "beta", "name": "alpha"}', 'Map.sort(DESC)');
$a->sortk(); 													assert((string)$a == '{"last": "beta", "name": "alpha"}', 'Map.sortk(ASC)');
$a->sortk('DESC');												assert((string)$a == '{"name": "alpha", "last": "beta"}', 'Map.sortk(DESC)');
																assert($a->length() == 2, 'Map.length');
																assert($a->get('name') == 'alpha', 'Map.get');
																assert($a->has('middle') === false, 'Map.has');
																assert((string)$a->keys() == '["name", "last"]', 'Map.elements');
																assert((string)$a->values() == '["alpha", "beta"]', 'Map.values');
$b = $a->replicate();											assert($b->length() == 2, 'Map.replicate');
$a->clear();													assert($b->length() == 2 && $a->length() == 0, 'Map.clear');
																assert($b->keyOf('alpha')=='name', 'Map.keyOf');
																assert($b->has('last')===true, 'Map.has');
$a->set('middle','x');											assert($a->has('middle')===true, 'Map.set');
																assert($a->get('middle')=='x', 'Map.get');
$c = $a->merge($b);												assert($c->size == 3, 'Map.merge');
$a->removeAll('/^x$/');											assert($a->size == 0, 'Map.removeAll');
$c->name = 'jon';												assert($c->get('name') == 'jon', 'Map.__set');
																assert($c->name == 'jon', 'Map.__get');
																assert($a->get(8) === null, '_Map.get null');


/* ************ TESTING CONFIGURATION ************** */
$c = Configuration::getInstance();
																assert($c->General->title == 'System', 'Configuration.get normal');
																assert($c->General->desc1 == 'Hello=World', 'Configuration.get with equal');
																assert($c->General->desc2 == "Hello\n\nWorld", 'Configuration.get multiline');
$d = new Map();
$d->main = new Map();
$d->main->name = "John=Doe";
$d->main->last = "Hello\nWorld";
$d->main->temp = "1020";

assert(Configuration::saveToBuffer($d) == "\n[main]\nname=John=Doe\nlast=`\nHello\nWorld\n`\ntemp=1020\n\n", 'Configuration.saveToBuffer');

/* ************ TESTING MATH ************** */
assert(Math::inrange (2, 3, 10) === false, 'Math.inrange / false');
assert(Math::inrange (4, -3, 10) === true, 'Math.inrange / true');
assert(Math::rand() != Math::rand(), 'Math.rand');
assert(Math::randmax() != 0, 'Math.randmax');
assert(Math::abs (4) == 4, 'Math.abs / a');
assert(Math::abs (-4) == 4, 'Math.abs / b');
assert(Math::min (-4, 2) == -4, 'Math.min / a');
assert(Math::min (4, 2) == 2, 'Math.min / b');
assert(Math::max (-4, 2) == 2, 'Math.max / a');
assert(Math::max (4, 2) == 4, 'Math.max / b');
assert(Math::truncate (40, -4, 4) == 4, 'Math.truncate / a');
assert(Math::truncate (-5, -4, 4) == -4, 'Math.truncate / b');
assert(Math::truncate (2, -4, 4) == 2, 'Math.truncate / c');

/* ************ TESTING REGEX ************** */
$a = new Regex("/h(.+?) ((.+?)ld)/i");
assert($a->matches('hello world') === true, 'Regex.matches / a');
assert($a->matches('world') === false, 'Regex.matches / b');
assert($a->matchFirst('hello world, this hello mold.')->get(2) == 'world', 'Regex.matchFirst');
assert($a->getString('hello world, this hello mold.') == 'hello world', 'Regex.getString / a');
assert($a->getString('hello also hello mold.', 2) == 'also hello mold', 'Regex.getString / b');
assert($a->matchAll('hello world nice hello mold', 2) == '["world", "mold"]', 'Regex.matchAll');
assert($a->split('hello world nice hello world')->get(1) == ' nice ', 'Regex.split');
assert($a->replace('hello world nice hello cold', '$2') == 'world nice cold', 'Regex.replace');
assert($a->extract('hello world nice hello cold', ';', 2) == 'world;cold');

/* ************ TESTING DATETIME ************** */
$a = new DateTime('2020-03-25 14:25');
$b = new DateTime();

assert($a->format('datetime') == '2020-03-25 14:25:00', 'DateTime format datetime');
assert($a->format('date') == '2020-03-25', 'DateTime format date');
assert($a->format('time') == '14:25:00', 'DateTime format time');

assert($b->add($a->sub($b)) == '2020-03-25 14:25:00', 'DateTime add+sub');
assert($a->sub('2020-03-24 14:24:30') == 86430, 'DateTime sub from str date');
assert(new DateTime($a->getTimestamp()) == '2020-03-25 14:25:00', 'DateTime from Unix');

exit;

/* ************ TESTING PATH, DIRECTORY AND FILE ************** */
$path = 'C:/Users/Master/../Master/Desktop/allsource/root/lib/rfw3/index.php';

assert(Path::basename($path) == 'index.php', 'Path::basename');
assert(Path::name($path) == 'index', 'Path::name');
assert(Path::extname($path) == '.php', 'Path::extname');
assert(Path::dirname($path) == 'C:/Users/Master/../Master/Desktop/allsource/root/lib/rfw3', 'Path::dirname');
assert(Path::resolve($path) == 'C:/Users/Master/Desktop/allsource/root/lib/rfw3/index.php', 'Path::path');
assert(Path::is_file($path) === true, 'Path::isFile');
assert(Path::is_dir(Path::dirname($path)) === true, 'Path::isDir');
assert(Path::exists($path) === true, 'Path::exists / a');
assert(Path::exists(Path::dirname($path)) === true, 'Path::exists / b');

assert(Directory::create("A/B/C/D", true) === true, 'Directory::create / a');
assert(Path::exists('A/B/C/D'), 'Directory::create / b');

assert(Path::normalize('c:\\wamp\\www\\') == 'c:/wamp/www', 'Path::normalize');
assert(Path::normalize('c:\\wamp/www\\info.txt') == 'c:/wamp/www/info.txt', 'Path::normalize');
assert(Path::append('c:\\wamp\\www\\', 'B\\C/', 'D/E/') == 'c:/wamp/www/B/C/D/E', 'Path::append');

assert(File::copy('test.txt', 'A/B/C/D') === true, 'File::copy / a');
assert(File::copy('test.txt', 'A/B/C/D/test2.txt') === true, 'File::copy / b');
assert(File::getContents('A/B/C/D/test2.txt') == File::getContents('A/B/C/D/test.txt'), 'File::copy / c');
assert(File::create('A/B/C/D/test2.txt') === true, 'File::create / a');
assert(File::size('A/B/C/D/test2.txt') == 0, 'File::create / b');

assert(File::size('test.txt') == 11, 'File::size');

assert(Directory::copy('A', 'X') === true, 'Directory::copy');

assert(sha1(Directory::readFiles('A', true)) == '27658f49aa2c30df4b6496af9289a5c1311c7a72', 'Directory::readFiles');
assert(sha1(Directory::readDirs('A', true)) == '79fd220cd7f72349529e0870dae92616e2b5824b', 'Directory::readDirs');

$a = Directory::read('X', true, '/./', 2);
$a->remove('name');
$b = Directory::read('A', true, '/./', 2);
$b->remove('name');

assert((string)$a == (string)$b, 'Directory::read + Directory::copy');
assert(Directory::remove('A/B/C/D') === false, 'Directory::remove / a');
assert(Directory::remove('A/B/C/D/test.txt') === true, 'Directory::remove / a');
assert(Directory::remove('A/B/C/D/test2.txt') === true, 'Directory::remove / b');
assert(Directory::remove('A/B/C/D') === true, 'Directory::remove / c');
assert(Directory::remove('A', true) === true, 'Directory::remove / d');
assert(Directory::remove('X', true) === true, 'Directory::remove / e');
assert(!Path::exists('A') && !Path::exists('X'), 'Directory::remove / f');

ob_start();
File::dump('test.txt');
assert (ob_get_clean() == 'HELLO_WORLD', 'File::dump');

assert(File::getContents('test.txt') == 'HELLO_WORLD', 'File::getContents');
File::setContents('test2.txt', 'BYE_WORLD');
assert(File::getContents('test2.txt') == 'BYE_WORLD', 'File::setContents');
File::appendContents('test2.txt', '!');
assert(File::getContents('test2.txt') == 'BYE_WORLD!', 'File::appendContents');
File::remove('test2.txt');
assert(Path::exists('test2.txt') === false, 'File::remove / a');
assert(File::remove('test2.txt') === true, 'File::remove / b');

//assert(Directory::remove("C:/Temp/A/", true) === true);
