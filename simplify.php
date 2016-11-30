<?php
/**
 * Created by PhpStorm.
 * User: jan.majek
 * Date: 28/11/2016
 * Time: 22:40
 */
require_once 'vendor/autoload.php';
require('./wordnik/wordnik/Swagger.php');

//echo getDefinition('love');
//exit;


$uuid = '596ec790-afe8-11e6-9c37-5787335499a0';

// Fetch article form ES
$es = new Ft\ElasticSearchApi();
$article = $es->getItemByUuid($uuid);
$bodyHTML = $article['_source']['bodyHTML'];

// Parse out all HTML tags
$htmlTags = [];
preg_match_all("/<(?:.|\n)*?>/m", $bodyHTML, $htmlTags);
$htmlTags = $htmlTags[0];
echo 'Tags: '. count($htmlTags) . PHP_EOL . PHP_EOL;

// Replace all HTML tags with placeholders
$bodyNoHTML = preg_replace_callback(
	"/<(?:.|\n)*?>/m",
	function ($m) {
		static $i = 0;
		return ' $tag::' . (int)$i++ . ' ';
	},
	$bodyHTML
);
$bodyNoHTML = str_replace(['“', '”', '’', '…'], ['"', '"', "'", '...'], $bodyNoHTML);

$words = explode(' ', $bodyNoHTML);

