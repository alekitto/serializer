<?php

namespace JMS\Serializer\Util;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\XmlRoot;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * @XmlRoot(name="form")
 */
class SerializableForm
{
    /**
     * @Type("array<Symfony\Component\Form\FormError>")
     *
     * @var FormError[]
     */
    private $errors = [];

    /**
     * @Type("array<JMS\Serializer\Util\SerializableForm>")
     * @XmlList(entry="form", inline=true)
     *
     * @var static[]
     */
    private $children = [];

    /**
     * @Type("string")
     * @XmlAttribute()
     *
     * @var string
     */
    private $name;

    public function __construct(FormInterface $form)
    {
        $this->name = $form->getName();

        foreach ($form->getErrors() as $error) {
            $this->errors[] = $error;
        }

        foreach ($form->all() as $child) {
            $this->children[] = new static($child);
        }
    }
}
