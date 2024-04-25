<?php

namespace Kcs\Serializer\Tests\Fixtures\Kernel\Controller;

use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Tests\Fixtures\IndexedCommentsBlogPost;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

class TestController
{
    private ContainerInterface $container;

    #[Required]
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function serializeAction($format)
    {
        $serializer = $this->container->get(SerializerInterface::class);

        return new Response($serializer->serialize(new IndexedCommentsBlogPost(), $format));
    }
}
