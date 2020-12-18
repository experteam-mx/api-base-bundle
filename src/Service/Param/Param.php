<?php

namespace Experteam\ApiBaseBundle\Service\Param;

use Doctrine\ORM\NonUniqueResultException;
use Experteam\ApiBaseBundle\Entity\Parameter;
use Experteam\ApiBaseBundle\Repository\ParameterRepository;
use Experteam\ApiBaseBundle\Security\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Param implements ParamInterface
{
    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $url;

    /**
     * Param constructor.
     *
     * @param HttpClientInterface $client
     * @param ContainerInterface $container
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(HttpClientInterface $client, ContainerInterface $container, TokenStorageInterface $tokenStorage)
    {
        $this->client = $client;
        $this->container = $container;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->url = $this->container->getParameter('app.apis.companies.url.parameters_get');
    }

    /**
     * @param array $values
     * @return array|string
     * @throws NonUniqueResultException
     */
    public function findByName(array $values)
    {
        $result = [];
        $content = [];
        $exception = true;
        /** @var ParameterRepository $parameterRepository */
        $parameterRepository = $this->container->get('doctrine')->getRepository(Parameter::class);
        $parameters = array();
        try {
            foreach ($values as $value) {
                array_push($parameters,array('name' => $value));
            }
            $response = $this->client->request('POST', $this->url, [
                'auth_bearer' => $this->user->getToken(),
                'body' => ['parameters' => $parameters]
            ]);

            $content = $response->toArray(false);
            $exception = false;
        } catch (ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
        }

        if (!$exception && isset($content['status']) && $content['status'] === 'success' && isset($content['data']['parameters'])) {
            $data = $content['data']['parameters'];

            foreach ($values as $value) {
                $that = false;
                foreach ($data as $datum) {
                    if($datum['name'] == $value){
                        $that = true;
                        $result[$value] = $datum['value'];
                        break;
                    }

                }
                if(!$that){
                    $result[$value] = $parameterRepository->findOneByName($value);
                }

            }
        } else {
            $result = $parameterRepository->findByName($values);
        }

        return ((count($result) === 1) ? array_values($result)[0] : $result);
    }

    /**
     * @param string $name
     * @return string
     * @throws NonUniqueResultException
     */
    public function findOneByName(string $name)
    {
        return $this->findByName(compact('name'));
    }
}