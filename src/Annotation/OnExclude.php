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

namespace Kcs\Serializer\Annotation;

use Kcs\Serializer\Exception\RuntimeException;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class OnExclude
{
    const NULL = 'null';
    const SKIP = 'skip';

    public $policy = self::NULL;

    public function __construct(array $values = null)
    {
        if (empty($values)) {
            return;
        }

        if (! is_string($values['value'])) {
            throw new RuntimeException('"value" must be a string.');
        }

        $this->policy = strtolower($values['value']);

        if (self::NULL !== $this->policy && self::SKIP !== $this->policy) {
            throw new RuntimeException('OnExclude policy must either be "null", or "skip".');
        }
    }
}
