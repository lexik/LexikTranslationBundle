<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Lexik\Bundle\TranslationBundle\Entity\Translation;

/**
 * @ORM\Entity(repositoryClass="Lexik\Bundle\TranslationBundle\Repository\TransUnitRepository")
 * @ORM\Table(
 *     name="lexik_trans_unit",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="key_domain_idx", columns={"key_name", "domain"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(fields={"key", "domain"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnit
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, name="key_name")
     *
     * @Assert\NotBlank()
     */
    protected $key;

    /**
     * @ORM\Column(type="string", length=255, name="domain")
     *
     * @Assert\NotBlank()
     */
    protected $domain;

    /**
     * @ORM\OneToMany(targetEntity="Lexik\Bundle\TranslationBundle\Entity\Translation", mappedBy="transUnit", cascade={"all"})
     */
    protected $translations;

    /**
     * @ORM\Column(type="datetime", name="created_at", nullable="true")
     *
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", name="updated_at", nullable="true")
     *
     */
    protected $updatedAt;

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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set key name
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get key name
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set domain
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
      $this->domain = $domain;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
      return $this->domain;
    }

    /**
     * Add translations
     *
     * @param Lexik\Bundle\TranslationBundle\Entity\Translation $translations
     */
    public function addTranslation(Translation $translation)
    {
        if (!($translation->getTransUnit() instanceof self)) {
            $translation->setTransUnit($this);
        }

        $this->translations[] = $translation;
    }

    /**
     * Get translations
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Return true if this object has a translation for the given locale.
     *
     * @param string $locale
     * @return boolean
     */
    public function hasTranslation($locale)
    {
        $i = 0;
        $end = count($this->getTranslations());
        $found = false;

        while ($i<$end && !$found) {
            $found = ($this->translations[$i]->getLocale() == $locale);
            $i++;
        }

        return $found;
    }

    /**
     * Set translations collection
     *
     * @param Collection $collection
     */
    public function setTranslations(Collection $collection)
    {
        $this->translations = $collection;
    }

    /**
     * Return transaltions with  not blank content.
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function filterNotBlankTranslations()
    {
        return $this->getTranslations()->filter(function ($translation) {
            $content = $translation->getContent();
            return !empty($content);
        });
    }

    /**
     * Get createdAt
     *
     * @return datetime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get updatedAt
     *
     * @return datetime $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}