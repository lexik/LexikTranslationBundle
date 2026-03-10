<?php

namespace Lexik\Bundle\TranslationBundle;

use Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler\RegisterMappingPass;
use Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle main class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class LexikTranslationBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TranslatorPass());
        $container->addCompilerPass(new RegisterMappingPass());
    }
}
