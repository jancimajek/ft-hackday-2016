<?php

/**
 * Created by PhpStorm.
 * User: jan.majek
 * Date: 28/11/2016
 * Time: 23:13
 */
namespace Ft;

class ElasticSearchApi
{
	public function getItemByUuid($uuid) {
		// create curl resource
		$ch = curl_init();

		// set url
		curl_setopt($ch, CURLOPT_URL, "http://next-elastic.ft.com/v3_api_v2/item/" . $uuid);

		//return the transfer as a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// $output contains the output string
		$output = curl_exec($ch);

		// close curl resource to free up system resources
		curl_close($ch);

		return json_decode($output, true);
	}
}