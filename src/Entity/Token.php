<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expires_in = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getExpiresIn(): ?\DateTimeImmutable
    {
        return $this->expires_in;
    }

    public function setExpiresIn(\DateTimeImmutable $expires_in): static
    {
        $this->expires_in = $expires_in;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expires_in < new \DateTimeImmutable();
    }
}
