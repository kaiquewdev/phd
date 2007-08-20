<?php
/*  $Id$ */

class phpweb extends phpdotnet implements PhDTheme {
    protected $streams = array();
    protected $writeit = false;


    public function __construct($IDs, $filename, $ext = "php", $chunked = true) {
        parent::__construct($IDs, $filename, $ext, $chunked);
        if (!file_exists("php") || is_file("php")) mkdir("php") or die("Can't create the cache directory");
        if (!file_exists("php/toc") || is_file("php/toc")) mkdir("php/toc") or die("Can't create the toc directory");
    }
    public function writeChunk($id, $stream) {
        rewind($stream);
        file_put_contents($this->ext."/$id.".$this->ext, $this->header($id));
        file_put_contents($this->ext."/$id.".$this->ext, $stream, FILE_APPEND);
        file_put_contents($this->ext."/$id.".$this->ext, $this->footer($id), FILE_APPEND);
    }
    public function appendData($data, $isChunk) {
        switch($isChunk) {
        case PhDReader::CLOSE_CHUNK:
            $id = $this->CURRENT_ID;
            $stream = array_pop($this->streams);
            $retval = fwrite($stream, $data);
            $this->writeChunk($id, $stream);
            fclose($stream);
            return $retval;
            break;

        case PhDReader::OPEN_CHUNK:
            $this->streams[] = fopen("php://temp/maxmemory", "r+");

        default:
            $stream = end($this->streams);
            $retval = fwrite($stream, $data);
            return $retval;
        }
    }
    public function header($id) {
        $ext = '.' .$this->ext;
        $parent = PhDHelper::getParent($id);
        $filename = $this->ext . DIRECTORY_SEPARATOR . "toc" . DIRECTORY_SEPARATOR . $parent . ".inc";
        $up = array(null, null);
        $incl = '';

        $next = $prev = array(null, null);
        if ($parent && $parent != "ROOT") {
            $siblings = PhDHelper::getChildren($parent);
            /* TODO:
             *   Maybe this isn't worth it.. but this, in theory, allows you 
             * to easily add new pages without needing to rebuild the entire 
             * section.
             */
            if (!file_exists($filename)) {
                echo "Creating $filename...\n";

                foreach($siblings as $sid => $array) {
                    $toc[] = array($sid.$ext, empty($array["sdesc"]) ? $array["ldesc"] : $array["sdesc"]);
                }

                $parents = array();
                $p = $parent;
                while (($p = PhDHelper::getParent($p)) && $p != "ROOT") {
                    $parents[] = array($p, PhDHelper::getDescription($p, true));
                }

                $content = '<?php
$TOC = ' . var_export($toc, true) . ';
$PARENTS = ' . var_export($parents, true) . ';';

                file_put_contents($filename, $content);
            }

            $incl = 'include_once dirname(__FILE__) ."/toc/' .$parent. '.inc";';
            $up = array($this->getFilename($parent).$ext, PhDHelper::getDescription($parent, true));

            $prev = $this->createPrev($id, $parent, $siblings);
            $next = $this->createNext($id, $parent, $siblings);
        }

        $setup = array(
            "home" => array('index'.$ext, "PHP Manual"),
            "head" => array("UTF-8", "en"), // FIXME: We should probably check the xml:lang on the current chunk and fallback on the roots element lang
            "this" => array($id.$ext, PhDHelper::getDescription($id)),
            "up"   => $up,
            "prev" => $prev,
            "next" => $next,
        );
        $var = var_export($setup, true);

        /* Yes. This is scary. I know. */
        return '<?php
include_once $_SERVER[\'DOCUMENT_ROOT\'] . \'/include/shared-manual.inc\';
$TOC = array();
'.$incl.'
$setup = '.$var.';
$setup["toc"] = $TOC;
manual_setup($setup);

manual_header();
?>
';
    }
    public function footer($id) {
        return "<?php manual_footer(); ?>";
    }
    protected function createPrev($id, $parent, $siblings) {
        $ext = '.' .$this->ext;
        if (!isset($siblings[$id])) {
            return array(null, null);
        }

        // Seek to $id
        if (HAS_ARRAY_SEEK) {
        in_array($siblings[$id], $siblings, false, true);
        } else {
            /* Workaround PHPs incapability of seeking into array */
            while(list($tmp,) = each($siblings)) {
                if ($tmp == $id) {
                    // Set the internal pointer back to $id
                    prev($siblings);
                    break;
                }
            }
        }
        $tmp = prev($siblings);
        if ($tmp) {
            while (!empty($tmp["children"])) {
                $tmp = end($tmp["children"]);
            }
            return array($tmp["filename"].$ext, (empty($tmp["sdesc"]) ? $tmp["ldesc"] : $tmp["sdesc"]));
            break;
        }

        return array(PhDHelper::getFilename($parent).$ext, PhDHelper::getDescription($parent, false));
    }
    protected function createNext($id, $parent, $siblings) {
        $ext = '.' .$this->ext;
        $next = array(null, null);
        // {{{ Create the "next" link
        if (!empty($siblings[$id]["children"])) {
            $tmp = reset($siblings[$id]["children"]);
            return array($tmp["filename"].$ext, (empty($tmp["ldesc"]) ? $tmp["sdesc"] : $tmp["ldesc"]));
        }
        do {
            if (!isset($siblings[$id])) {
                break;
            }

            // Seek to $id
            if (HAS_ARRAY_SEEK) {
            in_array($siblings[$id], $siblings, false, true) or die(var_export(debug_backtrace(), true) ."\n$id\n$parent"); // This should *never* happen
            } else {
                /* Workaround PHPs incapability of seeking into array */
                while(list($tmp,) = each($siblings)) {
                    if ($tmp == $id) {
                        // Set the internal pointer back to $id
                        prev($siblings);
                        break;
                    }
                }
            }

            $tmp = next($siblings);
            prev($siblings); // Reset the internal pointer to previous pos
            if ($tmp) {
                $next = array($tmp["filename"].$ext, (empty($tmp["sdesc"]) ? $tmp["ldesc"] : $tmp["sdesc"]));
                break;
            }

            // We are the end element in this chapter
            $grandpa = PhDHelper::getParent($parent);
            if (!$grandpa || $grandpa == "ROOT") {
                // There is no next relative
                break;
            }

            $siblings  = PhDHelper::getChildren($grandpa);
            $id = $parent;
            $parent = $grandpa;
        } while(true);
        return $next;
    }
    public function __destruct() {
        copy("php/manual.php", "php/index.php");
    }
}

/*
* vim600: sw=4 ts=4 fdm=syntax syntax=php et
* vim<600: sw=4 ts=4
*/

