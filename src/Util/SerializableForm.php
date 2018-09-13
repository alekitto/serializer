<?php declare(strict_types=1);

namespace Kcs\Serializer\Util;

use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlAttribute;
use Kcs\Serializer\Annotation\XmlList;
use Kcs\Serializer\Annotation\XmlRoot;
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
     * @Type("array<Kcs\Serializer\Util\SerializableForm>")
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

    /**
     * @return FormError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return static[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
