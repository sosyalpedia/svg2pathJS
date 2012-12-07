<?php

/*
 * @title Multilayer SVG parser for Raphaël JS (CLI version)
 *
 * @author Taner DOGAN <hello@tanerdogan.com>
 * github.com/tanerdogan | @tanerdogan
 *
 */

class svg2pathJS {

    var $output;

//
// Converter
//
    public function convert($svgFile, $toJson = false) {
        if (!$contents = file_get_contents($svgFile))
            die('Opppss! Where is SVG file?!');

        // Remove all types of line breaks and tabs. Also get an array of paths
        $graphicalPaths = explode('<path', str_replace(array("\r", "\r\n", "\n", "\t"), '', $contents));

        $pathIterator = 0;
        $output = array();
        $matches = array();

        // Regex for find match svg attributes.
        $regex = '/\b(id|fill|d|name|stroke|style|stroke-width)\b=["|\']([^"\']*)["|\']/';


        foreach ($graphicalPaths as $path) {
            // If not found, skip.
            if (!preg_match_all($regex, $path, $matches) OR !in_array('d', $matches[1]))
                continue;

            $attrIterator = 0;

            // Loops attributes and adds to output array.
            foreach ($matches[1] as $match) {
                // if match is equal name or id, run slugify
                if (in_array($match, array('name', 'id')))
                    $output[$pathIterator][$match] = $this->slugify($matches[2][$attrIterator]);
                else
                    $output[$pathIterator][$match] = $matches[2][$attrIterator];
                $attrIterator++;
            }
            $pathIterator++;
        }

        // return json or array. *optional*
        $this->output = $toJson ? json_encode($output) : $output;
    }

//
// Export
//
    public function export($jsFile = "") {

        $export .= "
/*
 * JavaScript Vector Library for Raphaël JS
 *
 * This file created by svg2pathJS (CLI version)
 * @author Taner DOGAN <hello@tanerdogan.com>
 * github.com/tanerdogan | @tanerdogan
 *
*/

var paths = {
    blank: {
    name: 'blank',
    path: ''
    },";

        $arr = $this->output;
        $i = 1;
        foreach ($arr as $attr) {

            if (empty($attr['name']))
                $attr['name'] = "path" . $i++;

            $export .= "
     $attr[name]: {
     name: '$attr[name]',
     value: 'notSelected',
     path: '$attr[d]'
    }";
            if (next($arr))
                $export .= ",\r";
            else
                $export .= "\r\n}";
        }
        if (!empty($jsFile)) {
            $fp = fopen($jsFile, 'w');
            fwrite($fp, $export);
            fclose($fp);
        } else
            echo $export;
    }

//
// Modifies a string to remove all non ASCII characters and spaces.
//
    private function slugify($text) {
        $text = preg_replace('~[^\\pL\d]+~u', '_', $text);
        $text = trim($text, '-');
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }

}

//
// ---- CLI ----
//

if (defined('STDIN')) {
    echo "\n\r --- svg2pathJS v0.2 [Running from CLI] --- \n\r\n\r";


    $_ARG = array();
    foreach ($argv as $arg) {
        if (ereg('--([^=]+)=(.*)', $arg, $reg)) {
            $_ARG[$reg[1]] = $reg[2];
        } elseif (ereg('-([a-zA-Z0-9])', $arg, $reg)) {
            $_ARG[$reg[1]] = 'true';
        }
    }

    if ((isset($_ARG['in']) && (isset($_ARG['in'])))) {
        $svg = new svg2pathJS();
        $svg->convert($_ARG['in']);
        $svg->export($_ARG['out']);
        echo "\n\rSVG Exported!\n\r";
    } else {
        echo "\n\rMissing parameters!\n\r";
        echo "\n\rUSAGE\n\r";
        echo "-----------------------------------------------------------\n\r";
        echo "$ php svg2pathjs.php --in=filename.svg --out=filename.js\n\r";
    }
} else {
    echo("Sorry, only running from CLI");
    exit(0);
}


/*
$svg = new svg2pathJS();
$svg->convert('SVG_example7.svg');
//$svg->convert('BlankMapTurkishProvincesRegions.svg');
//$svg->convert('Tux.svg');
$svg->export();
*/


