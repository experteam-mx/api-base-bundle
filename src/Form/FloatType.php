<?php

namespace Experteam\ApiBaseBundle\Form;

use Experteam\ApiBaseBundle\DataTransformer\FloatToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class FloatType extends AbstractType
{
    public function __construct(
        private readonly FloatToStringTransformer $transformer
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->transformer);
    }

    public function getParent(): string
    {
        return NumberType::class;
    }
}
