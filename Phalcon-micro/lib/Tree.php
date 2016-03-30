<?php
use Phalcon\Mvc\Micro;

class Tree extends Micro
{

    private static $html;
    
    
    public function build($arrs)
    {
        return $this->build_tree($arrs, $parentid=0, $level=0);
    }
    
    private function build_tree($arrs, $parentid=0, $level=0) {
    foreach ($arrs as $arr) {
        if ($arr['parentid'] == $parentid) {
            self::$html .=  "<a href=\"" . $arr['uri']. "\" style=\"margin-left: " . $level*15 . "px;\">".$arr['name']."</a><br />";
            $this->build_tree($arrs, $arr['id'], $level+1);
        }
    }
    return self::$html;
    }
    
}
