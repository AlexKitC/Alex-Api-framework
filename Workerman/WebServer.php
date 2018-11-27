<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */


 /**
  * @author Alex-黑白
  * @QQ     392999164
  * @based  workerman
  */
namespace Workerman;

use Workerman\Protocols\Http;
use Workerman\Protocols\HttpCache;
/**
 *  WebServer.
 */
class WebServer extends Worker
{
    /**
     * Virtual host to path mapping.
     *
     * @var array ['workerman.net'=>'/home', 'www.workerman.net'=>'home/www']
     */
    protected $serverRoot = array();

    protected $app_moudle = 'index';
    protected $app_controller = 'index';
    protected $app_action = 'index';
    protected $ext = ".php";

    private $is_moudle;
    private $is_controller;
    private $is_action;

    private $controllerObjs = []; //保存各个控制器的实例
    /**
     * Mime mapping.
     *
     * @var array
     */
    protected static $mimeTypeMap = array();


    /**
     * Used to save user OnWorkerStart callback settings.
     *
     * @var callback
     */
    protected $_onWorkerStart = null;

    /**
     * Add virtual host.
     *
     * @param string $domain
     * @param string $config
     * @return void
     */
    public function addRoot($domain, $config)
    {
	if (is_string($config)) {
            $config = array('root' => $config);
	}
        $this->serverRoot[$domain] = $config;
    }

    /**
     * Construct.
     *
     * @param string $socket_name
     * @param array  $context_option
     */
    public function __construct($socket_name, $context_option = array())
    {
        list(, $address) = explode(':', $socket_name, 2);
        parent::__construct('http:' . $address, $context_option);
        $this->name = 'WebServer';
    }

    /**
     * Run webserver instance.
     *
     * @see Workerman.Worker::run()
     */
    public function run()
    {
        $this->_onWorkerStart = $this->onWorkerStart;
        $this->onWorkerStart  = array($this, 'onWorkerStart');
        $this->onMessage      = array($this, 'onMessage');
        parent::run();
    }

    /**
     * Emit when process start.
     *
     * @throws \Exception
     */
    public function onWorkerStart()
    {
        if (empty($this->serverRoot)) {
            echo new \Exception('server root not set, please use WebServer::addRoot($domain, $root_path) to set server root path');
            exit(250);
        }

        // Init mimeMap.
        $this->initMimeTypeMap();

        // Try to emit onWorkerStart callback.
        if ($this->_onWorkerStart) {
            try {
                call_user_func($this->_onWorkerStart, $this);
            } catch (\Exception $e) {
                self::log($e);
                exit(250);
            } catch (\Error $e) {
                self::log($e);
                exit(250);
            }
        }
    }

    /**
     * Init mime map.
     *
     * @return void
     */
    public function initMimeTypeMap()
    {
        $mime_file = Http::getMimeTypesFile();
        if (!is_file($mime_file)) {
            $this->log("$mime_file mime.type file not fond");
            return;
        }
        $items = file($mime_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($items)) {
            $this->log("get $mime_file mime.type content fail");
            return;
        }
        foreach ($items as $content) {
            if (preg_match("/\s*(\S+)\s+(\S.+)/", $content, $match)) {
                $mime_type                      = $match[1];
                $workerman_file_extension_var   = $match[2];
                $workerman_file_extension_array = explode(' ', substr($workerman_file_extension_var, 0, -1));
                foreach ($workerman_file_extension_array as $workerman_file_extension) {
                    self::$mimeTypeMap[$workerman_file_extension] = $mime_type;
                }
            }
        }
    }

