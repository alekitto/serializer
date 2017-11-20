<?php

namespace Kcs\Serializer\Tests\Fixtures\Kernel\Controller;

use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Tests\Fixtures\IndexedCommentsBlogPost;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    public function serializeAction($format)
    {
        $serializer = $this->get(SerializerInterface::class);

        return new Response($serializer->serialize(new IndexedCommentsBlogPost(), $format));
    }
}
