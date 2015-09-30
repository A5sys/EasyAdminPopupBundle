<?php

namespace A5sys\EasyAdminPopupBundle\Listener;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 *
 * @author Thomas BEAUJEAN
 *
 * ref: easy_admin_popup.listener.crud_flashbag
 */
class CrudFlashbagListener
{
    protected $session = null;
    protected $translator = null;

    /**
     * Constructor
     *
     * @param Session    $session
     * @param Translator $translator
     */
    public function __construct(Session $session, Translator $translator)
    {
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * onPostPersist
     *
     * @param GenericEvent $event
     */
    public function onPostPersist(GenericEvent $event)
    {
        $this->addFlashMessage('flash.entity.persist', $event);
    }

    /**
     * onPostUpdate
     *
     * @param GenericEvent $event
     */
    public function onPostUpdate(GenericEvent $event)
    {
        $this->addFlashMessage('flash.entity.update', $event);
    }

    /**
     * onPostRemove
     *
     * @param GenericEvent $event
     */
    public function onPostRemove(GenericEvent $event)
    {
        $this->addFlashMessage('flash.entity.remove', $event);
    }

    /**
     * Add a flash message
     *
     * @param type         $message
     * @param GenericEvent $event
     */
    protected function addFlashMessage($message, GenericEvent $event)
    {
        $finalMessage = $this->getMessage($message, $event);

        $this->session->getFlashBag()->add('notice', $finalMessage);
    }

    /**
     *
     * @param type         $message
     * @param GenericEvent $event
     */
    protected function getMessage($message, GenericEvent $event)
    {
        $transDomain = 'EasyAdminBundle';
        $entityClass = $this->getEntityClass($event);
        $entityClassLabel = $this->translator->trans(/** @Ignore */$entityClass.'.label', [], $transDomain);

        $messageParameters = array('%entity%' => $entityClassLabel);
        $finalMessage = $this->translator->trans(/** @Ignore */$message, $messageParameters, $transDomain);

        return $finalMessage;
    }

    /**
     *
     * @return string
     */
    protected function getEntityClass(GenericEvent $event)
    {
        $entity = $event->getArgument('entity');
        $entityNamespace = get_class($entity);

        $entityClasses = explode('\\', $entityNamespace);

        //get last string of the classname
        $entityClass = $entityClasses[count($entityClasses) - 1];

        return $entityClass;
    }
}
