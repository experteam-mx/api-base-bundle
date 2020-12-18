<?php

namespace Experteam\ApiBaseBundle\Repository;

use Experteam\ApiBaseBundle\Entity\Parameter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Parameter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Parameter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Parameter[]    findAll()
 * @method Parameter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parameter::class);
    }

    /**
     * @param array $values
     * @return array
     */
    public function findByName(array $values): array
    {
        $data = [];
        $queryBuilder = $this->createQueryBuilder('p');

        /** @var Parameter[] $parameters */
        $parameters = $queryBuilder
            ->where($queryBuilder->expr()->in('p.name', $values))
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($parameters as $parameter) {
            $data[$parameter->getName()] = $parameter->getValue();
        }

        if (count($data) < count($values)) {
            $diffs = array_diff($values, array_keys($data));

            foreach ($diffs as $diff) {
                $data[$diff] = '';
            }
        }

        return $data;
    }

    /**
     * @param string $value
     * @return string
     * @throws NonUniqueResultException
     */
    public function findOneByName(string $value): string
    {
        /** @var Parameter|null $parameter */
        $parameter = $this->createQueryBuilder('p')
            ->where('p.name = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();

        return (is_null($parameter) ? '' : $parameter->getValue());
    }
}