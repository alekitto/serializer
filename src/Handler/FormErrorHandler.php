<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\Util\SerializableForm;
use Kcs\Serializer\VisitorInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;

class FormErrorHandler implements SubscribingHandlerInterface
{
    private $translator;

    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => 'Symfony\Component\Form\Form',
                'method' => 'serializeForm',
            ], [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => 'Symfony\Component\Form\FormError',
                'method' => 'serializeFormError',
            ],
        ];
    }

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function serializeForm(VisitorInterface $visitor, Form $form, Type $type, Context $context)
    {
        $serializableForm = new SerializableForm($form);
        $metadata = $context->getMetadataFactory()->getMetadataFor($serializableForm);

        return $visitor->visitObject($metadata, $serializableForm, $type, $context);
    }

    public function serializeFormError(VisitorInterface $visitor, FormError $formError, Type $type, Context $context)
    {
        return $visitor->visitString($this->getErrorMessage($formError), $type, $context);
    }

    private function getErrorMessage(FormError $error)
    {
        if (null !== $error->getMessagePluralization()) {
            return $this->translator->transChoice($error->getMessageTemplate(), $error->getMessagePluralization(), $error->getMessageParameters(), 'validators');
        }

        return $this->translator->trans($error->getMessageTemplate(), $error->getMessageParameters(), 'validators');
    }
}
