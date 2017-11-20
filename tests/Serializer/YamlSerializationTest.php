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

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Serializer\Exception\RuntimeException;

class YamlSerializationTest extends BaseSerializationTest
{
    protected function getContent($key)
    {
        if (! file_exists($file = __DIR__.'/yml/'.$key.'.yml')) {
            throw new RuntimeException(sprintf('The content with key "%s" does not exist.', $key));
        }

        return file_get_contents($file);
    }

    protected function getFormat()
    {
        return 'yml';
    }

    protected function hasDeserializer()
    {
        return true;
    }
}
