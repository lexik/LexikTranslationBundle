<?php

namespace Lexik\Bundle\TranslationBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Base File class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[ORM\MappedSuperclass]
abstract class File
{
    protected $id;

    #[ORM\Column(name: 'domain', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    protected string $domain;

    #[ORM\Column(name: 'locale', type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    protected string $locale;

    #[ORM\Column(name: 'extention', type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    protected string $extention;

    #[ORM\Column(name: 'path', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    protected string $path;

    #[ORM\Column(name: 'hash', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    protected string $hash;

    protected $createdAt;

    protected $updatedAt;

    /**
     * @var Collection
     */
    protected Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setExtention(string $extention): void
    {
        $this->extention = $extention;
    }

    public function getExtention(): string
    {
        return $this->extention;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setName(string $name): void
    {
        [$domain, $locale, $extention] = explode('.', $name);

        $this->domain = $domain;
        $this->locale = $locale;
        $this->extention = $extention;
    }

    public function getName(): string
    {
        return sprintf('%s.%s.%s', $this->domain, $this->locale, $this->extention);
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function addTranslation(Translation $translation): void
    {
        $translation->setFile($this);

        $this->translations[] = $translation;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }
}
