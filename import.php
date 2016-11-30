<?php
/**
 * Created by PhpStorm.
 * User: jan.majek
 * Date: 28/11/2016
 * Time: 17:31
 */

require_once 'vendor/autoload.php';

die('Disabled' . PHP_EOL);

$listFile = 'google-10000-english-master/google-10000-english-no-swears.txt';
$dynamo = new Aws\DynamoDb\DynamoDbClient([
	'credentials' => Aws\Credentials\CredentialProvider::ini(),
	'region' => 'eu-west-1',
	'version' => '2012-08-10',
]);

$lines = file($listFile);

$batch = [];
$processed = 0;
$total = count($lines);

echo '[' . $processed . '/' . $total . '] ';
foreach ($lines as $line) {
	if (trim($line) === '') continue;

	$batch[] = [
		'PutRequest' => [
			'Item' => [ // REQUIRED
				'word' => [
					'S' => trim($line),
				],
				// ...
			],
		],
	];
	echo '.';
	$processed++;

	if (count($batch) === 25) {
		echo '[25] ';
		$dynamo->batchWriteItem([
			'RequestItems' => [ // REQUIRED
				'10k-list' => $batch,
			],
		]);

		echo '[written]' . PHP_EOL;
		echo '[' . $processed . '/' . $total . '] ';
		$batch = [];
	}
}

if (count($batch) > 0) {
	echo '[' . (int)count($batch) . '] ';
	$dynamo->batchWriteItem([
		'RequestItems' => [ // REQUIRED
			'10k-list' => $batch,
		],
	]);

	echo '[written]' . PHP_EOL;
}

