<?php

namespace Lexik\Bundle\TranslationBundle\Manager;

/**
 * File interface.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface FileInterface
{
    public function getId();
    public function setDomain(string $domain): void;
    public function getDomain(): string;
    public function setLocale(string $locale): void;
    public function getLocale(): string;
    public function setExtention(string $extention): void;
    public function getExtention(): string;
    public function setPath(string $path): void;
    public function getPath(): string;
    public function setName(string $name): void;
    public function getName(): string;
    public function setHash(string $hash): void;
    public function getHash(): string;
}
