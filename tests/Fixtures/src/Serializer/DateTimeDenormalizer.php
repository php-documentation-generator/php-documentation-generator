<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpDocumentGenerator\Tests\Fixtures\Serializer;

use PhpDocumentGenerator\Tests\Fixtures\Services\IgnoredInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException as FooBarException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class DateTimeDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface, IgnoredInterface
{
    use DenormalizerAwareTrait;

    /**
     * This method throws multiple exception and returns multiple types to ensure the types are correctly imported.
     *
     * {@inheritdoc}
     *
     * Also, the parent documentation should be placed in the middle of this method documentation.
     *
     * @throws FooBarException|\RuntimeException
     * @throws \LogicException
     *
     * @return mixed                               the data
     * @return string|\stdClass|\DateTimeInterface
     * @return array<int, \DateTimeInterface>
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): \DateTimeInterface
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return true;
    }
}
