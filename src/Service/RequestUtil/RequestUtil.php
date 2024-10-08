<?php

namespace Experteam\ApiBaseBundle\Service\RequestUtil;

use Exception;
use PhpDocReader\AnnotationException;
use PhpDocReader\PhpDocReader;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestUtil implements RequestUtilInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface  $validator)
    {
    }

    /**
     * @param string $data
     * @param string $model
     * @param string[]|null $groups
     * @return object
     */
    public function validate(string $data, string $model, ?array $groups = null): object
    {
        if (!$data) {
            throw new BadRequestHttpException('Empty body.');
        }

        $this->validateDataTypes($data, $model);

        try {
            $object = $this->serializer->deserialize($data, $model, 'json', [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]);
        } catch (Exception $e) {
            $message = $e->getMessage();
            throw new BadRequestHttpException("Invalid body: $message" . (str_ends_with($message, '.') ? '' : '.'));
        }

        $errors = $this->validator->validate($object, null, $groups);

        if ($errors->count()) {
            $_errors = [];

            /** @var ConstraintViolation $violation */
            foreach ($errors as $violation) {
                $_errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            throw new BadRequestHttpException(json_encode($this->buildMessages($_errors)));
        }

        return $object;
    }

    protected function validateDataTypes(string $data, string $model, bool $throwException = true): array
    {
        $validationTypes = $this->getValidationTypes($model);

        if (empty($validationTypes)) {
            return [];
        }

        $constraints = new Assert\Collection($validationTypes);
        $constraints->allowExtraFields = true;
        $constraints->allowMissingFields = true;

        $validationErrors = $this->validator->validate(json_decode($data, true), $constraints);
        $processedErrors = [];

        if ($validationErrors->count() > 0) {
            $errors = [];

            foreach ($validationErrors as $violation) {
                $errors[$this->formatPropertyPath($violation->getPropertyPath())] = $violation->getMessage();
            }

            $processedErrors = $this->buildMessages($errors);

            if ($throwException) {
                throw new BadRequestHttpException(json_encode($processedErrors));
            }
        }

        return $processedErrors;
    }

    private function getValidationTypes(string $model): array
    {
        try {
            $refClass = new ReflectionClass($model);
        } catch (ReflectionException) {
            return [];
        }

        $reader = new PhpDocReader();
        $validationTypes = [];

        foreach ($refClass->getProperties() as $refProperty) {
            $fieldName = $refProperty->getName();

            try {
                $type = $reader->getPropertyType($refProperty);
            } catch (AnnotationException) {
                continue;
            }

            if (is_null($type)) {
                continue;
            }

            if (in_array($type, ['string', 'int', 'float', 'bool', 'array'])) {
                $validationTypes[$fieldName] = new Assert\Type($type == 'float' ? 'numeric' : $type);
            } else {
                $childType = (!str_contains($type, '[]') ? $type : trim(explode('[]', $type)[0]));

                if (in_array($childType, ['string', 'int', 'float'])) {
                    $validationTypes[$fieldName] = [
                        new Assert\Type('array'),
                        new Assert\All([new Assert\Type($childType)])
                    ];
                } elseif (class_exists($childType)) {
                    $_validationTypes = $this->getValidationTypes($childType);

                    if (!empty($_validationTypes)) {
                        $_collection = new Assert\Collection($_validationTypes);
                        $_collection->allowMissingFields = true;
                        $_collection->allowExtraFields = true;

                        $validationTypes[$fieldName] = (!str_contains($type, '[]') ? $_collection : [
                            new Assert\Type('array'),
                            new Assert\All([$_collection])
                        ]);
                    }
                }
            }
        }

        return $validationTypes;
    }

    private function formatPropertyPath(string $propertyPath): string
    {
        $property = '';
        foreach (explode('][', substr($propertyPath, 1, -1)) as $prop) {
            $property .= preg_match('/^\d+$/', $prop) ? sprintf('[%s]', $prop) : sprintf('.%s', $prop);
        }
        return ltrim($property, '.');
    }

    public function buildMessages(array $errors): array
    {
        $result = [];

        foreach ($errors as $path => $message) {
            $temp = &$result;
            $path = str_replace(['children', '[', ']'], '', $path);

            foreach (explode('.', $path) as $key) {
                preg_match('/(.*)(\[.*?\])/', $key, $matches);

                if ($matches) {
                    $temp = &$temp[$matches[1]][$matches[2]];
                } else {
                    $temp = &$temp[$key];
                }
            }

            $temp = $message;
        }

        if (isset($result['data'])) {
            $result = $result['data'];
        }

        return $result;
    }
}
