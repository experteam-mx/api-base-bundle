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
    const GLOBAL = 'GLOBAL';
    const COUNTRY = 'Country';
    const COMPANY_COUNTRY = 'CompanyCountry';
    const LOCATION = 'Location';
    const INSTALLATION = 'Installation';
    const EMPLOYEE = 'Employee';

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
     * @param string|null $modelType
     * @param string|null $modelId
     * @return array|string
     * @throws Exception
     */
    public function findByName(array $values, string $modelType = null, string $modelId = null)
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
                        $authentication = !is_null($user->getToken())
                            ? ['auth_bearer' => $user->getToken()]
                            : ['headers' => ['AppKey' => $user->getAppkey()]];

                        $response = $this->client->request('POST', $url, array_merge([
                            'body' => [
                                'parameters' => array_map(function ($v) use ($modelType, $modelId) {
                                    return array_merge(
                                        ['name' => $v],
                                        !is_null($modelType) && !is_null($modelId) ? [
                                            'model_type' => $modelType,
                                            'model_id' => $modelId
                                        ] : []
                                    );
                                }, $values)
                            ]
                        ], $authentication))->toArray(false);
                    } catch (ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
                        throw new Exception('ExperteamApiBaseBundle: Error connecting to remote url params.');
                    }

                    if ($response['status'] == 'success' && isset($response['data']['parameters'])) {
                        foreach ($response['data']['parameters'] as $paramValue) {
                            $result[$paramValue['name']] = $paramValue['value'];
                        }

                        foreach (array_keys(array_diff_key(array_flip($values), $result)) as $name) {
                            $result[$name] = $this->getDefault($name);
                        }
                    }
                }
            }
        }

        if (empty($result)) {
            foreach ($values as $v) {
                $result[$v] = $this->getDefault($v);
            }
        }

        return ((count($result) === 1) ? reset($result) : $result);
    }

    /**
     * @param string $name
     * @param string|null $modelType
     * @param string|null $modelId
     * @return array|string
     * @throws Exception
     */
    public function findOneByName(string $name, string $modelType = null, string $modelId = null)
    {
        return $this->findByName([$name], $modelType, $modelId);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    protected function getDefault(string $name)
    {
        $cfgParams = $this->parameterBag->get('experteam_api_base.params');
        $default = $cfgParams['defaults'][$name] ?? null;

        if (is_string($default)) {
            $value = json_decode(sprintf('{"v": %s}', $default), true);
            if (json_last_error() != JSON_ERROR_NONE)
                $value = json_decode(sprintf('{"v": "%s"}', str_replace('"', '\"', $default)), true);
            $default = $value['v'];
        }

        return $default;
    }
}