    /**
     * Emit when http message coming.
     *
     * @param Connection\TcpConnection $connection
     * @return void
     */
    public function onMessage ($connection)
    {
        // REQUEST_URI.
        $workerman_url_info = parse_url($_SERVER['REQUEST_URI']);
        if (!$workerman_url_info) {
            Http::header('HTTP/1.1 400 Bad Request');
            $connection->close('<h1>400 Bad Request</h1>');
            return;
        }
        $PATH_INFO = '/' . $this -> app_moudle . '/' . $this -> app_controller . '/' . $this -> app_action;
        
        /** */
        $pathInfoArr = array_values(array_filter(explode("/",$workerman_url_info['path'])));
        $pathInfo = '';
        if(count($pathInfoArr) == 0){
            $pathInfo = $this -> app_moudle."/".$this -> app_controller."/".$this -> app_action;
        }elseif(count($pathInfoArr) == 1){
            $pathInfo = $pathInfoArr['0']."/".$this -> app_controller."/".$this -> app_action;
        }elseif(count($pathInfoArr) == 2){
            $pathInfo = $pathInfoArr['0']."/".$pathInfoArr['1']."/".$this -> app_action;
        }elseif(count($pathInfoArr) == 3){
            $pathInfo = $pathInfoArr['0']."/".$pathInfoArr['1']."/".$pathInfoArr['2'];
        }else{
            $pathInfo = $workerman_url_info['path'];
        }
        if (($pathInfo !== '/') && count($pathInfoArr) > 2){
            $urlArray = array_values(array_filter(explode("/",$pathInfo)));
            // 获取模块名
            $moudleName = empty($urlArray[0]) ? $this -> app_moudle : $urlArray[0];
            $moudle = $moudleName;
            $_SERVER['m'] = $moudle;
            $this -> isMoudle($moudle);
            //检测当前模块
            if($this -> is_moudle){//若存在再检测控制器
                // 获取控制器名
                $controllerName = ucfirst(empty($urlArray[1]) ? $this -> app_controller : $urlArray[1]);
                $controller = $controllerName;
                $_SERVER['c'] = $controller;
                $this -> isController($moudle,$controller);
                if($this -> is_controller){//若存在控制器则实例化该控制器类
                    require_once(APIROOT.'/application/'.$moudle.'/controller/'.$controller.$this -> ext);
                    $ControllerObjStr = '\application\controller\\'.$controller;
                    $Controller = "";
                    if(!in_array($ControllerObjStr,$this -> controllerObjs)){
                        $Controller = new $ControllerObjStr();
                        array_push($this -> controllerObjs,["$ControllerObjStr"=>$Controller]);
                    }
                    //执行对应方法
                    $action = empty($urlArray[2]) ? $this -> app_action : $urlArray[2];
                    $_SERVER['a'] = $action;
                    $this -> isAction($Controller,$action);
                    if($this -> is_action){//检测方法是否存在
                        //解析参数
                        $paramsArr = [];
                        foreach($urlArray as $k =>$v){
                            if($k !==0 && $k !== 1 && $k !==2){
                                array_push($paramsArr,$v);
                            }
                        }
                        //重组键值对
                        if(!empty($paramsArr)){//有参数
                            if(count($paramsArr)%2 == 0){//键值对为偶数参数正常
                                $params = [];
                                foreach($paramsArr as $k => $v){
                                    if($k%2 == 0){
                                        $params[$paramsArr[$k]] = null;
                                    }elseif($k%2 == 1){
                                        $params[$paramsArr[$k-1]] = $paramsArr[$k];
                                    }
                                }
                                $_SERVER['_GET'] = $params;
                                ob_start();
                                $Controller -> $action();
                                if(strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive"){
                                    $connection -> send(ob_get_clean());
                                }else{
                                    $connection -> close(ob_get_clean());
                                }
                                return;
                            }else{//参数个数异常
                                die('ERROR: unexpected params\'s number');
                            }
                            
                        }else{
                            ob_start();
                            $Controller -> $action();
                            if(strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive"){
                                $connection -> send(ob_get_clean());
                            }else{
                                $connection -> close(ob_get_clean());
                            }
                            return;
                        }
                    }else{
                        die('ERROR: Action: '.$action.'  is  not  exists.');
                    }

                }else{
                    die('ERROR: Controller: '.$controller.'  is  not  exists.');
                }

                // $queryString = empty($urlArray) ? array() : $urlArray;
                // $PATH_INFO = '/' . $moudle . '/' . $controller . '/' . $action . '/';
                // header('Location: '.$_SERVER['SCRIPT_NAME'].$PATH_INFO);
            }else{
                die('ERROR: Moudle: '.$moudle.'  is  not  exists.');
            }
            
        }else{
            //执行默认某块控制器方法请求
            // $PATH_INFO = '/' . $this -> app_moudle . '/' . $this -> app_controller . '/' . $this -> app_action;
            // HTTP::header('location: '.$_SERVER['HTTP_HOST'].$PATH_INFO);
            $connection -> send(json_encode(['status'=>0,'msg'=>'please domain/Moudle/Controller/action']));
            return;
        }

        
    }

