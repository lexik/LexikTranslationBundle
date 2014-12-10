<?php

namespace Lexik\Bundle\TranslationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler\RegisterMappingPass;
use Lexik\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorPass;

/**
 * Bundle main class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class LexikTranslationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TranslatorPass());
        $container->addCompilerPass(new RegisterMappingPass());
    }
}
