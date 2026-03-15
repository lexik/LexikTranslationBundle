<?php

namespace Lexik\Bundle\TranslationBundle\Storage;

use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;
use Lexik\Bundle\TranslationBundle\Entity\TransUnitRepository;
use Lexik\Bundle\TranslationBundle\Entity\FileRepository;
use Lexik\Bundle\TranslationBundle\Document\FileRepository as DocumentFileRepository;
use Lexik\Bundle\TranslationBundle\Document\TransUnitRepository as DocumentTransUnitRepository;

/**
 * Common doctrine storage logic.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class AbstractDoctrineStorage implements StorageInterface
{
    public function __construct(
        protected ManagerRegistry $registry,
        protected string $managerName,
        protected array $classes,
    ) {
    }

    protected function getManager(): ObjectManager
    {
        return $this->registry->getManager($this->managerName);
    }

    /**
     * Returns the TransUnit repository.
     */
    protected function getTransUnitRepository(): TransUnitRepository|DocumentTransUnitRepository
    {
        /** @var TransUnitRepository|DocumentTransUnitRepository $repository */
        $repository = $this->getManager()->getRepository($this->classes['trans_unit']);

        return $repository;
    }

    /**
     * Returns the File repository.
     */
    protected function getFileRepository(): FileRepository|DocumentFileRepository
    {
        /** @var FileRepository|DocumentFileRepository $repository */
        $repository = $this->getManager()->getRepository($this->classes['file']);

        return $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity): void
    {
        $this->getManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($entity): void
    {
        $this->getManager()->remove($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function flush($entity = null): void
    {
        $this->getManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->getManager()->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getModelClass($name): mixed
    {
        if (!isset($this->classes[$name])) {
            throw new \RuntimeException(sprintf('No class defined for name "%s".', $name));
        }

        return $this->classes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesByLocalesAndDomains(array $locales, array $domains): mixed
    {
        return $this->getFileRepository()->findForLocalesAndDomains($locales, $domains);
    }

    /**
     * {@inheritdoc}
     */
    public function getFileByHash($hash): mixed
    {
        return $this->getFileRepository()->findOneBy(['hash' => $hash]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitDomains(): mixed
    {
        return $this->getTransUnitRepository()->getAllDomains();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitById($id): ?TransUnitInterface
    {
        return $this->getTransUnitRepository()->find($id);
    }

    /**
     * Returns a TransUnit by its key and domain.
     */
    public function getTransUnitByKeyAndDomain(string $key, string $domain): ?TransUnitInterface
    {
        $key = mb_substr($key, 0, 255, 'UTF-8');

        $fields = [
            'key'    => $key,
            'domain' => $domain,
        ];

        return $this->getTransUnitRepository()->findOneBy($fields);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitDomainsByLocale(): array
    {
        return $this->getTransUnitRepository()->getAllDomainsByLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitsByLocaleAndDomain($locale, $domain): array
    {
        return $this->getTransUnitRepository()->getAllByLocaleAndDomain(locale: $locale, domain: $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitList(?array $locales = null, ?int $rows = 20, ?int $page = 1, ?array $filters = null): array
    {
        return $this->getTransUnitRepository()->getTransUnitList(locales: $locales, rows: $rows, page: $page, filters: $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function countTransUnits(?array $locales = null, ?array $filters = null): int
    {
        $criteria = [
            'locales' => $locales,
            'filters' => $filters,
        ];
        if (null === $locales) {
            unset($criteria['locales']);
        }
        if (null === $filters) {
            unset($criteria['filters']);
        }
        return $this->getTransUnitRepository()->count(criteria: $criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationsFromFile($file, $onlyUpdated): mixed
    {
        return $this->getTransUnitRepository()->getTranslationsForFile($file, $onlyUpdated);
    }
}
