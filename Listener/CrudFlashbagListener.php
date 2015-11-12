<?php

namespace A5sys\EasyAdminPopupBundle\Listener;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Translation\TranslatorInterface;
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
    protected $customizedFlash = null;

    /**
     * Constructor
     *
     * @param Session    $session
     * @param Translator $translator
     * @param boolean    $customizedFlash
     */
    public function __construct(Session $session, TranslatorInterface $translator, $customizedFlash)
    {
        $this->session = $session;
        $this->translator = $translator;
        $this->customizedFlash = $customizedFlash;
    }

    /**
     * onPostPersist
     *
     * @param GenericEvent $event
     */
    public function onPostPersist(GenericEvent $event)
    {
        $this->addFlashMessage('persist', $event);
    }

    /**
     * onPostUpdate
     *
     * @param GenericEvent $event
     */
    public function onPostUpdate(GenericEvent $event)
    {
        $this->addFlashMessage('update', $event);
    }

    /**
     * onPostRemove
     *
     * @param GenericEvent $event
     */
    public function onPostRemove(GenericEvent $event)
    {
        $this->addFlashMessage('remove', $event);
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
        if ($this->customizedFlash) {
            $entityClass = $this->getEntityClass($event);
            $transDomain = 'messages';
            $message = $this->translator->trans(/** @Ignore */'flash.'.$entityClass.'.'.$message, [], $transDomain);
        } else {
            $entityTransDomain = 'messages';
            $entityClass = $this->getEntityClass($event);
            $entityClassLabel = $this->translator->trans(/** @Ignore */$entityClass.'.label', [], $entityTransDomain);

            $transDomain = 'EasyAdminBundle';
            $messageParameters = array('%entity%' => $entityClassLabel);

            $message = $this->translator->trans(/** @Ignore */'flash.entity.'.$message, $messageParameters, $transDomain);
        }

        return $message;
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
        return $entityClasses[count($entityClasses) - 1];
    }
}
