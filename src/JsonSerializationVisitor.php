<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

class JsonSerializationVisitor extends GenericSerializationVisitor
{
    /**
     * @var int
     */
    private $options = 0;

    /**
     * {@inheritdoc}
     */
    public function getResult(): string
    {
        $result = @\json_encode($this->getRoot(), $this->options);

        switch (\json_last_error()) {
            case JSON_ERROR_NONE:
                return $result;

            case JSON_ERROR_UTF8:
                throw new \RuntimeException('Your data could not be encoded because it contains invalid UTF8 characters.');
            default:
                throw new \RuntimeException(\sprintf('An error occurred while encoding your data (error code %d).', \json_last_error()));
        }
    }

    public function getOptions(): int
    {
        return $this->options;
    }

    public function setOptions(int $options): void
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function visitHash($data, Type $type, Context $context)
    {
        $result = parent::visitHash($data, $type, $context);

        if ($type->hasParam(1) && 0 === \count($result)) {
            // ArrayObject is specially treated by the json_encode function and
            // serialized to { } while a mere array would be serialized to [].
            $this->setData($result = new \ArrayObject());
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context, ObjectConstructorInterface $objectConstructor = null)
    {
        $rs = parent::visitObject($metadata, $data, $type, $context, $objectConstructor);

        // Force JSON output to "{}" instead of "[]" if it contains either no properties or all properties are null.
        if (0 === \count($rs)) {
            $this->setData($rs = new \ArrayObject());
        }

        return $rs;
    }
}
