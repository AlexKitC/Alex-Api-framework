<?php
namespace application\controller;
class Home{

    public function index(){
        echo json_encode(['status'=>1,'msg'=>'Home page']);
    }

    public function home(){
        echo json_encode(['status'=>1,'msg'=>'Home page2']);
    }
}