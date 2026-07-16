<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Apelido = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $graduaçao = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isPublic = null;

    #[ORM\Column(length: 30)]
    private ?string $accountType = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'teachers')]
    private Collection $academies;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'academies')]
    #[ORM\JoinTable(name: 'user_teachers')]
    private Collection $teachers;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(mappedBy: 'organizer', targetEntity: Event::class, cascade: ['remove'])]
    private Collection $events;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'participants')]
    private Collection $eventsParticipated;

    public function __construct()
    {
        $this->academies = new ArrayCollection();
        $this->teachers = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->eventsParticipated = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getApelido(): ?string
    {
        return $this->Apelido;
    }

    public function setApelido(?string $Apelido): static
    {
        $this->Apelido = $Apelido;
        return $this;
    }

    public function getGraduaçao(): ?string
    {
        return $this->graduaçao;
    }

    public function setGraduaçao(?string $graduaçao): static
    {
        $this->graduaçao = $graduaçao;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getAccountType(): ?string
    {
        return $this->accountType;
    }

    public function setAccountType(string $accountType): static
    {
        $this->accountType = $accountType;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAcademies(): Collection
    {
        return $this->academies;
    }

    public function addAcademy(User $academy): static
    {
        if (!$this->academies->contains($academy)) {
            $this->academies->add($academy);
            $academy->addTeacher($this);
        }
        return $this;
    }

    public function removeAcademy(User $academy): static
    {
        if ($this->academies->removeElement($academy)) {
            $academy->removeTeacher($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getTeachers(): Collection
    {
        return $this->teachers;
    }

    public function addTeacher(User $teacher): static
    {
        if (!$this->teachers->contains($teacher)) {
            $this->teachers->add($teacher);
        }
        return $this;
    }

    public function removeTeacher(User $teacher): static
    {
        $this->teachers->removeElement($teacher);
        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEventsParticipated(): Collection
    {
        return $this->eventsParticipated;
    }

    public function addEventsParticipated(Event $eventsParticipated): static
    {
        if (!$this->eventsParticipated->contains($eventsParticipated)) {
            $this->eventsParticipated->add($eventsParticipated);
            $eventsParticipated->addParticipant($this);
        }

        return $this;
    }

    public function removeEventsParticipated(Event $eventsParticipated): static
    {
        if ($this->eventsParticipated->removeElement($eventsParticipated)) {
            $eventsParticipated->removeParticipant($this);
        }

        return $this;
    }
}