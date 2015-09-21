<?php

namespace A5sys\EasyAdminPopupBundle\Translation;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\ExtractorInterface;
use JavierEguiluz\Bundle\EasyAdminBundle\Translation\EntityTranslation as EasyEntityTranslation;

/**
 * The extractor for the automatic translations
 *
 *  ref: easyadmin.translation.entity_translation
 */
class EntityExtractor implements ExtractorInterface
{
    protected $domain;
    protected $backendConfiguration;
    protected $easyEntityTranslation;

    /**
     * Constructor
     *
     * @param string[]              $backendConfiguration
     * @param string                $domain
     * @param EasyEntityTranslation $easyEntityTranslation
     */
    public function __construct(array $backendConfiguration, $domain, EasyEntityTranslation $easyEntityTranslation)
    {
        $this->backendConfiguration = $backendConfiguration;
        $this->easyEntityTranslation = $easyEntityTranslation;
        $this->domain = $domain;
    }

    /**
     * Extract translations
     *
     * @return MessageCatalogue
     */
    public function extract()
    {
        $catalogue = $this->getTranslations();

        $translations = $this->easyEntityTranslation->getTranslations();

        foreach ($translations as $translation) {
            $message = new Message($translation, $this->domain);
            $catalogue->add($message);
        }

        return $catalogue;
    }

    /**
     * Get the translations
     *
     * @return MessageCatalogue
     */
    protected function getTranslations()
    {
        $catalogue = new MessageCatalogue();
        $labels = array();

        $entities = $this->getEntities();

        foreach ($entities as $entity) {
            $labels[] = $entity.'.label';
            $labels[] = $entity.'.show.title';
            $labels[] = $entity.'.edit.title';
            $labels[] = $entity.'.list.title';
            $labels[] = $entity.'.new.title';
            $labels[] = $entity.'.delete.title';
        }

        //avoid doublons
        $uniqueLabels = array_unique($labels);

        foreach ($uniqueLabels as $uniqueLabel) {
            $message = new Message($uniqueLabel, $this->domain);
            $catalogue->add($message);
        }

        return $catalogue;
    }

    /**
     * Get the list of entities
     *
     * @return array:String The list of entities
     */
    protected function getEntities()
    {
        $entities = array_keys($this->backendConfiguration['entities']);

        return $entities;
    }
}
