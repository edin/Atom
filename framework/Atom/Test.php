<?php

class SomeType {
    public $Database;
    public $UserRepository;
    public $Response;
    public $Request;
    public $Container;
}

$function = function (int $id, SomeType $type) {

};

$result = $function instanceof \Closure;
var_dump($result);

$info = new \ReflectionFunction($function);

var_dump($info->getParameters());

$r = new \ReflectionClass(new SomeType);

$props = $r->getProperties(\ReflectionProperty::IS_PUBLIC);

foreach($props as $prop) {
    echo $prop->getName(), "\n";
}