    public static function sendFile($connection, $file_path)
    {
        // Check 304.
        $info = stat($file_path);
        $modified_time = $info ? date('D, d M Y H:i:s', $info['mtime']) . ' ' . date_default_timezone_get() : '';
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $info) {
            // Http 304.
            if ($modified_time === $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
                // 304
                Http::header('HTTP/1.1 304 Not Modified');
                // Send nothing but http headers..
                $connection->close('');
                return;
            }
        }

        // Http header.
        if ($modified_time) {
            $modified_time = "Last-Modified: $modified_time\r\n";
        }
        $file_size = filesize($file_path);
        $file_info = pathinfo($file_path);
        $extension = isset($file_info['extension']) ? $file_info['extension'] : '';
        $file_name = isset($file_info['filename']) ? $file_info['filename'] : '';
        $header = "HTTP/1.1 200 OK\r\n";
        if (isset(self::$mimeTypeMap[$extension])) {
            $header .= "Content-Type: " . self::$mimeTypeMap[$extension] . "\r\n";
        } else {
            $header .= "Content-Type: application/octet-stream\r\n";
            $header .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n";
        }
        $header .= "Connection: keep-alive\r\n";
        $header .= $modified_time;
        $header .= "Content-Length: $file_size\r\n\r\n";
        $trunk_limit_size = 1024*1024;
        if ($file_size < $trunk_limit_size) {
            return $connection->send($header.file_get_contents($file_path), true);
        }
        $connection->send($header, true);

        // Read file content from disk piece by piece and send to client.
        $connection->fileHandler = fopen($file_path, 'r');
        $do_write = function()use($connection)
        {
            // Send buffer not full.
            while(empty($connection->bufferFull))
            {
                // Read from disk.
                $buffer = fread($connection->fileHandler, 8192);
                // Read eof.
                if($buffer === '' || $buffer === false)
                {
                    return;
                }
                $connection->send($buffer, true);
            }
        };
        // Send buffer full.
        $connection->onBufferFull = function($connection)
        {
            $connection->bufferFull = true;
        };
        // Send buffer drain.
        $connection->onBufferDrain = function($connection)use($do_write)
        {
            $connection->bufferFull = false;
            $do_write();
        };
        $do_write();
    }

    /**
     * @func 检测当前模块是否存在
     * $param $moudle 需要检测的模块名
     */
    public function isMoudle($moudle){
        if(!is_dir('../../application/'.$moudle)){
            $this -> is_moudle = false;
        }else{
            $this -> is_moudle = true;
        }
    }

    /**
     * @func 检测当前控制器是否存在
     * @param $moudle 待检测控制器所属模块名
     * @param $controller 需要检测的控制器名
     */
    public function isController($moudle,$controller){
        if(file_exists(APIROOT.'/application/'.$moudle.'/controller/'.$controller.$this -> ext)){
            $this -> is_controller = true;
        }else{
            $this -> is_controller = false;
        }
    }

    /**
     * @func 检测当前模块控制器下的指定方法是否存在
     * @param @obj 当前方法所在class
     * @param @action
     */
    public function isAction($obj,$action){
        if(!method_exists($obj,$action)){
            $this -> is_action = false;
        }else{
            $this -> is_action = true;
        }
    }
}
