<?php
namespace Db;
/**
 * @info    Db 
 * @author  Alex-黑白
 * @QQ      392999164
 */

class Db{

    private $host = 'localhost';
    private $database = 'test';
    private $username = 'root';
    private $password = 'root';

    private $table_prefix = 'alex_';
    private $_pools = [];
    private $poolSize = 1;
    private $sql;
    private $table;

    public function init(){
        for($i=0;$i< $this -> poolSize;$i++){
            $conn = new \PDO("mysql:host=".$this -> host.";dbname=".$this -> database,$this -> username,$this -> password,array(\PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8",\PDO::ATTR_PERSISTENT => true));
            if($conn){
                array_push($this -> _pools,$conn);
            }
        }
    }

    public function __construct($table=null){
        if($table !== null){
            $this -> table = $this -> table_prefix == '' ? $table : $this -> table_prefix.$table;
        }
    }

    private function getConn(){
        if(count($this -> _pools) > 0){
            $conn = array_pop($this -> _pools);
            return $conn;
        }
    }

    private function releaseConn($conn){
        if(count($this -> _pools) >= $this -> poolSize){

        }else{
            array_push($this -> _pools, $conn);
        }
    }

    /**
     * 查询多个符合条件的条目 返回二维数组
     * @param $fields 查询的字段(仅支持数组)
     * @param $where  查询条件(条件)
     * @param $limit  查询的条数
     * @param $orderBy 排序
     */
    public function query($fields="*",$where='',$limit='',$orderBy=""){
        if($this -> getConn() == null){
            $this -> init();
        }
        $conn = $this -> getConn();
        $where = $where == ''?"":" WHERE ".$where;
        $limit = $limit == ''?"":" LIMIT ".$limit;
        $orderBy = $orderBy == ""?"":" ORDER BY ".$orderBy;
        $sql = "";
        if(is_array($fields)){
            $filedsStr = implode(",",$fields);
            $sql = "SELECT ".$filedsStr." FROM ".$this -> table.$where.$limit.$orderBy;
            $this -> sql = $sql;
            $res = $conn -> prepare($sql);
            $res -> execute();
            $resultArr = $res -> fetchAll(\PDO::FETCH_ASSOC);
            $this -> releaseConn($conn);
            return $resultArr;
        }else{
            die('Class Db() function query params($fields) must be array!');
        }   
    }
}
