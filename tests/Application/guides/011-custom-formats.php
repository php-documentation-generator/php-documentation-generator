<?php
//The API Platform content negotiation system is extendable. You can add support for formats not available by default by creating custom normalizer and encoders. Refer to the Symfony documentation to learn how to create and register such classes.
//
//Then, register the new format in the configuration:
//
//# api/config/packages/api_platform.yaml
//api_platform:
//    formats:
//        # ...
//        myformat: ['application/vnd.myformat']
//
//API Platform will automatically call the serializer with your defined format name as format parameter during the deserialization process (myformat in the example). It will then return the result to the client with the requested MIME type using its built-in responder. For non-standard formats, a vendor, vanity or unregistered MIME type should be used.
//Reusing the API Platform Infrastructure
//
//Using composition is the recommended way to implement a custom normalizer. You can use the following template to start your own implementation of CustomItemNormalizer:
//
//# api/config/services.yaml
//services:
//'App\Serializer\CustomItemNormalizer':
//arguments: [ '@api_platform.serializer.normalizer.item' ]
# Uncomment if you don't use the autoconfigure feature
#tags: [ 'serializer.normalizer' ]

# ...

// api/src/Serializer/CustomItemNormalizer.php
//namespace App\Serializer {
//
//    use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
//    use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
//
//    final class CustomItemNormalizer implements NormalizerInterface, DenormalizerInterface
//    {
//        private NormalizerInterface&DenormalizerInterface $normalizer;
//
//        public function __construct(NormalizerInterface $normalizer)
//        {
//            if (!$normalizer instanceof DenormalizerInterface) {
//                throw new \InvalidArgumentException('The normalizer must implement the DenormalizerInterface');
//            }
//
//            $this->normalizer = $normalizer;
//        }
//
//        public function denormalize($data, $class, $format = null, array $context = [])
//        {
//            return $this->normalizer->denormalize($data, $class, $format, $context);
//        }
//
//        public function supportsDenormalization($data, $type, $format = null)
//        {
//            return $this->normalizer->supportsDenormalization($data, $type, $format);
//        }
//
//        public function normalize($object, $format = null, array $context = [])
//        {
//            return $this->normalizer->normalize($object, $format, $context);
//        }
//
//        public function supportsNormalization($data, $format = null)
//        {
//            return $this->normalizer->supportsNormalization($data, $format);
//        }
//    }
//}


//For example if you want to make the csv format work for even complex entities with a lot of hierarchy, you have to flatten or remove overly complex relations:


// api/src/Serializer/CustomItemNormalizer.php
//namespace App\Serializer {
//
//    abstract class CustomCSVItemNormalizer implements NormalizerInterface, DenormalizerInterface
//    {
//        // ...
//
//        public function normalize($object, $format = null, array $context = [])
//        {
//            $result = $this->normalizer->normalize($object, $format, $context);
//
//            if ('csv' !== $format || !is_array($result)) {
//                return $result;
//            }
//
//            foreach ($result as $key => $value) {
//                if (is_array($value) && array_keys(array_keys($value)) === array_keys($value)) {
//                    unset($result[$key]);
//                }
//            }
//
//            return $result;
//        }
//
//        // ...
//    }
//}


//Contributing Support for New Formats

//Adding support for standard formats upstream is welcome! We'll be glad to merge new encoders and normalizers in API Platform.
