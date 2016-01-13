<?php

namespace A5sys\EasyAdminPopupBundle\Form\Type\Configurator;

use JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
use Symfony\Component\Form\FormConfigInterface;

/**
 *
 * @author Thomas Beaujean
 */
class DateTypeConfigurator implements TypeConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure($name, array $options, array $metadata, FormConfigInterface $parentConfig)
    {
        return array(
            'widget' => 'single_text',
            'datepicker' => true,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, array $options, array $metadata)
    {
        return 'date' === $type;
    }
}
