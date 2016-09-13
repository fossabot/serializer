<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Modifications copyright (c) 2016 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\EventDispatcher\Subscriber;

use Doctrine\Common\Persistence\Proxy;
use Kcs\Serializer\EventDispatcher\Events;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DoctrineProxySubscriber implements EventSubscriberInterface
{
    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getData();

        if (! $object instanceof Proxy) {
            return;
        }

        $object->__load();
        $type = $event->getType();

        if ($type->is(get_class($object))) {
            $type->setName(get_parent_class($object));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_SERIALIZE => ['onPreSerialize', 20],
        ];
    }
}
