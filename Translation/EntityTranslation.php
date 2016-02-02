<?php

namespace A5sys\EasyAdminPopupBundle\Translation;

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;

/**
 * The extractor for the automatic translations
 *
 * ref: easy_admin_popup.translation.entity_translation
 */
class EntityTranslation
{
    protected $backendConfiguration;
    protected $configurator;

    /**
     * Constructor
     *
     * @param array        $backendConfiguration
     * @param Configurator $configurator
     */
    public function __construct(array $backendConfiguration, Configurator $configurator)
    {
        $this->backendConfiguration = $backendConfiguration;
        $this->configurator = $configurator;
    }

    /**
     * Get the translations
     *
     * @return string[] The translations
     */
    public function getTranslations()
    {
        $labels = array();

        $entities = $this->getEntities();

        foreach ($entities as $entity) {
            $entityConfiguration = $this->configurator->getEntityConfiguration($entity);
            $entityLabels = $this->getAllFieldsLabels($entityConfiguration);
            $labels = array_merge($labels, $entityLabels);
        }

        //avoid doublons
        return array_unique($labels);
    }

    /**
     * Get the list of entities
     *
     * @return string[] The list of entities
     */
    protected function getEntities()
    {
        return array_keys($this->backendConfiguration['entities']);
    }

    /**
     * Get all fields label of all views
     *
     * @param string[] $entityConfiguration
     *
     */
    protected function getAllFieldsLabels($entityConfiguration)
    {
        $labels = array();

        $views = array('edit', 'list', 'new', 'search', 'show');

        foreach ($views as $view) {
            $viewLabels = $this->getViewFieldLabels($entityConfiguration, $view);
            $labels = array_merge($labels, $viewLabels);
        }

        return array_unique($labels);
    }

    /**
     *
     * @param string[] $entityConfiguration
     * @param string $view
     */
    protected function getViewFieldLabels($entityConfiguration, $view)
    {
        $labels = array();

        //the view might have not been enabled
        if (isset($entityConfiguration[$view])) {
            $viewConfiguration = $entityConfiguration[$view];
            $fieldsConfiguration = $viewConfiguration['fields'];

            foreach ($fieldsConfiguration as $configuration) {
                $label = $configuration['label'];

                if (!empty($label)) {
                    $labels[] = $label;
                }
            }
        }

        return $labels;
    }
}
