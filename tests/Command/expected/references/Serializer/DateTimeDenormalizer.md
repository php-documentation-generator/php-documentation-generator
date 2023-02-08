# PhpDocumentGenerator\Tests\Command\App\Serializer\DateTimeDenormalizer

### Implements:

> `Symfony\Component\Serializer\Normalizer\DenormalizerInterface`
>
> `Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface`
>
> `PhpDocumentGenerator\Tests\Command\App\Services\IgnoredInterface`

## Properties:

### <a href="#property-denormalizer" id="property-denormalizer">§</a> protected $denormalizer

Types:

> `Symfony\Component\Serializer\Normalizer\DenormalizerInterface`

## Methods:

### <a href="#method-denormalize" id="method-denormalize">§</a> public function denormalize($data, `string` $type, `?string` $format, `array` $context = `[]`): [`DateTimeInterface`](https://php.net/class.datetimeinterface)

This method throws multiple exception and returns multiple types to ensure the types are correctly imported.

Denormalizes data back into an object of the given class.

Also, the parent documentation should be placed in the middle of this method documentation.

Additional info:

> `mixed` $data Data to restore
>
> `string` $type The expected class to instantiate
>
> `string` $format Format the given data was extracted from
>
> `array` $context Options available to the denormalizer

Returns:

> `mixed`
>
> `string`|[`stdClass`](https://php.net/class.stdclass)|[`DateTimeInterface`](https://php.net/class.datetimeinterface)
>
> `array<int, DateTimeInterface>`

Throws:

> `Symfony\Component\Serializer\Exception\BadMethodCallException` Occurs when the normalizer is not called in an expected context
>
> `Symfony\Component\Serializer\Exception\InvalidArgumentException` Occurs when the arguments are not coherent or not supported
>
> `Symfony\Component\Serializer\Exception\UnexpectedValueException` Occurs when the item cannot be hydrated with the given data
>
> `Symfony\Component\Serializer\Exception\ExtraAttributesException` Occurs when the item doesn't have attribute to receive given data
>
> `Symfony\Component\Serializer\Exception\LogicException` Occurs when the normalizer is not supposed to denormalize
>
> `Symfony\Component\Serializer\Exception\RuntimeException` Occurs if the class cannot be instantiated
>
> `Symfony\Component\Serializer\Exception\ExceptionInterface` Occurs for all the other cases of errors
>
> `Symfony\Component\Serializer\Exception\NotNormalizableValueException`|[`RuntimeException`](https://php.net/class.runtimeexception)|`string`
>
> [`LogicException`](https://php.net/class.logicexception)

---

### <a href="#method-supportsDenormalization" id="method-supportsDenormalization">§</a> public function supportsDenormalization($data, `string` $type, `?string` $format, `array` $context = `[]`): `bool`

Checks whether the given class is supported for denormalization by this normalizer.

Additional info:

> `mixed` $data Data to denormalize from
>
> `string` $type The class to which the data should be denormalized
>
> `string` $format The format being deserialized from
>
> `array` $context Options available to the denormalizer

Returns:

> `bool`

---

### <a href="#method-setDenormalizer" id="method-setDenormalizer">§</a> public function setDenormalizer(`Symfony\Component\Serializer\Normalizer\DenormalizerInterface` $denormalizer)
