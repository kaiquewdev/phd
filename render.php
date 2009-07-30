<?php
namespace phpdotnet\phd;
/* $Id$ */

$ROOT = __DIR__;
function autoload($name)
{
    $file = str_replace(array('\\', '_'), '/', $name) . '.php';
    if (!$fp = fopen($file,'r', true)) {
        throw new \Exception('Cannot find file for ' . $name . ': ' . $file);
    }   
    fclose($fp);
    require $file;
}
spl_autoload_register(__NAMESPACE__ . '\\autoload');
require_once 'phpdotnet/phd/functions.php';

$optparser = new BuildOptionsParser();
$optparser->getopt();

define("NO_SQLITE", false);

/* If no docbook file was passed, die */
if (!is_dir(Config::xml_root()) || !is_file(Config::xml_file())) {
    trigger_error("No Docbook file given. Specify it on the command line with --docbook.", E_USER_ERROR);
}
if (!file_exists(Config::output_dir())) {
    v("Creating output directory..", E_USER_NOTICE);
    if (!mkdir(Config::output_dir())) {
        v("Can't create output directory", E_USER_ERROR);
    }
} elseif (!is_dir(Config::output_dir())) {
    v("Output directory is not a file?", E_USER_ERROR);
}

Config::init(array(
    "verbose"                 => VERBOSE_ALL^(VERBOSE_PARTIAL_CHILD_READING|VERBOSE_CHUNK_WRITING),
    "lang_dir"                => $ROOT . DIRECTORY_SEPARATOR . "phpdotnet" . DIRECTORY_SEPARATOR 
                                    . "phd" . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR 
                                    . "langs" . DIRECTORY_SEPARATOR,
    "phpweb_version_filename" => Config::xml_root() . DIRECTORY_SEPARATOR . 'version.xml',
    "phpweb_acronym_filename" => Config::xml_root() . DIRECTORY_SEPARATOR . 'entities' . DIRECTORY_SEPARATOR . 'acronyms.xml',
    ));

$render = new Render();
$reader = new Reader();
$factory = Format_Factory::createFactory();

// Indexing & registering formats
foreach(range(0, 0) as $i) {
    if (Index::requireIndexing()) {
        v("Indexing...", VERBOSE_INDEXING);
        // Create indexer
        $format = $render->attach(new Index);

        $reader->open(Config::xml_file());
        $render->render($reader);

        $render->detach($format);

        v("Indexing done", VERBOSE_INDEXING);
    } else {
        v("Skipping indexing", VERBOSE_INDEXING);
    }

    if (count(Config::output_format()) == 0) {
        Config::set_output_format($factory->getOutputFormats());
    }
 
    foreach (Config::output_format() as $format) {
        $render->attach($factory->createFormat($format));
    }
}

// Rendering formats
foreach(range(0, 0) as $i) {
	$reader->open(Config::xml_file());
    foreach($render as $format) {
        $format->notify(Render::VERBOSE, true);
    }
    $render->render($reader);
}

v("Finished rendering", VERBOSE_FORMAT_RENDERING);


/*
* vim600: sw=4 ts=4 syntax=php et
* vim<600: sw=4 ts=4
*/

