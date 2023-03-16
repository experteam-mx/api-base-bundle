<?php

namespace Experteam\ApiBaseBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private $id;

    private $name;

    private $username;

    private $created_at;

    private $updated_at;

    private $token;

    private $appkey;

    private $roles = [];

    private $model_type;

    private $model_id;

    private $auth_type;

    private $language_id;

    /**
     * @var array
     */
    private $session;

    public function __construct(array $properties = [])
    {
        foreach ($properties as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(string $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return (array)$this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        // not needed for apps that do not check user passwords
        return null;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        // not needed for apps that do not check user passwords
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return array
     */
    public function getSession(): ?array
    {
        return $this->session;
    }

    /**
     * @param array|null $session
     * @return $this
     */
    public function setSession(?array $session): self
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getModelType(): ?string
    {
        return $this->model_type;
    }

    /**
     * @param string|null $model_type
     * @return $this
     */
    public function setModelType(?string $model_type): self
    {
        $this->model_type = $model_type;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModelId(): ?int
    {
        return $this->model_id;
    }

    /**
     * @param int|null $model_id
     * @return $this
     */
    public function setModelId(?int $model_id): self
    {
        $this->model_id = $model_id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAuthType(): ?string
    {
        return $this->auth_type;
    }

    /**
     * @param string|null $auth_type
     * @return $this
     */
    public function setAuthType(?string $auth_type): self
    {
        $this->auth_type = $auth_type;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLanguageId(): ?int
    {
        return $this->language_id;
    }

    /**
     * @param int|null $language_id
     * @return $this
     */
    public function setLanguageId(?int $language_id): self
    {
        $this->language_id = $language_id;

        return $this;
    }

    public function getAppkey(): ?string
    {
        return $this->appkey;
    }

    public function setAppkey($appkey): self
    {
        $this->appkey = $appkey;

        return $this;
    }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return ($this->token ?? ($this->appkey ?? ''));
    }
}
