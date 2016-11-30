<?php
/**
 * Created by PhpStorm.
 * User: jan.majek
 * Date: 28/11/2016
 * Time: 17:31
 */

require_once 'vendor/autoload.php';

$dynamo = new Aws\DynamoDb\DynamoDbClient([
	'credentials' => Aws\Credentials\CredentialProvider::ini(),
	'region' => 'eu-west-1',
	'version' => '2012-08-10',
]);

$result = $client->putItem([
    'Item' => [],
    'TableName' => '<string>',
]);



function createDynamoArray(array $in)
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
