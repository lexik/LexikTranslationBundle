<?php

namespace Lexik\Bundle\TranslationBundle\Propel;

use Lexik\Bundle\TranslationBundle\Propel\om\BaseFile;
use Lexik\Bundle\TranslationBundle\Manager\FileInterface;

class File extends BaseFile implements FileInterface
{
    /**
     * Set file name
     *
     * @param string $name
     */
    public function setName($name)
    {
        list($domain, $locale, $extention) = explode('.', $name);

        $this
            ->setDomain($domain)
            ->setLocale($locale)
            ->setExtention($extention)
        ;

        return $this;
    }

    /**
     * Get file name
     *
     * @return string
     */
    public function getName()
    {
        return sprintf('%s.%s.%s', $this->getDomain(), $this->getLocale(), $this->getExtention());
    }
}
