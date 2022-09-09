<?php
// TranslateHTML ver. 0.0.1
// Requires ''php-intl'' and ''php-mbstring'' packages.
// Install them through ''sudo apt-get update && sudo apt-get install php-intl php-mbstring'' (Debian-based distros).
// The latest Ruffle binary release compatible with Debian 10 "Buster" is Ruffle 2020-12-11.

$rootdir = '.';

// You can change the dot (.) below to any other path where this script will look files to translate.
// E.g: /home/Eduardo/HTML
$dirlist = getFileList($rootdir);
// print_r($dirlist);
// exit();

foreach($dirlist as $dir) {
    if(($dir['type']) !== "dir") { continue; };
    // Limit Translation to the folder named "test"
    // if(strpos($dir['name'], "teste") == false) { continue; };
    echo $dir['name'] . "\r\n";
    // chdir(os_path_join(__DIR__, $dir['name']));
    chdir($dir['name']);

    // RPGM game
    if (file_exists('Game.exe')) {
      echo "RPGM game. Skipping.\r\n";
      continue;
    }

    // File to translate exists but isn't translated yet.
    if (file_exists('data.txt')) {
      // echo "File " . os_path_join($dir['name'], 'data.txt') . " exists.\r\n";
      if (!file_exists('data_trans.txt')) {
          echo "File to translate exists but isn't translated yet. Skipping.\r\n";
          continue;
        }
    }

    $files = glob_recursive("*.htm*");
    //var_dump($files);

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

    foreach ($files as $file) {
        $pathinfo = pathinfo($file);
        $source_file = os_path_join($pathinfo['dirname'], $pathinfo['filename'] . '_orig.' . $pathinfo['extension']);
        if (strpos($pathinfo['dirname'], 'flash') !== false) { /* echo "Directory contains 'flash'. Skipping.\r\n"; */ continue; }
        if (substr($pathinfo['filename'], -3) == '_orig') { /* echo "Original file from already translated content. Skipping.\r\n"; */ continue; }
        if (file_exists($source_file)) { /* echo "File " . $source_file . " already exists. Skipping.\r\n"; */ continue; }

        if (!file_exists('data_trans.txt')) {
            echo "\r\nExtracting text to translate from " . os_path_join($dir['name'], $file) . "\r\n";
            fwrite($fp, "# " . os_path_join($file) . "\r\n");
        } else {
          echo "\r\nTranslating " . os_path_join($dir['name'], $file) . "\r\n";
        }

        //
        $html = file_get_contents($file);
        if (mb_detect_encoding($html, "SJIS-win, UTF-8") == "SJIS-win") {
            $htmlenconding = "SJIS-win";
            $html = mb_convert_encoding($html, "UTF-8", "SJIS-win");
            echo "Encoding: SJIS-win.\r\n";
        } else {
            echo "Encoding: UTF-8.\r\n";
        }
        // echo $html;

        libxml_use_internal_errors(true);
        $dom = new DomDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//text()[
            not(ancestor::script) and
            not(normalize-space(.) = "")
        ]') as $text) {
            /* if (trim(translateText('ja', 'en', utf8_decode($text->nodeValue)))) {
                $text->nodeValue = $output;
            } else {
                echo "Google Translator limit probably exceeded.\r\n";
                exit();
            } */

            // Remove Tabs characters
            $text->nodeValue = trim(str_replace('　', '', utf8_decode($text->nodeValue)));
            // data.txt is open, so we are generating the source translation file.
            if (@is_resource($fp)) {
                //echo $text->nodeValue;
                //echo strlen($text->nodeValue);
                //sleep(1);

                // Create translation file
                if(trim($text->nodeValue)) {
                    fwrite($fp, $text->nodeValue . "\r\n");
                }
            } elseif (file_exists('data_trans.txt')) {
                // Translate
                if (array_key_exists($counter, $tfp)) {
                    if(trim($text->nodeValue)) {
                        $text->nodeValue = $tfp[$counter];
                        // echo $tfp[$counter]."\r\n";
                        $counter++;
                    }
                }
                else {
                    // Exit Script if Translation is incomplete.
                    echo "Exiting script because Translation is incomplete (e.g. using 'translateText()' function and exceeded Google Translator limitations).";
                    exit();
                }
            }
        }

        // Add UTF-8 tag and detect if there's embed (SWF) objects.
        // If translating (both file exists so the HTML files are being translated)
        if(file_exists('data.txt') && file_exists('data_trans.txt')) {
            $head = $xpath->query('//head')->item(0);

            // Add Meta Charset
            $domElement = $dom->createElement('meta');
            $domAttribute = $dom->createAttribute('charset');
            // Value for the created attribute
            if ($htmlenconding == "SJIS-win") {
              $domAttribute->value = 'shift-JIS';
            } else {
              $domAttribute->value = 'utf8';
            }
            $domElement->appendChild($domAttribute);
            $head->appendChild($domElement);

            // $embed = $xpath->query('//embed');
            // echo $embed->length;
			$dirsrc = os_path_join('..', 'ruffle');
			if (file_exists($dirsrc)) {
				foreach ($xpath->query('//embed') as $embed) {
					$embedReplace = str_replace(strtolower($pathinfo['filename']), $pathinfo['filename'], utf8_decode($embed->getAttribute('src')));
					// $embedReplace = mb_convert_encoding(utf8_decode($embedReplace), "SJIS-win", "ISO-8859-1");

					//echo "Original Embed: " . utf8_decode($embed->getAttribute('src')) . "\r\n";
					//echo "Fixed Embed: " . $embedReplace . "\r\n";
					//continue;
					$embed->setAttribute('src',$embedReplace);

					// Will Copy Ruffle Files
					
					$dirtgt = os_path_join($pathinfo['dirname'], 'ruffle');
					if(!file_exists($dirtgt)) {
						// echo "Copy Ruffle:\r\ndirsrc = " . $dirsrc . "\r\ndirtgt = " . $dirtgt;
						custom_copy($dirsrc, $dirtgt);
					}

					// Add Ruffle
					// https://github.com/ruffle-rs/ruffle/wiki/Using-Ruffle
					// https://stackoverflow.com/questions/65465492/how-to-embed-ruffle-flash-player-emulator-into-html-file
					// https://github.com/ruffle-rs/ruffle/discussions/2998
					$domElement = $dom->createElement('script', 'window.RufflePlayer = {
					  config: {
						autoplay: "on",
					  }
					};');
					$head->appendChild($domElement);

					$domElement = $dom->createElement('script');
					$domAttribute = $dom->createAttribute('src');
					// Value for the created attribute
					$domAttribute->value = './ruffle/ruffle.js';
					$domElement->appendChild($domAttribute);
					$head->appendChild($domElement);
				}
			}
            // echo html_entity_decode($dom->saveHtml());
            // continue;

            // Commit the changes.
            rename($file, $source_file);

            $hfp = fopen($file, 'w');
            //fwrite($hfp, $dom->saveHtml((new \DOMXPath($dom))->query('/')->item(0)));
            fwrite($hfp, html_entity_decode($dom->saveHtml((new \DOMXPath($dom))->query('/')->item(0))));
            fclose($hfp);
        }
    }

    // Close data.txt handle if it's open (aka. generating the source translation file)
    if (@is_resource($fp)) {
        fclose($fp);
    }
}

