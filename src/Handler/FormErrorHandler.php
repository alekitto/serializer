<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\Util\SerializableForm;
use Kcs\Serializer\VisitorInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;

use function assert;
use function gettype;
use function is_object;
use function Safe\sprintf;

class FormErrorHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        return [
            [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => Form::class,
                'method' => 'serializeForm',
            ],
            [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => FormError::class,
                'method' => 'serializeFormError',
            ],
        ];
    }

    public function __construct(private LegacyTranslatorInterface | TranslatorInterface | null $translator = null)
    {
    }

    public function serializeForm(VisitorInterface $visitor, Form $form, Type $type, Context $context): mixed
    {
        $serializableForm = new SerializableForm($form);
        $metadata = $context->getMetadataFactory()->getMetadataFor($serializableForm);
        assert($metadata instanceof ClassMetadata);

        return $visitor->visitObject($metadata, $serializableForm, $type, $context);
    }

    public function serializeFormError(VisitorInterface $visitor, FormError $formError, Type $type, Context $context): mixed
    {
        return $visitor->visitString($this->getErrorMessage($formError), $type, $context);
    }

    private function getErrorMessage(FormError $error): string
    {
        if ($this->translator === null) {
            return $error->getMessage();
        }

        if ($error->getMessagePluralization() !== null) {
            if ($this->translator instanceof TranslatorInterface) {
                return $this->translator->trans(
                    $error->getMessageTemplate(),
                    ['%count%' => $error->getMessagePluralization()] + $error->getMessageParameters(),
                    'validators'
                );
            }

            return $this->translator->transChoice(
                $error->getMessageTemplate(),
                $error->getMessagePluralization(),
                $error->getMessageParameters(),
                'validators'
            );
        }

        return $this->translator->trans($error->getMessageTemplate(), $error->getMessageParameters(), 'validators');
    }
}
