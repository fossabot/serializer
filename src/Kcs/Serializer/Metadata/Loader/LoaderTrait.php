<?php

/*
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Common\Inflector\Inflector;

trait LoaderTrait
{
    private function createAnnotationObject($name)
    {
        $annotationClass = 'Kcs\\Serializer\\Annotation\\'.Inflector::classify($name);
        $annotation = new $annotationClass();

        return $annotation;
    }

    private function getDefaultPropertyName($annotation)
    {
        $reflectionAnnotation = new \ReflectionClass($annotation);
        $properties = $reflectionAnnotation->getProperties();

        if (isset($properties[0])) {
            return $properties[0]->name;
        }
    }
}
