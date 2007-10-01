<?php
/*  $Id$ */

class PhDHelper {
    private $IDs            = array();
    /* abstract */ protected $elementmap  = array();
    /* abstract */ protected $textmap     = array();
    private static $autogen         = array();

    public function __construct(array $IDs) {
        $this->IDs = $IDs;
    }
    final public function getFilename($id) {
        return isset($this->IDs[$id]) ? $this->IDs[$id]["filename"] : false;
    }
    final public function getDescription($id, $long = false) {
        return $long ?
            ($this->IDs[$id]["ldesc"] ? $this->IDs[$id]["ldesc"] : $this->IDs[$id]["sdesc"]) :
            ($this->IDs[$id]["sdesc"] ? $this->IDs[$id]["sdesc"] : $this->IDs[$id]["ldesc"]);
    }
    final public function getChildren($id) {
        return $this->IDs[$id]["children"];
    }
    final public function getParent($id) {
        return $this->IDs[$id]["parent"];
    }
    final public function getElementMap() {
        return $this->elementmap;
    }
    final public function getTextMap() {
        return $this->textmap;
    }
    final public function autogen($text, $lang) {
        if (isset(PhDHelper::$autogen[$lang])) {
            return PhDHelper::$autogen[$lang][$text];
        }

        $filename = dirname(__FILE__) ."/langs/$lang.xml";
        $r = new XMLReader;
        if (!$r->open($filename)) {
            return $this->autogen($text, "en");
        }
        $autogen = array();
        while ($r->read()) {
            if ($r->nodeType != XMLReader::ELEMENT) {
                continue;
            }
            if ($r->name == "term") {
                $r->read();
                $k = $r->value;
                $autogen[$k] = "";
            } else if ($r->name == "simpara") {
                $r->read();
                $autogen[$k] = $r->value;
            }
        }
        PhDHelper::$autogen[$lang] = $autogen;
        return PhDHelper::$autogen[$lang][$text];
    }
}

/*
 * vim600: sw=4 ts=4 fdm=syntax syntax=php et
 * vim<600: sw=4 ts=4
 */

