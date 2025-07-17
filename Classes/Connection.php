<?php

class Connection {
    private $servername;
    private $username;
    private $password;
    private $dbname;
    private $port;

    protected function connect() {
        $this->servername = "localhost";
        $this->username = "root";
        $this->password = "";
        $this->dbname = "reactdb";

        // $this->servername = "biiox1gbnxtolzmbmbvl-mysql.services.clever-cloud.com";
        // $this->username = "ukaafovfut2sw1xv";
        // $this->password = "W8ZmlOsU8kLmOFjD7WtU";
        // $this->dbname = "biiox1gbnxtolzmbmbvl";

        $this->port = 3306;

        $conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname, $this->port);
        return $conn;
    }
}