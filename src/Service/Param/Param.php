<?php

namespace Experteam\ApiBaseBundle\Service\Param;

use Exception;
use Experteam\ApiBaseBundle\Security\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class Param implements ParamInterface
{
    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Param constructor.
     *
     * @param HttpClientInterface $client
     * @param ParameterBagInterface $parameterBag
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(HttpClientInterface $client, ParameterBagInterface $parameterBag, TokenStorageInterface $tokenStorage)
    {
        $this->client = $client;
        $this->parameterBag = $parameterBag;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param array $values
     * @return array|string
     * @throws Exception
     */
    public function findByName(array $values)
    {
        $result = [];
        $cfgParams = $this->parameterBag->get('experteam_api_base.params');
        $url = ($cfgParams['remote_url'] ?? null);

        if (Validation::createValidator()->validate($url, [new Assert\Url(), new Assert\NotNull()])->count() == 0) {
            $token = $this->tokenStorage->getToken();

            if (!is_null($token)) {
                /** @var User $user */
                $user = $token->getUser();

                if ($user instanceof User) {
                    try {
                        $response = $this->client->request('POST', $url, [
                            'auth_bearer' => $user->getToken(),
                            'body' => [
                                'parameters' => array_map(function ($v) {
                                    return ['name' => $v];
                                }, $values)
                            ]
                        ])->toArray(false);
                    } catch (ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
                        throw new Exception('ExperteamApiBaseBundle: Error connecting to remote url params.');
                    }

                    if ($response['status'] == 'success' && isset($response['data']['parameters'])) {
                        foreach ($response['data']['parameters'] as $paramValue) {
                            $result[$paramValue['name']] = $paramValue['value'];
                        }
                    }
                }
            }
        }

        if (empty($result)) {
            foreach ($values as $v) {
                $result[$v] = $cfgParams['defaults'][$v] ?? null;
            }
        }

        return ((count($result) === 1) ? reset($result) : $result);
    }

    /**
     * @param string $name
     * @return string
     * @throws Exception
     */
    public function findOneByName(string $name)
    {
        return $this->findByName([$name]);
    }
}