<?php declare(strict_types=1);

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Kcs\Serializer\XmlSerializationVisitor;

class DateHandler implements SubscribingHandlerInterface
{
    private $defaultFormat;
    private $defaultTimezone;
    private $xmlCData;

    public static function getSubscribingMethods()
    {
        yield [
            'type' => \DateTime::class,
            'direction' => Direction::DIRECTION_DESERIALIZATION,
            'method' => 'deserializeDateTime',
        ];

        foreach ([\DateTimeImmutable::class, \DateTimeInterface::class] as $class) {
            yield [
                'type' => $class,
                'direction' => Direction::DIRECTION_DESERIALIZATION,
                'method' => 'deserializeDateTimeImmutable',
            ];
        }


        foreach ([\DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class] as $class) {
            yield [
                'type' => $class,
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'method' => 'serializeDateTime',
            ];
        }
        yield [
            'type' => \DateInterval::class,
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'method' => 'serializeDateInterval',
        ];
    }

    public function __construct($defaultFormat = \DateTime::ISO8601, $defaultTimezone = 'UTC', $xmlCData = true)
    {
        $this->defaultFormat = $defaultFormat;
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);
        $this->xmlCData = $xmlCData;
    }

    public function serializeDateTime(VisitorInterface $visitor, \DateTimeInterface $date, Type $type, Context $context)
    {
        $format = $this->getFormat($type);
        if ('U' === $format) {
            return $visitor->visitInteger($date->getTimestamp(), $type, $context);
        }

        return $this->serialize($visitor, $date->format($this->getFormat($type)), $type, $context);
    }

    public function serializeDateInterval(VisitorInterface $visitor, \DateInterval $date, Type $type, Context $context)
    {
        return $this->serialize($visitor, $this->formatInterval($date), $type, $context);
    }

    public function deserializeDateTime(VisitorInterface $visitor, $data, Type $type)
    {
        return $this->deserializeDateTimeInterface(\DateTime::class, $data, $type);
    }

    public function deserializeDateTimeImmutable(VisitorInterface $visitor, $data, Type $type)
    {
        return $this->deserializeDateTimeInterface(\DateTimeImmutable::class, $data, $type);
    }

    private function deserializeDateTimeInterface(string $class, $data, Type $type)
    {
        if (null === $data) {
            return null;
        }

        $timezone = $type->hasParam(1) ? new \DateTimeZone($type->getParam(1)) : $this->defaultTimezone;
        $format = $this->getFormat($type);
        $datetime = $class::createFromFormat($format, (string) $data, $timezone);

        if (false === $datetime) {
            throw new RuntimeException(sprintf('Invalid datetime "%s", expected format %s.', $data, $format));
        }

        return $datetime;
    }

    private function serialize(VisitorInterface $visitor, $data, Type $type, Context $context)
    {
        if ($visitor instanceof XmlSerializationVisitor && false === $this->xmlCData) {
            return $visitor->visitSimpleString($data);
        }

        return $visitor->visitString($data, $type, $context);
    }

    /**
     * @return string
     *
     * @param Type $type
     */
    private function getFormat(Type $type)
    {
        return $type->hasParam(0) ? $type->getParam(0) : $this->defaultFormat;
    }

    /**
     * @param \DateInterval $dateInterval
     *
     * @return string
     */
    public function formatInterval(\DateInterval $dateInterval)
    {
        $format = 'P';

        if (0 < $dateInterval->y) {
            $format .= $dateInterval->y.'Y';
        }

        if (0 < $dateInterval->m) {
            $format .= $dateInterval->m.'M';
        }

        if (0 < $dateInterval->d) {
            $format .= $dateInterval->d.'D';
        }

        if (0 < $dateInterval->h || 0 < $dateInterval->i || 0 < $dateInterval->s) {
            $format .= 'T';
        }

        if (0 < $dateInterval->h) {
            $format .= $dateInterval->h.'H';
        }

        if (0 < $dateInterval->i) {
            $format .= $dateInterval->i.'M';
        }

        if (0 < $dateInterval->s) {
            $format .= $dateInterval->s.'S';
        }

        return $format;
    }
}
