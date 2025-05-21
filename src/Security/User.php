<?php

namespace Experteam\ApiBaseBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $username = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    private ?string $token = null;
    private ?string $appkey = null;
    private array $roles = [];
    private ?string $model_type = null;
    private ?int $model_id = null;
    private ?string $auth_type = null;
    private ?int $language_id = null;
    private ?array $session = null;
    private ?bool $is_active = null;
    private ?string $email = null;
    private ?array $role = null;

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

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
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

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(string $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
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
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getSession(): ?array
    {
        return $this->session;
    }

    public function setSession(?array $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getModelType(): ?string
    {
        return $this->model_type;
    }

    public function setModelType(?string $model_type): static
    {
        $this->model_type = $model_type;

        return $this;
    }

    public function getModelId(): ?int
    {
        return $this->model_id;
    }

    public function setModelId(?int $model_id): static
    {
        $this->model_id = $model_id;

        return $this;
    }

    public function getAuthType(): ?string
    {
        return $this->auth_type;
    }

    public function setAuthType(?string $auth_type): static
    {
        $this->auth_type = $auth_type;

        return $this;
    }

    public function getLanguageId(): ?int
    {
        return $this->language_id;
    }

    public function setLanguageId(?int $language_id): static
    {
        $this->language_id = $language_id;

        return $this;
    }

    public function getAppkey(): ?string
    {
        return $this->appkey;
    }

    public function setAppkey(?string $appkey): static
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

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(?bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRole(): ?array
    {
        return $this->role;
    }

    public function setRole(?array $role): static
    {
        $this->role = $role;

        return $this;
    }
}
