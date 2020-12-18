<?php

namespace Experteam\ApiBaseBundle\DataFixtures;

use Experteam\MicroservicesBaseBundle\Entity\Parameter;
use Doctrine\Persistence\ObjectManager;

class ParameterFixtures
{
    /**
     * @param ObjectManager $manager
     * @param array $parameters
     */
    public function load(ObjectManager $manager, array $parameters)
    {
        $parameterRepository = $manager->getRepository(Parameter::class);

        foreach ($parameters as $value) {
            $name = $value['name'];
            $parameter = $parameterRepository->findOneBy(['name' => $name]);

            if (is_null($parameter)) {
                $parameter = new Parameter();
                $parameter->setName($name);
            }

            $parameter->setValue($value['value']);
            $manager->persist($parameter);
        }

        $manager->flush();
    }
}