<?php declare(strict_types=1);

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\XmlRoot("price")
 * @Serializer\AccessType("property")
 */
class CurrencyAwarePrice
{
    /**
     * @Serializer\XmlAttribute
     * @Serializer\Type("string")
     */
    private $currency;

    /**
     * @Serializer\XmlValue
     * @Serializer\Type("double")
     */
    private $amount;

    public function __construct($amount, $currency = 'EUR')
    {
        $this->currency = $currency;
        $this->amount = $amount;
    }
}
