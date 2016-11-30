<?php
/**
 * Created by PhpStorm.
 * User: jan.majek
 * Date: 28/11/2016
 * Time: 18:42
 */

// create curl resource
$ch = curl_init();

// set url
curl_setopt($ch, CURLOPT_URL, "http://next-elastic.ft.com/v3_api_v2/item/596ec790-afe8-11e6-9c37-5787335499a0");

//return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// $output contains the output string
$output = curl_exec($ch);

// close curl resource to free up system resources
curl_close($ch);


$article = json_decode($output, true);

print_r($article['_source']['bodyHTML']);

