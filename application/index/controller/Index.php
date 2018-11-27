<?php
namespace application\controller;
class Index{

    public function index(){
        echo json_encode(['status'=>1,'msg'=>'index page']);
    }
}