<?php

use Phalcon\Mvc\Model;

class Categories extends Model
{
    public $id;
    
    public $name;
    
    public $parentid;
    
    public $url;
}

