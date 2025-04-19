# HTML Translate
 Translate HTML files.
 
## How it works and why it exists?

The ``translate.php`` file is a PHP script that extract text from HTML files recursively to create a "data.txt" file at the HTML files top folder (see "File Tree Example" for more info).

You can upload this file to Google Translate or any other translation service of your choise to have a machine translation (MTL) of yours HTML files content, so you can run the PHP script again to apply your translation for each HTML file.

I created this script because I had a couple of Japanese games and I couldn't find any other alternative to translate HTML files, so I wrote one.

The ``translate-json.php`` is an experimental PHP script to extract and translate JSON strings.

## File Tree Example

```
HTML_games/
├─ cors_server.py
├─ python.png
├─ ruffle/
│  ├─ ruffle.js
│  ├─ ...
├─ HTML game 1/
│  ├─ subfolder/
│  │  ├─ file1.html
│  ├─ data.txt
│  ├─ data_trans.txt
│  ├─ index.html
├─ HTML game 2/
│  ├─ index.html
│  ├─ data.txt
│  ├─ data_trans.txt
```

The PHP scripts search for HTML files recursively at the "HTML_games" folder and creates a ``data.txt`` for each subdirectory at the ``HTML_games`` folder.

At this example, will be created two ``data.txt`` files: at the root of ``HTML game 1`` folder and at the root of ``HTML game 2`` folder, as can be seen above.

The ``data.txt`` file from "HTML game 1" folder will contain all strings (text) from "index.html" and "file1.html", while ``data.txt`` from "HTML game 2" will contain data about "index.html" only.

## About the data_trans.txt file

The ``data_trans.txt`` file needs to be created manually by you at the same folder of ``data.txt`` file. Please see the "File Tree Example" section for an example.

The ``data_trans.txt`` file must contain the translated strings from the ``data.txt`` file. You can translate the strings by hand or use a translation service (e.g. Google Translate at https://translatordrive.softgateon.net/) to upload your source file to have a MTL (machine translated) version from it.

Note: The translation service may translate the filename on # (comment section) but it doesn't matter. The ``data_trans.txt`` file must match exactly the same number of lines of the ``data.txt`` file, and each line of ``data_trans.txt`` MUST correspond the same line number of ``data.txt`` file, or the script will apply the translation out of place (aka. instead of replacing the original string, it will replace another one).

Most translation services do not allow translating file with a lot of letters. You can split the number of lines from the file (to limit the number of words to translate when using an on-line service) by hand or running the following command at the Terminal (Unix):

```
# Split the data.txt file to files with an "x" preffix with 600 lines each.
split --lines 600 --additional-suffix=.txt data.txt x
```

After translating each splited ``x*.txt`` file, you can merge it to create the ``data_trans.txt`` file running the following command at the Terminal (Unix):

```
# Concatenate each x*.html file to a single file
cat x*.txt > data_trans.txt
```

## Dependencies

This script requires the following dependencies:

1. php-intl
2. php-mbstring

You can check if they are available into your system by running the command:

```
php -m |grep -E 'intl|mbstring'
intl
mbstring
```

You can download and install them at Debian-based distros running the following command at the Terminal:

```
sudo apt-get update && sudo apt-get install php-intl php-mbstring
```

## HowTo

After installing the PHP scripts dependencies, you can finally run it: 

1. Run the script running the following command at the Terminal (Linux)/Command Prompt (Windows):

```
php translate.php /home/Eduardo/HTML_games
```

Change the ``/home/Eduardo/HTML_games`` variable to point it to where your HTML files are (into the "File Tree Example" above, the root directory would be the full path to the "HTML_games" directory). It's recommended to specify a full path for this argument.

2. It will create a ``data.txt`` file at each subfolder.

Tip: You can start from scratch by deleting all ``data.txt`` files recursively through the command:

```
find . -name "data.txt" -exec rm -f {} \;
````

3. Create the ``data_trans.txt`` file containing the translated strings of the ``data.txt`` file. Please read the "About the data_trans.txt file" section for more info.

4. Run the script again to apply the translation to your HTML files. **PLEASE DO A BACKUP OF YOUR HTML FILES FIRST!**

```
php translate.php /home/Eduardo/HTML_games
```

## Backup

Before translating your HTML files, the script creates an automatic backup of your existing HTML files.

You can restore the original files (if needed) through the following command:

```
find . -type f -name '*_orig.htm*' -print0 |
    while IFS= read -d '' file; do
        extension="${file##*.}"
        mv "$file" "${file%_orig.$extension}.$extension"
    done
```

## Ruffle integration (optional)

Most of my HTML games were Adobe Flash based, so I embeded an optional [Ruffle](https://ruffle.rs) integration. Ruffle allows you to play Flash based games into newer browsers without using Adobe Flash.

The script automatically detects if there's any "embed" tag at your HTML files and automatically embed Ruffle at the page if Ruffle could be found at the main directory (please see the "File Tree Example" section for an example).

If you want to use this feature, you need to:

1. Download the Self-Hosted version of Ruffle. Available at: https://ruffle.rs/downloads

2. Extract the downloaded file (e.g. `ruffle_nightly_2020_11_25_selfhosted.zip`) to the "ruffle" subdirectory at the root directory of the file tree hierarchy. Please see the "File Tree Example" section for an example.

If you didn't provide Ruffle during script execution, you can apply it on HTML files through the following Shell Script:

```
for file in *.html; do sed -i "s#</head>#<SCRIPT>\n    window.RufflePlayer = {\n      config: {\n        autoplay: \"on\",\n      }\n    };\n</SCRIPT>\n<SCRIPT src=\"./flash/ruffle/ruffle.js\"></SCRIPT>\n<META charset=\"utf8\">\n</head>#" $file; done
```

### CORS Server

Adobe Flash allowed you to load local SWF files using the "file://" prefix, but modern browsers do not allow doing it because it could allow a malicious attacker to have access to local system files. So, for Ruffle to load SWF files, you'll need to serve them through a Web Server (using the "http://localhost" prefix).

The ``cors_server.py`` script is a modified version from CORS Servers Python script available [here](https://gist.github.com/enjalot/2904124).

This modification includes a taskbar tray icon that allows you to close or browse your running CORS Servers using your default browser.

For it to work, you must install the following dependency:

```
python3 -m pip install pystray
```

You can run the CORS Server by copying the file to the main folder (please see the "File Tree Example" for more information) and running the following commands through Terminal:

```
cd HTML_games/
python3 cors_server.py 8000
```

Alternatively, you can run the HTTP server without tray icon instead:

```
python3 -m http.server
```

It would serve the contents of the "HTML_games/" folder through a local web server ("http://localhost:8080"), allowing Rufus to load local SWF files.

By default, if none TCP port would be specified, the CORS Server would listen for HTTP connections at the "8000" port.

## License

[GNU GPLv3](LICENSE).
