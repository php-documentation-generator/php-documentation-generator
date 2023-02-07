# \PhpDocumentGenerator\Tests\Command\App\Serializer\DateTimeDenormalizer

### Implements:

> [`Symfony\Component\Serializer\Normalizer\DenormalizerInterface`](https://symfony.com/doc/current/index.html)
>
> [`Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface`](https://symfony.com/doc/current/index.html)
>
> `PhpDocumentGenerator\Tests\Command\App\Services\IgnoredInterface`

## Properties:

### <a href="#property-denormalizer" id="property-denormalizer">§</a> protected $denormalizer

Types:

> `DenormalizerInterface`

## Methods:

### <a href="#method-denormalize" id="method-denormalize">§</a> public function denormalize($data, string $type, ?string $format``, array $context`[]`): `DateTimeInterface`

Denormalizes data back into an object of the given class.

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

Throws:

> [`BadMethodCallException`](https://php.net/class.badmethodcallexception) Occurs when the normalizer is not called in an expected context
>
> [`InvalidArgumentException`](https://php.net/class.invalidargumentexception) Occurs when the arguments are not coherent or not supported
>
> [`UnexpectedValueException`](https://php.net/class.unexpectedvalueexception) Occurs when the item cannot be hydrated with the given data
>
> `ExtraAttributesException` Occurs when the item doesn't have attribute to receive given data
>
> [`LogicException`](https://php.net/class.logicexception) Occurs when the normalizer is not supposed to denormalize
>
> [`RuntimeException`](https://php.net/class.runtimeexception) Occurs if the class cannot be instantiated
>
> `ExceptionInterface` Occurs for all the other cases of errors
>
> `NotNormalizableValueException`

---

### <a href="#method-supportsDenormalization" id="method-supportsDenormalization">§</a> public function supportsDenormalization($data, string $type, ?string $format``, array $context`[]`): `bool`

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

### <a href="#method-setDenormalizer" id="method-setDenormalizer">§</a> public function setDenormalizer(Symfony\Component\Serializer\Normalizer\DenormalizerInterface $denormalizer)
