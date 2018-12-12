<?php

namespace Kcs\Serializer\Tests\Fixtures\Kernel\Controller;

use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Tests\Fixtures\IndexedCommentsBlogPost;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;

class TestController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function serializeAction($format)
    {
        $serializer = $this->container->get(SerializerInterface::class);

        return new Response($serializer->serialize(new IndexedCommentsBlogPost(), $format));
    }
}