// Load word list
$listFile = 'google-10000-english-master/google-10000-english-no-swears.txt';
$simpleWords = array_flip(file($listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
ksort($simpleWords);

// Process words
$notSimple = [];
$simplified = [];
$replacedSynonyms = [];

foreach ($words as $word) {
	// If it's a HTML tag placeholder, convert it back to the original HTML tag
	if (strpos($word, '$tag::') === 0) {
		list($t, $i) = explode('::', $word);
		$simplified[] = $htmlTags[$i];
		continue;
	}

	// If ignorable or simple word, carry on
	if (shouldIgnore($word, $simpleWords)) {
		$simplified[] = $word;
		continue;
	}

	// Split word by punctuation
	$wordParts = preg_split('/([\.,;:\-\'"\[\]\(\)\/\\\])/', $word, -1, PREG_SPLIT_DELIM_CAPTURE);

	if (count($wordParts) < 2) {
		$notSimple[] = cleanWord($word);
		$simplified[] = notSimple($word, $simpleWords);
	} else {
		$simplified[] = processWordParts($wordParts, $simpleWords);
	}

}

function processWordParts(array $wordParts, $simpleWords)
{
	$simplified = [];
	foreach ($wordParts as $word) {
		// If ignorable or simple word, carry on
		if (shouldIgnore($word, $simpleWords)) {
			$simplified[] = $word;
			continue;
		}

		$GLOBALS['notSimple'][] = $word;
		$simplified[] = notSimple($word, $simpleWords);
	}

	// @todo SYNONYMS HERE

	return implode('', $simplified);
}

function shouldIgnore($word, $simpleWords)
{
	$word = cleanWord($word);
	// Ignore empty strings, numbers and single letters
	return (
		strlen($word) <= 1 ||                    // empty or single letter string
		is_numeric($word) ||                     // numbers
		ucfirst($word) === $word ||              // words with capital first letters
		isset($simpleWords[strtolower($word)])   // simple words
	);
}

function cleanWord($word)
{
	// Trim all white spaces and punctuation from beginning and end of the word
	return trim(
		trim(
			trim($word),
			'.,;:-\'"[]()/\\?!$£€…'
		)
	);
}


function notSimple($word, $simpleWords) {
	// Get synonyms for the word
	$synonyms = getSynonyms($word);
	$definition = getDefinition($word);

	foreach ($synonyms as $synonym) {
		// If synonym is simple word:
		if (shouldIgnore($synonym, $simpleWords)) {
			$GLOBALS['replacedSynonyms'][] = $word . ' -> ' . $synonym;
			// Replace it
			return notSimpleTooltip(
				$synonym,
				'<strong>' . $word . '</strong><br/><em>' . $definition . '</em><br/><br/> <em>Synonyms:</em> ' . implode(', ', $synonyms) . '.'
			);
		}
	}

	$GLOBALS['replacedSynonyms'][] = $word . ' -> X :(';

	// No synonyms are simple words
	return notSimpleTooltip(
		$word,
		'<strong>' . $word . '</strong><br/><em>' . $definition . '</em><br/><br/> <em>Synonyms:</em> ' . implode(', ', $synonyms) . '.'
	);
}

function notSimpleTooltip($word, $msg = '') {
//	return 'NOTSIMPLE(' . $word . ')';
	return '<span class="tooltip">' . $word . '<span class="tooltiptext">' . $msg . '</span></span>';
}


function getSynonyms($word)
{
	$dynamo = new Aws\DynamoDb\DynamoDbClient([
		'credentials' => Aws\Credentials\CredentialProvider::ini(),
		'region' => 'eu-west-1',
		'version' => '2012-08-10',
	]);

	// Get from dynamoDB cache:
	$res = $dynamo->getItem([
		'Key' => [ // REQUIRED
			'term' => ['S' => $word],
		],
		'TableName' => 'synonyms',
	]);

	if ($res->hasKey('Item')) {
		echo 'Cache HIT:  ' . $word . PHP_EOL;
		$item = $res->get('Item');
		$wordnikJson = json_decode($item['wordnik_json']['S']);

	} else {
		echo 'Cache MISS: ' . $word . PHP_EOL;

		$myAPIKey = '2ce6a922644282b5513030053cc0b929aea3b632fad6733ee';
		$client = new APIClient($myAPIKey, 'http://api.wordnik.com/v4');

		$wordApi = new WordApi($client);
//			$result = $wordApi->getDefinitions('simplify');
		$wordnikJson = $wordApi->getRelatedWords($word, 'synonym');


		$res = $dynamo->putItem([
			'Item' => [ // REQUIRED
				'term' => ['S' => $word],
				'wordnik_json' => ['S' => json_encode($wordnikJson)],
			],
			'TableName' => 'synonyms',
		]);
	}


	if (!is_array($wordnikJson) || !isset($wordnikJson[0]) || !is_object($wordnikJson[0]) || !isset($wordnikJson[0]->words)) {
		$synonyms = [];
	} else {
		$synonyms = $wordnikJson[0]->words;
	}

	if (!is_array($synonyms)) $synonyms = [$synonyms];

	print_r($synonyms);

	return $synonyms;
}


function getDefinition($word)
{
	$dynamo = new Aws\DynamoDb\DynamoDbClient([
		'credentials' => Aws\Credentials\CredentialProvider::ini(),
		'region' => 'eu-west-1',
		'version' => '2012-08-10',
	]);

	// Get from dynamoDB cache:
	$res = $dynamo->getItem([
		'Key' => [ // REQUIRED
			'term' => ['S' => $word],
		],
		'TableName' => 'definitions',
	]);

	if ($res->hasKey('Item')) {
		echo 'Cache HIT:  ' . $word . PHP_EOL;
		$item = $res->get('Item');
		$definition = $item['definition']['S'];
		echo $definition . PHP_EOL;

	} else {
		echo 'Cache MISS: ' . $word . PHP_EOL;

		$myAPIKey = '2ce6a922644282b5513030053cc0b929aea3b632fad6733ee';
		$client = new APIClient($myAPIKey, 'http://api.wordnik.com/v4');

		$wordApi = new WordApi($client);
		$wordnikJson = $wordApi->getDefinitions($word);


		if (is_array($wordnikJson) && isset($wordnikJson[0]) && is_object($wordnikJson[0])) {
			$definition = '(' . $wordnikJson[0]->partOfSpeech . ') ' . $wordnikJson[0]->text;
		} else {
			$definition = '';
		}
		echo $definition . PHP_EOL;

		$putItem = [
			'Item' => [ // REQUIRED
				'term' => ['S' => $word],
				'definition' => (empty($definition) ? ['NULL' => true] : ['S' => $definition]),
			],
			'TableName' => 'definitions',
		];

		$res = $dynamo->putItem($putItem);
	}

	return $definition;
}



echo implode(' ', $simplified);
print_r($notSimple);
print_r($replacedSynonyms);
echo count($notSimple) . PHP_EOL;
