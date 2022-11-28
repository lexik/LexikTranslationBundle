<?php

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Lexik\Bundle\TranslationBundle\LexikTranslationBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

return [
    new FrameworkBundle(),
    new DoctrineBundle(),
    new LexikTranslationBundle(),
];
