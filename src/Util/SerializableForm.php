<?php

declare(strict_types=1);

namespace Kcs\Serializer\Util;

use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

use function assert;

/** @Xml\Root(name="form") */
#[Xml\Root(name: 'form')]
final class SerializableForm
{
    /**
     * @Type("array<Symfony\Component\Form\FormError>")
     * @var FormError[]
     */
    #[Type('array<Symfony\Component\Form\FormError>')]
    private array $errors = [];

    /**
     * @Type("array<Kcs\Serializer\Util\SerializableForm>")
     * @Xml\XmlList(entry="form", inline=true)
     * @var static[]
     */
    #[Type('array<Kcs\Serializer\Util\SerializableForm>')]
    #[Xml\XmlList(entry: 'form', inline: true)]
    private array $children = [];

    /**
     * @Type("string")
     * @Xml\Attribute()
     */
    #[Type('string')]
    #[Xml\Attribute()]
    private string $name;

    public function __construct(FormInterface $form)
    {
        $this->name = $form->getName();

        foreach ($form->getErrors(false) as $error) {
            assert($error instanceof FormError);
            $this->errors[] = $error;
        }

        foreach ($form->all() as $child) {
            $this->children[] = new static($child);
        }
    }

    /** @return FormError[] */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return static[] */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
