<?php

namespace App\Entity;

use App\Repository\FolderRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FolderRepository::class)
 */
class Folder
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $names;

    /**
     * @ORM\Column(type="smallint")
     */
    private $sexe;

    /**
     * @ORM\Column(type="integer")
     */
    private $age;

    /**
     * @ORM\Column(type="text")
     */
    private $background;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $side;

    /**
     * @ORM\Column(type="text")
     */
    private $job;

    /**
     * @ORM\Column(type="integer")
     */
    private $hrpAge;

    /**
     * @ORM\Column(type="integer")
     */
    private $hrpExperience;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $hrpProvenance;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="folders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="smallint")
     */
    private $state = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    private $inserted;

    public function __construct()
    {
        $this->inserted = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNames(): ?string
    {
        return $this->names;
    }

    public function setNames(string $names): self
    {
        $this->names = $names;

        return $this;
    }

    public function getSexe(): ?int
    {
        return $this->sexe;
    }

    public function setSexe(int $sexe): self
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function setBackground(string $background): self
    {
        $this->background = $background;

        return $this;
    }

    public function getSide(): ?string
    {
        return $this->side;
    }

    public function setSide(string $side): self
    {
        $this->side = $side;

        return $this;
    }

    public function getJob(): ?string
    {
        return $this->job;
    }

    public function setJob(string $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function getHrpAge(): ?int
    {
        return $this->hrpAge;
    }

    public function setHrpAge(int $hrpAge): self
    {
        $this->hrpAge = $hrpAge;

        return $this;
    }

    public function getHrpExperience(): ?int
    {
        return $this->hrpExperience;
    }

    public function setHrpExperience(int $hrpExperience): self
    {
        $this->hrpExperience = $hrpExperience;

        return $this;
    }

    public function getHrpProvenance(): ?string
    {
        return $this->hrpProvenance;
    }

    public function setHrpProvenance(?string $hrpProvenance): self
    {
        $this->hrpProvenance = $hrpProvenance;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getInserted(): ?\DateTimeInterface
    {
        return $this->inserted;
    }

    public function setInserted(\DateTimeInterface $inserted): self
    {
        $this->inserted = $inserted;

        return $this;
    }
}
