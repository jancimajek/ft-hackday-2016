<?php
/**
 * Created by PhpStorm.
 * User: jan.majek
 * Date: 28/11/2016
 * Time: 18:31
 */
require_once 'vendor/autoload.php';
require('./wordnik/wordnik/Swagger.php');

$myAPIKey = '2ce6a922644282b5513030053cc0b929aea3b632fad6733ee';
$client = new APIClient($myAPIKey, 'http://api.wordnik.com/v4');

$wordApi = new WordApi($client);
//$result = $wordApi->getDefinitions('simplify');
$result = $wordApi->getRelatedWords('love', 'synonym');
print_r($result);

//$dynamoResult = createDynamoArray($result[0]);
//print_r($dynamoResult);


$dynamo = new Aws\DynamoDb\DynamoDbClient([
	'credentials' => Aws\Credentials\CredentialProvider::ini(),
	'region' => 'eu-west-1',
	'version' => '2012-08-10',
]);

//$res = $dynamo->putItem([
//	'Item' => [ // REQUIRED
//		'term' => ['S' => 'love'],
//		'wordnik_json' => ['S' => json_encode($result)],
//	],
//	'TableName' => 'synonyms',
//]);
//print_r($res);

$res = $dynamo->getItem([
	'Key' => [ // REQUIRED
		'term' => ['S' => 'love'],
	],
	'TableName' => 'synonyms',
]);
print_r($res);

var_dump($res->hasKey('Item'));
var_dump($res->get('Item'));



function createDynamoArray($in)
{
	// Encode and decode the array so that assoc arrays will become objects
	return dynamify(json_decode(json_encode($in)));
}

function dynamify($arr) {
	$ret = [];

	if (!is_array($arr) && !is_object($arr)) {
		$arr = [];
	}

	$arr = (is_array($arr) || is_object($arr)) ? $arr : [];

	foreach ($arr as $k => $v) {
		if (is_null($v)) {
			$ret[$k] = ['NULL' => true];
			continue;
		}

		if (is_bool($v)) {
			$ret[$k] = ['BOOL' => $v];
			continue;
		}

		if (is_int($v) || is_float($v)) {
			$ret[$k] = ['N' => $v];
			continue;
		}

		if (is_string($v)) {
			$ret[$k] = ['S' => $v];
			continue;
		}

		if (is_array($v)) {
			$ret[$k] = ['L' => dynamify($v)];
			continue;
		}

		if (is_object($v)) {
			$ret[$k] = ['M' => dynamify($v)];
			continue;
		}
	}

	return $ret;
}
