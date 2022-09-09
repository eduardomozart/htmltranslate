<?php

$rootdir = '.';
$sourcejsonfile = 'en.json'
$targetjsonfile = 'pt.json'

chdir($rootdir);

echo "Current directory: " . getcwd() . PHP_EOL;

$json = trim(file_get_contents($sourcejsonfile), "\xEF\xBB\xBF");

$assoc = json_decode($json, true);

// var_dump($assoc);
// exit();

function extractText($item, $key)
{
  $fp = fopen('data_json.txt', 'a'); // Open without overwriting contents

  // echo "$key holds $item\n";

  $message = escapeText($item);

  echo "Message: " . $message;

  if(validateText($message)) {
    // fwrite($fp, "# " . $key . "\r\n");
    fwrite($fp, $message . "\r\n");
  }

  fclose($fp);
}

function translateText(&$item, $key) {
  global $counter;

  $tfp = explode("\r\n", file_get_contents('data_trans_json.txt'));
  /*
  // Remove lines that starts with hashtag (comments)
  $tfp = preg_replace(
    "/^\#(.*)$/m",
    "",
    $tfp);
  // Remove empty array entries and reindex the array
  $tfp = array_values(array_filter($tfp));
  */

  if(validateText($item)) {
      /* if (strpos($tfp[$counter], 'test') !== false) {
          echo "Item: " . $item . PHP_EOL;
          echo "Translated text: " . $tfp[$counter];
          exit();
      } */

      $item = $tfp[$counter];
      $counter++;
  }
}

function validateText($message) {
  if(trim($message) && !(is_numeric($message))) {
    return true;
  }
  return false;
}

function escapeText($item) {
  return str_replace(["\\","\r","\n"],["\\\\", "\\r","\\n"],$item);
}

if (!(file_exists('data_trans_json.txt'))) {
  $fp = fopen('data_json.txt', 'w'); // Open overwriting contents
  fwrite($fp, pack("CCC",0xef,0xbb,0xbf)); // Create file with "UTF-8 with BOM" encoding
  fclose($fp);

  array_walk_recursive($assoc, 'extractText');
} else {
  $counter = 0;
  array_walk_recursive($assoc, 'translateText');

  // https://stackoverflow.com/questions/2934563/how-to-decode-unicode-escape-sequences-like-u00ed-to-proper-utf-8-encoded-cha
  $assoc_decode = json_encode($assoc);
  $assoc_decode = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}, $assoc_decode);

  $jfp = fopen($targetjsonfile, 'w');
  fwrite($jfp, $assoc_decode);
  fclose($jfp);
}

// var_dump($assoc);

/*
foreach ($assoc as $key => $value) {
    echo "The value of key '$key' is '$value'", PHP_EOL;
}*/

/*
    // if (!file_exists('data_trans.txt')) {
    if(!file_exists('data.txt')) {
        $fp = fopen('data.txt', 'w'); // Open overwriting contents
        fwrite($fp, pack("CCC",0xef,0xbb,0xbf)); // Create file with "UTF-8 with BOM" encoding
    } elseif (file_exists('data_trans.txt')) {
        $tfp = explode("\n", file_get_contents('data_trans.txt'));
        // Remove lines that starts with hashtag (comments)
        $tfp = preg_replace(
            "/^\#(.*)$/m",
            "",
            $tfp);
        // Remove empty array entries and reindex the array
        $tfp = array_values(array_filter($tfp));
        // var_dump($tfp);
        // exit();
        $counter = 0;
    }

	$html = file_get_contents($file);
	        $dom = new DomDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

		                if(trim($text->nodeValue)) {
                    fwrite($fp, $text->nodeValue . "\n");
                }
*/
