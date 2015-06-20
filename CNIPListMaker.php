<?php

class CNIPListMaker
{
    const RESOURCE_URL = 'http://ftp.apnic.net/apnic/stats/apnic/delegated-apnic-latest';
    const OUT_FILE = 'cn_ips.php';
    private static $_instance = null;
    private $_cn_ips = null;

    private function __construct()
    {
        $this->_cn_ips = include($this->getFile());
    }

    public function getFile()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . self::OUT_FILE;
    }

    // get text from resource url and extracts ipv4 addresses of CN

    public static function instance()
    {
        if (self::$_instance === null)
            self::$_instance = new self;

        return self::$_instance;
    }

    public function run()
    {
        $stuff = file_get_contents(self::RESOURCE_URL);
        if ($stuff === false) throw new Exception('failed to get resource');

        if (preg_match_all('/apnic\|CN\|ipv4\|(?P<IPS>\d{0,3}\.\d{0,3}\.\d{0,3}\.\d{0,3})\|\S+/', $stuff, $matches) === false)
            throw new Exception('no match found in resource');


        $list = array_fill_keys($matches['IPS'], 1);
        $list = '<?php return ' . var_export($list, true) . ';';
        if (file_put_contents($this->getFile(), $list) === false)
            throw new Exception('failed to write data');
    }

    public function isCN($ip)
    {
        $ipArr = explode('.', $ip);
        if (count($ipArr) !== 4) return false;

        $tmp = implode('.', [
            $ipArr[0],
            $ipArr[1],
            0,
            0
        ]);

        if (isset($this->_cn_ips[$tmp])) return true;

        $tmp = implode('.', [
            $ipArr[0],
            $ipArr[1],
            $ipArr[3],
            0
        ]);

        if (isset($this->_cn_ips[$tmp])) return true;

        return isset($this->_cn_ips[$ip]);
    }

    public function getCTime()
    {
        return filectime($this->getFile());
    }

    public function getStatus()
    {
        $file = $this->getFile();
        if (!file_exists($file)) return 'Not Ready';

        if (!is_readable($file)) return 'Not readable';

        if (!is_writable($file)) return 'Not Writable';

        return 'Ready, last change time: ' . date('Y-m-d H:i:s', $this->getCTime());
    }
}

// CNIPListMaker::instance()->run();
// var_dump(CNIPListMaker::instance()->isCN('122.96.237.41'));
