<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
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

use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on Doctrine 2 metadata.
 */
class DoctrineTypeLoader extends AbstractDoctrineTypeLoader
{
    protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata)
    {
        return false;
    }

    protected function setDiscriminator(DoctrineClassMetadata $doctrineMetadata, ClassMetadata $classMetadata)
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $doctrineMetadata */
        if (empty($classMetadata->discriminatorMap) && ! $classMetadata->discriminatorDisabled
            && ! empty($doctrineMetadata->discriminatorMap) && $doctrineMetadata->isRootEntity()
        ) {
            $classMetadata->setDiscriminator(
                $doctrineMetadata->discriminatorColumn['name'],
                $doctrineMetadata->discriminatorMap,
                []
            );
        }
    }

    protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadata $propertyMetadata)
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $doctrineMetadata */
        $propertyName = $propertyMetadata->name;
        if ($doctrineMetadata->hasField($propertyName) && $fieldType = $this->normalizeFieldType($doctrineMetadata->getTypeOfField($propertyName))) {
            $propertyMetadata->setType($fieldType);
        } elseif ($doctrineMetadata->hasAssociation($propertyName)) {
            $targetEntity = $doctrineMetadata->getAssociationTargetClass($propertyName);

            if (null === $targetMetadata = $this->tryLoadingDoctrineMetadata($targetEntity)) {
                return;
            }

            // For inheritance schemes, we cannot add any type as we would only add the super-type of the hierarchy.
            // On serialization, this would lead to only the supertype being serialized, and properties of subtypes
            // being ignored.
            if ($targetMetadata instanceof DoctrineClassMetadata && ! $targetMetadata->isInheritanceTypeNone()) {
                return;
            }

            if (! $doctrineMetadata->isSingleValuedAssociation($propertyName)) {
                $targetEntity = "ArrayCollection<{$targetEntity}>";
            }

            $propertyMetadata->setType($targetEntity);
        }
    }
}
