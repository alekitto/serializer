<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\Util\SerializableForm;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\GraphNavigator;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;

class FormErrorHandler implements SubscribingHandlerInterface
{
    private $translator;

    public static function getSubscribingMethods()
    {
        $methods = array();
        foreach (array('xml', 'json', 'yml') as $format) {
            $methods[] = array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'type' => 'Symfony\Component\Form\Form',
                'format' => $format,
                'method' => 'serializeForm'
            );
            $methods[] = array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'type' => 'Symfony\Component\Form\FormError',
                'format' => $format,
                'method' => 'serializeFormError'
            );
        }

        return $methods;
    }

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function serializeForm(VisitorInterface $visitor, Form $form, array $type, Context $context)
    {
        $serializableForm = new SerializableForm($form);
        $metadata = $context->getMetadataFactory()->getMetadataFor($serializableForm);

        return $visitor->visitObject($metadata, $serializableForm, $type, $context);
    }

    public function serializeFormError(VisitorInterface $visitor, FormError $formError, array $type, Context $context)
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
