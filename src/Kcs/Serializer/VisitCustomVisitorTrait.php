<?php

namespace Kcs\Serializer;

use Kcs\Serializer\Type\Type;

trait VisitCustomVisitorTrait
{
    public function visitCustom(callable $handler, $data, Type $type, Context $context)
    {
        $args = func_get_args();

        $handler = array_shift($args);
        array_unshift($args, $this);

        return $this->data = call_user_func_array($handler, $args);
    }
}
