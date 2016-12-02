<?php

namespace thriftlib;

/**
 * @author sergey <sergey@zhangyoubao.com>
 */
class Domain 
{

    /**
     * instance of domain
     *
     * @var Domain
     */
    private static $instance = null;

    private $lastDomainCacheTime = 0;

    private $domainCache = array();

    /**
     * get instance [domain]
     *
     * @return \thriftlib\Domain
     * 
     */
    public static function getInstance() 
    {
        if (null == self::$instance)
        {
            self::$instance = new Domain();
        }
        return self::$instance;
    }

    public function searchHost($domain)
    {
        $domainList = $this->domainTable();
        if (isset($domainList[$domain])) 
        {
            return $domainList[$domain];
        }
        return '';
    }


    /**
     * get domain table, just like A record, one by one 
     *
     * @return array
     */
    private function domainTable() 
    {
        if ($this->lastDomainCacheTime < (time() - 30 * 60))
        {
            $this->domainCache = $this->loadDomainTable();
            $this->lastDomainCacheTime = time();
        }
        return $this->domainCache;
    }

    /**
     * load domain table
     *
     * @return array
     */
    private function loadDomainTable()
    {
        $rlocal = $this->loadDomainTabelFromFile(__DIR__ . DIRECTORY_SEPARATOR . 'lib_config' . DIRECTORY_SEPARATOR . 'hosts');
        $rsys = $this->loadDomainTabelFromFile('/etc/path/to/private/hosts');
        return array_merge($rlocal, $rsys);
    }

    /**
     * load domain tabel from file and parser it
     *
     * @param string $file filename
     *
     * @return array
     */
    private function loadDomainTabelFromFile($file)
    {
        $result = array();
        if (!file_exists($file))
        {
            return $result;
        }
        $tmpDomainTable = file_get_contents($file);
        $tmpDomainTable = str_replace("\r", '', $tmpDomainTable);
        $tmpDomainTable = explode("\n", $tmpDomainTable);
        foreach ($tmpDomainTable as $dt)
        {
            $dt = trim($dt);
            $dt = explode(' ', $dt);
            $domain = '';
            $host = '';
            foreach ($dt as $d)
            {
                $t = trim($d);
                if (!empty($t))
                {
                    if (empty($domain))
                    {
                        $domain = trim($d);
                    }
                    else 
                    {
                        $host = trim($d);
                        break;
                    }
                }
            }
            if (!empty($domain) && !empty($host))
            {
                $result[$domain] = $host;
            }
        }
        return $result;
    }


}