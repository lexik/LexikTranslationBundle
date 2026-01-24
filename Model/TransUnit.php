<?php

namespace Lexik\Bundle\TranslationBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Lexik\Bundle\TranslationBundle\Manager\TranslationInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Lexik\Bundle\TranslationBundle\Entity\Translation;
use Lexik\Bundle\TranslationBundle\Document\Translation as DocumentTranslation;
use DateTime;

/**
 * This class represent a trans unit which contain translations for a given domain and key.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
#[ORM\MappedSuperclass]
abstract class TransUnit
{
    /**
     * @var int
     */
    protected $id;

    #[ORM\Column(name: 'key_name', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    protected string $key;

    #[ORM\Column(name: 'domain', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    protected string $domain;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $translations;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected DateTime|string $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected DateTime|string $updatedAt;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->domain = 'messages';
        $this->translations = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set key name
     *
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * Get key name
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set domain
     *
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Add translations
     *
     * @param Translation $translations
     */
    public function addTranslation(DocumentTranslation|Translation $translation): void
    {
        $this->translations[] = $translation;
    }

    /**
     * Remove translations
     *
     * @param Translation $translations
     */
    public function removeTranslation(DocumentTranslation|Translation $translation): void
    {
        $this->translations->removeElement($translation);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations(): array|Collection
    {
        return $this->translations;
    }

    /**
     * Return true if this object has a translation for the given locale.
     *
     * @param string $locale
     * @return boolean
     */
    public function hasTranslation(string $locale): bool
    {
        return null !== $this->getTranslation(locale: $locale);
    }

    /**
     * Return the content of translation for the given locale.
     *
     * @param string $locale
     */
    public function getTranslation(string $locale): ?TranslationInterface
    {
        foreach ($this->getTranslations() as $translation) {
            if ($translation->getLocale() == $locale) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * Set translations collection
     */
    public function setTranslations(Collection $collection): void
    {
        $this->translations = new ArrayCollection();

        foreach ($collection as $translation) {
            $this->addTranslation(translation: $translation);
        }
    }

    /**
     * Return transaltions with  not blank content.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function filterNotBlankTranslations(): Collection
    {
        return $this->getTranslations()->filter(function (TranslationInterface $translation) {
            $content = $translation->getContent();
            return !empty($content);
        });
    }

    /**
     * Get createdAt
     *
     * @return datetime $createdAt
     */
    public function getCreatedAt(): DateTime|string
    {
        return $this->createdAt;
    }

    /**
     * Get updatedAt
     *
     * @return datetime $updatedAt
     */
    public function getUpdatedAt(): DateTime|string
    {
        return $this->updatedAt;
    }
}
