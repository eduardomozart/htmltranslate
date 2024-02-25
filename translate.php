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

    $currdir = $dir['name'];
    // $currdir = os_path_join(__DIR__, $dir['name']);

    // Limit Translation to the folder named "test"
    // $currdir = 
    // if(strpos($dir['name'], "teste") == false) { continue; };
    
    echo $currdir . "\r\n";

    // chdir($currdir);

    // RPGM game
    if (file_exists(os_path_join($currdir, 'Game.exe'))) {
      echo "RPGM game. Skipping.\r\n";
      continue;
    }

    // File to translate exists but isn't translated yet.
    if (file_exists(os_path_join($currdir, 'data.txt'))) {
      // echo "File " . os_path_join($dir['name'], 'data.txt') . " exists.\r\n";
      if (!file_exists(os_path_join($currdir, 'data_trans.txt'))) {
          echo "File to translate exists but isn't translated yet. Skipping.\r\n";
          continue;
        }
    }

    $files = rsearch($currdir, "/.*\.html?$/");
    // die(var_dump($files));

    // if (!file_exists('data_trans.txt')) {
    if(!file_exists(os_path_join($currdir, 'data.txt'))) {
        $fp = fopen(os_path_join($currdir, 'data.txt'), 'w'); // Open overwriting contents
        fwrite($fp, pack("CCC",0xef,0xbb,0xbf)); // Create file with "UTF-8 with BOM" encoding
    } elseif (file_exists(os_path_join($currdir, 'data_trans.txt'))) {
        $tfp = explode("\n", file_get_contents(os_path_join($currdir, 'data_trans.txt')));
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
        echo $file . "\r\n";
        $pathinfo = pathinfo($file);
        $source_file = os_path_join($pathinfo['dirname'], $pathinfo['filename'] . '_orig.' . $pathinfo['extension']);
        if (strpos($pathinfo['dirname'], 'flash') !== false && !file_exists(os_path_join($currdir, 'data_trans.txt'))) { echo "Directory contains 'flash'. Skipping.\r\n"; continue; }
        if (substr($pathinfo['filename'], -3) == '_orig') { echo "Original file from already translated content. Skipping.\r\n"; continue; }
        if (file_exists($source_file)) { echo "File " . $source_file . " already exists. Skipping.\r\n"; continue; }

        if (!file_exists(os_path_join($currdir, 'data_trans.txt'))) {
            echo "\r\nExtracting text to translate from " . os_path_join($currdir, $file) . "\r\n";
            fwrite($fp, "# " . $file . "\r\n");
        }

        //
        $html = file_get_contents($file);
        if (mb_detect_encoding($html, "SJIS-win, UTF-8") == "SJIS-win") {
            $htmlenconding = "SJIS-win";
            // Directory do not contains "flash" on path
            if(strpos($pathinfo['dirname'], 'flash') == false) {
              $html = mb_convert_encoding($html, "UTF-8", "SJIS-win");
            }
        } else {
            $htmlenconding = "UTF-8";
        }
        echo "Encoding: " . $htmlenconding . "\r\n";
        // echo $html;

        // libxml_use_internal_errors(true);
        $dom = new DomDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//text()[
            not(ancestor::script) and
            not(normalize-space(.) = "")
        ]') as $text) {
            // Directory contains 'flash'. Skipping.
            if(strpos($pathinfo['dirname'], 'flash') !== false) { continue; }

            /* if (trim(translateText('ja', 'en', mb_convert_encoding($text->nodeValue, 'ISO-8859-1', 'UTF-8')))) {
                $text->nodeValue = $output;
            } else {
                echo "Google Translator limit probably exceeded.\r\n";
                exit();
            } */

            // Remove Tabs characters
            $text->nodeValue = trim(str_replace('　', '', mb_convert_encoding($text->nodeValue, 'ISO-8859-1', 'UTF-8')));
            // data.txt is open, so we are generating the source translation file.
            if (@is_resource($fp)) {
                //echo $text->nodeValue;
                //echo strlen($text->nodeValue);
                //sleep(1);

                // Create translation file
                if(trim($text->nodeValue)) {
                    fwrite($fp, $text->nodeValue . "\r\n");
                }
            } elseif (file_exists(os_path_join($currdir, 'data_trans.txt'))) {
                // Translate
                echo "\r\nTranslating " . $file . "\r\n";

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
        if(file_exists(os_path_join($currdir, 'data.txt')) && file_exists(os_path_join($currdir, 'data_trans.txt'))) {
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
            $dirsrc = os_path_join($currdir, '..', 'ruffle');
            // echo 'file_exists("' . $dirsrc . '"): ' . boolval(file_exists($dirsrc)) . "\r\n";
            if (file_exists($dirsrc)) {
              foreach ($xpath->query('//embed') as $embed) {
                $embedReplace = str_replace(strtolower($pathinfo['filename']), $pathinfo['filename'], mb_convert_encoding($embed->getAttribute('src'), 'ISO-8859-1', 'UTF-8'));
                // $embedReplace = mb_convert_encoding(mb_convert_encoding($embedReplace, 'ISO-8859-1', 'UTF-8'), "SJIS-win", "ISO-8859-1");

                //echo "Original Embed: " . mb_convert_encoding($embed->getAttribute('src'), 'ISO-8859-1', 'UTF-8');
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
            // fwrite($hfp, $dom->saveHTML($dom->documentElement));
            // fwrite($hfp, $dom->saveHtml((new \DOMXPath($dom))->query('/')->item(0)));
            // var_dump($dom->documentElement);
            fwrite($hfp, html_entity_decode($dom->saveHtml((new \DOMXPath($dom))->query('/')->item(0))));
            fclose($hfp);
        }
    }

    // Close data.txt handle if it's open (aka. generating the source translation file)
    if (@is_resource($fp)) {
        fclose($fp);
    }

    if(!filesize(os_path_join($currdir, 'data.txt'))) {
      unlink(os_path_join($currdir, 'data.txt'));
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

function rsearch($folder, $regPattern) {
  $dir = new RecursiveDirectoryIterator($folder);
  $ite = new RecursiveIteratorIterator($dir);
  $files = new RegexIterator($ite, $regPattern, RegexIterator::GET_MATCH);
  $fileList = array();
  foreach($files as $file) {
      $fileList = array_merge($fileList, $file);
  }
  return $fileList;
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
      if($entry[0] == ".") continue;
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
