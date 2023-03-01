# [PhpDocumentGenerator\Tests\Fixtures\Serializer\DateTimeDenormalizer](docs/references/Serializer/DateTimeDenormalizer) implements Symfony\Component\Serializer\Normalizer\DenormalizerInterface, Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface, [PhpDocumentGenerator\Tests\Fixtures\Services\IgnoredInterface](docs/references/Services/IgnoredInterface)

## Properties:

### <a href="#property-denormalizer" id="property-denormalizer">§</a> protected Symfony\Component\Serializer\Normalizer\DenormalizerInterface $denormalizer

## Methods:

### <a href="#method-denormalize" id="method-denormalize">§</a> public function denormalize([mixed](https://php.net/mixed) $data, [string](https://php.net/string) $type, [string](https://php.net/string) $format, [array](https://php.net/array) $context): [mixed](https://php.net/mixed)|[string](https://php.net/string)|[stdClass](https://php.net/class.stdclass)|[DateTimeInterface](https://php.net/class.datetimeinterface)[array&lt;int, DateTimeInterface&gt;](https://php.net/array&lt;int, DateTimeInterface&gt;)

This method throws multiple exception and returns multiple types to ensure the types are correctly imported.

Denormalizes data back into an object of the given class.

Also, the parent documentation should be placed in the middle of this method documentation.

#### Parameters

  - [mixed](https://php.net/mixed) $data Data to restore
  - [string](https://php.net/string) $type The expected class to instantiate
  - [string](https://php.net/string) $format Format the given data was extracted from
  - [array](https://php.net/array) $context Options available to the denormalizer

#### Returns

  - [mixed](https://php.net/mixed) the data
  - [string](https://php.net/string)
  - [stdClass](https://php.net/class.stdclass)
  - [DateTimeInterface](https://php.net/class.datetimeinterface)
  - [array&lt;int, DateTimeInterface&gt;](https://php.net/array&lt;int, DateTimeInterface&gt;)

#### Throws

  - Symfony\Component\Serializer\Exception\BadMethodCallException Occurs when the normalizer is not called in an expected context
  - Symfony\Component\Serializer\Exception\InvalidArgumentException Occurs when the arguments are not coherent or not supported
  - Symfony\Component\Serializer\Exception\UnexpectedValueException Occurs when the item cannot be hydrated with the given data
  - Symfony\Component\Serializer\Exception\ExtraAttributesException Occurs when the item doesn't have attribute to receive given data
  - Symfony\Component\Serializer\Exception\LogicException Occurs when the normalizer is not supposed to denormalize
  - Symfony\Component\Serializer\Exception\RuntimeException Occurs if the class cannot be instantiated
  - Symfony\Component\Serializer\Exception\ExceptionInterface Occurs for all the other cases of errors
  - (Symfony\Component\Serializer\Exception\NotNormalizableValueException | RuntimeException)
  - [LogicException](https://php.net/class.logicexception)

---

### <a href="#method-supportsDenormalization" id="method-supportsDenormalization">§</a> public function supportsDenormalization([mixed](https://php.net/mixed) $data, [string](https://php.net/string) $type, [string](https://php.net/string) $format, [array](https://php.net/array) $context): [bool](https://php.net/bool)

Checks whether the given class is supported for denormalization by this normalizer.

#### Parameters

  - [mixed](https://php.net/mixed) $data Data to denormalize from
  - [string](https://php.net/string) $type The class to which the data should be denormalized
  - [string](https://php.net/string) $format The format being deserialized from
  - [array](https://php.net/array) $context Options available to the denormalizer

#### Returns

  - [bool](https://php.net/bool)

---

### <a href="#method-setDenormalizer" id="method-setDenormalizer">§</a> public function setDenormalizer(Symfony\Component\Serializer\Normalizer\DenormalizerInterface $denormalizer)

#### Parameters

  - Symfony\Component\Serializer\Normalizer\DenormalizerInterface $denormalizer