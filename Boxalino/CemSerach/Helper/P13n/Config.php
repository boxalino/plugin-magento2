<?php

/**
 * User: Michal Sordyl
 * Mail: michal.sordyl@boxalino.com
 * Date: 28.05.14
 */
class Boxalino_CemSearch_Helper_P13n_Config
{
    private $host;
    private $account;
    private $username;
    private $password;
    private $domain;
    private $indexId;

    /**
     * @param $host your boxalino server host, eg cdn.bx-cloud.com
     * @param $account name of account
     * @param $username username for API. One user may have many accounts (above).
     * @param $password password for username
     * @param $domain shop domain
     * @param $indexId propably same as account name
     */
    public function __construct($host, $account, $username, $password, $domain)
    {
        $this->host = $host;
        $this->account = $account;
        $this->username = $username;
        $this->password = $password;
        $this->domain = $domain;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getUsername()
    {
        return $this->username;
    }

}