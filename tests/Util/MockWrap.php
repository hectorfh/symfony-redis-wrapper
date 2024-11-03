<?php


namespace IpartnersBundle\Tests\Util;


class MockWrap
{

    private $target;

    public function __construct($target = NULL)
    {
        $this->target = $target;
    }

    public function __call($closure, $args)
    {
        if (property_exists($this, $closure)) {
            return call_user_func_array($this->{$closure}, $args);
        }
        return call_user_func_array([$this->target, $closure], $args);
    }

}
