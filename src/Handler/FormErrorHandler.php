<?php

declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\Util\SerializableForm;
use Kcs\Serializer\VisitorInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class FormErrorHandler implements SubscribingHandlerInterface
{
    /** @var LegacyTranslatorInterface|TranslatorInterface|null */
    private $translator;

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

    /**
     * @param LegacyTranslatorInterface|TranslatorInterface|null $translator
     */
    public function __construct($translator = null)
    {
        if (
            $translator !== null &&
            ! $translator instanceof LegacyTranslatorInterface && ! $translator instanceof TranslatorInterface
        ) {
            throw new TypeError(sprintf('Argument 1 passed to %s constructor should be an instance of %s or %s, %s passed', self::class, LegacyTranslatorInterface::class, TranslatorInterface::class, is_object($translator) ? get_class($translator) : gettype($translator)));
        }

        $this->translator = $translator;
    }

    /**
     * @return mixed
     */
    public function serializeForm(VisitorInterface $visitor, Form $form, Type $type, Context $context)
    {
        $serializableForm = new SerializableForm($form);
        $metadata = $context->getMetadataFactory()->getMetadataFor($serializableForm);

        return $visitor->visitObject($metadata, $serializableForm, $type, $context);
    }

    /**
     * @return mixed
     */
    public function serializeFormError(VisitorInterface $visitor, FormError $formError, Type $type, Context $context)
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
