<?php

namespace A5sys\EasyAdminPopupBundle\Twig;

/**
 *
 */
class EasyAdminPopupTwigExtension extends \Twig_Extension
{
    protected $adminLayout = null;

    /**
     *
     * @param type $adminLayout
     */
    public function __construct($adminLayout)
    {
        $this->adminLayout = $adminLayout;
    }
    /**
     *
     * @return type
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('easyadminpopup_layout', array($this, 'getLayout')),
        );
    }

    /**
     *
     * @return type
     */
    public function getLayout()
    {
        return $this->adminLayout;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return 'easyadminpopup_extension';
    }
}
