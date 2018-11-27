<?php

if(!function_exists("dump")){
    function dump($res){
        echo "<pre>";
        var_dump($res);
        echo "</pre>";
    }
}