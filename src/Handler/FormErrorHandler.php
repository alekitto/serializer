<?php declare(strict_types=1);

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

class FormErrorHandler implements SubscribingHandlerInterface
{
    /**
     * @var LegacyTranslatorInterface|TranslatorInterface
     */
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

    public function __construct($translator = null)
    {
        if (
            null !== $translator &&
            ! $translator instanceof LegacyTranslatorInterface && ! $translator instanceof TranslatorInterface
        ) {
            throw new \TypeError(\sprintf(
                'Argument 1 passed to %s constructor should be an instance of %s or %s, %s passed',
                __CLASS__,
                LegacyTranslatorInterface::class,
                TranslatorInterface::class,
                \is_object($translator) ? \get_class($translator) : \gettype($translator)
            ));
        }

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

    private function getErrorMessage(FormError $error): string
    {
        if (null === $this->translator) {
            return $error->getMessage();
        }

        if (null !== $error->getMessagePluralization()) {
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
