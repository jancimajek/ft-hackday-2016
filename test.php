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

$result = $dynamo->getItem([
	'AttributesToGet' => ['word'],
    'ConsistentRead' => false,
    'Key' => [ // REQUIRED
	'word' => [
            'S' => 'simple',
        ],
        // ...
    ],
    'TableName' => '10k-list', // REQUIRED
]);

print_r($result);