<?php
use \Workerman\Worker;
use \Workerman\WebServer;
const APIROOT = __DIR__;
require_once __DIR__ . '/Workerman/Autoloader.php';
require_once __DIR__ . '/core/Db.php';
require_once __DIR__ . '/core/function.php';
class httpServer{
    private $httpServer;
    public function __construct(){
        $this -> httpServer = new WebServer('http://127.0.0.1:8080');
        $this -> httpServer -> addRoot('domain', __DIR__.'/application');
        $this -> httpServer -> count = 1;
    }
}
new httpServer();
Worker::runAll();
