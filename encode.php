<?php
class gzip_encode {
//     *    Take a look at http://Leknor.com/code/gziped.php and feed it a page to
    

    public $_version = 0.67; // Version of the gzip_encode class

    public $level;        // Compression level
    public $encoding;    // Encoding type
    public $crc;        // crc of the output
    public $size;        // size of the uncompressed content
    public $gzsize;    // size of the compressed content

    /*
     * gzip_encode constructor - gzip encodes the current output buffer
     * if the browser supports it.
     *
     * Note: all arguments are optionial.
     *
     * You can specify one of the following for the first argument:
     *    0:    No compression
     *    1:    Min compression
     *    ...    Some compression (integer from 1 to 9)
     *    9:    Max compression
     *    true:    Determin the compression level from the system load. The
     *        higher the load the less the compression.
     *
     * You can specify one of the following for the second argument:
     *    true:    Don't actully output the compressed form but run as if it
     *        had. Used for debugging.
     */
    public function __construct($level = 9, $debug = false) {
    if (!function_exists('gzcompress')) {
        trigger_error('gzcompress not found, ' .
            'zlib needs to be installed for gzip_encode',
            E_USER_WARNING);
        return;
    }
    if (!function_exists('crc32')) {
        trigger_error('crc32() not found, ' .
            'PHP >= 4.0.1 needed for gzip_encode', E_USER_WARNING);
        return;
    }
    if (headers_sent()) return;
    if (connection_status() !== 0) return;
    $encoding = $this->gzip_accepted();
    if (!$encoding) return;
    $this->encoding = $encoding;

    if ($level === true) {
        $level = $this->get_complevel();
    }
    $this->level = $level;

    $contents = ob_get_contents();
    if ($contents === false) return;

    $gzdata = "\x1f\x8b\x08\x00\x00\x00\x00\x00"; // gzip header
    $size = strlen($contents);
    $crc = crc32($contents);
    $gzdata .= gzcompress($contents, $level);
    $gzdata = substr($gzdata, 0, strlen($gzdata) - 4); // fix crc bug
    $gzdata .= pack("V",$crc) . pack("V", $size);

    $this->size = $size;
    $this->crc = $crc;
    $this->gzsize = strlen($gzdata);

    if ($debug) {
        return;
    }

    ob_end_clean();
    Header('Content-Encoding: ' . $encoding);
    Header('Vary: Accept-Encoding');
    Header('Content-Length: ' . strlen($gzdata));
    Header('X-Content-Encoded-By: class.gzip_encode '.$this->_version);

    echo $gzdata;
    }
    
    /**
     * Backward compatibility method for old-style constructor calls
     */
    public function gzip_encode($level = 9, $debug = false) {
        return $this->__construct($level, $debug);
    }
   

    /*
     * gzip_accepted() - Test headers for Accept-Encoding: gzip
     *
     * Returns: if proper headers aren't found: false
     *          if proper headers are found: 'gzip' or 'x-gzip'
     *
     * Tip: using this function you can test if the class will gzip the output
     *  without actually compressing it yet, eg:
     *    if (gzip_encode::gzip_accepted()) {
     *       echo "Page will be gziped";
     *    }
     *  note the double colon syntax, I don't know where it is documented but
     *  somehow it got in my brain.
     */
    public function gzip_accepted() {
    $http_accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    if (strpos($http_accept_encoding, 'gzip') === false) return false;
    if (strpos($http_accept_encoding, 'x-gzip') === false) {
        $encoding = 'gzip';
    } else {
        $encoding = 'x-gzip';
    }

    // Test file type. I wish I could get HTTP response headers.
    $magic = substr(ob_get_contents(),0,4);
    if (substr($magic,0,2) === '^_') {
        // gzip data
        $encoding = false;
    } else if (substr($magic,0,3) === 'GIF') {
        // gif images
        $encoding = false;
    } else if (substr($magic,0,2) === "\xFF\xD8") {
        // jpeg images
        $encoding = false;
    } else if (substr($magic,0,4) === "\x89PNG") {
        // png images
        $encoding = false;
    } else if (substr($magic,0,3) === 'FWS') {
        // Don't gzip Shockwave Flash files. Flash on windows incorrectly
        // claims it accepts gzip'd content.
        $encoding = false;
    } else if (substr($magic,0,2) === 'PK') {
        // pk zip file
        $encoding = false;
    }

    return $encoding;
    }

    /*
     * get_complevel() - The level of compression we should use.
     *
     * Returns an int between 0 and 9 inclusive.
     *
     * Tip: $gzleve = gzip_encode::get_complevel(); to get the compression level
     *      that will be used with out actually compressing the output.
     *
     * Help: if you use an OS other then linux please send me code to make
     * this work with your OS - Thanks
     */
    public function get_complevel() {
    $uname = posix_uname();
    switch ($uname['sysname']) {
        case 'Linux':
        $cl = (1 - $this->linux_loadavg()) * 10;
        $level = (int)max(min(9, $cl), 0);
        break;
        case 'FreeBSD':
        $cl = (1 - $this->freebsd_loadavg()) * 10;
        $level = (int)max(min(9, $cl), 0);
        break;
        default:
        $level = 3;
        break;
    }
    return $level;
    }

    /*
     * linux_loadavg() - Gets the max() system load average from /proc/loadavg
     *
     * The max() Load Average will be returned
     */
    public function linux_loadavg() {
    $buffer = "0 0 0";
    $f = fopen("/proc/loadavg","r");
    if (!feof($f)) {
        $buffer = fgets($f, 1024);
    }
    fclose($f);
    $load = explode(" ",$buffer);
    return max((float)$load[0], (float)$load[1], (float)$load[2]);
    }

    /*
     * freebsd_loadavg() - Gets the max() system load average from uname(1)
     *
     * The max() Load Average will be returned
     *
     * I've been told the code below will work on solaris too, anyone wanna
     * test it?
     */
    public function freebsd_loadavg() {
    $buffer = `uptime`;
    $load = array();
    if (preg_match("/averag(?:es|e): ([0-9]+\.[0-9]+), ([0-9]+\.[0-9]+), ([0-9]+\.[0-9]+)/", $buffer, $load)) {
        return max((float)$load[1], (float)$load[2], (float)$load[3]);
    }
    return 0.0; // Return default value if pattern doesn't match
    } 
}

?>