function translateText($source, $target, $dtext) {
    $cmd = sprintf('wget -U "Mozilla/5.0" -qO - "http://translate.googleapis.com/translate_a/single?client=gtx&sl=%s&tl=%s&dt=t&q=$(echo "%s" | sed "s/[\"\'<>]//g")" | sed "s/,,,0]],,.*//g" | awk -F\'"\' \'{print $2, $6}\' | awk \'{$NF="";sub(/[ \t]+$/,"")}1\'', $source, $target, $dtext);
    // echo $cmd;
    echo "Query: " . $dtext . "(" . $source . ")\r\n";
    echo "Translation: " . $output . " (" . $target . ").\r\n";
    $output = shell_exec($cmd);
    return $output;
}

// Does not support flag GLOB_BRACE
function glob_recursive($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function os_path_join(...$parts) {
  return preg_replace('#'.DIRECTORY_SEPARATOR.'+#', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, array_filter($parts)));
}

function getFileList($dir)
  {
    // array to hold return value
    $retval = [];

    // add trailing slash if missing
    if(substr($dir, -1) != "/") {
      $dir .= "/";
    }

    // open pointer to directory and read list of files
    $d = @dir($dir) or die("getFileList: Failed opening directory {$dir} for reading");
    while(FALSE !== ($entry = $d->read())) {
      // skip hidden files
      if($entry{0} == ".") continue;
      if(is_dir("{$dir}{$entry}")) {
        $retval[] = [
          'name' => "{$dir}{$entry}/",
          'type' => filetype("{$dir}{$entry}"),
          'size' => 0,
          'lastmod' => filemtime("{$dir}{$entry}")
        ];
      } elseif(is_readable("{$dir}{$entry}")) {
        $retval[] = [
          'name' => "{$dir}{$entry}",
          'type' => mime_content_type("{$dir}{$entry}"),
          'size' => filesize("{$dir}{$entry}"),
          'lastmod' => filemtime("{$dir}{$entry}")
        ];
      }
    }
    $d->close();

    return $retval;
  }

function custom_copy($src, $dst) {

    // open the source directory
    $dir = opendir($src);

    // Make the destination directory if not exist
    @mkdir($dst);

    // Loop through the files in source directory
    while( $file = readdir($dir) ) {

        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) )
            {

                // Recursively calling custom copy function
                // for sub directory
                custom_copy($src . '/' . $file, $dst . '/' . $file);

            }
            else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }

    closedir($dir);
}
