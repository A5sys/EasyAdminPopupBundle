<?php

namespace A5sys\EasyAdminPopupBundle\Configuration;

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator as BaseConfigurator;

/**
 * @author Thomas BEAUJEAN
 */
class Configurator extends BaseConfigurator
{
    /**
     * Returns the list of entity fields on which the search query is performed.
     *
     * @return array The list of fields to use for the search
     */
    protected function getFieldsForSearchAction(array $entityConfiguration)
    {
        if (0 === count($entityConfiguration['search']['fields'])) {
            $excludedFieldNames = array();
            $excludedFieldTypes = array('binary', 'boolean', 'blob', 'datetime', 'datetimetz', 'time', 'object');
            $entityConfiguration['search']['fields'] = $this->filterFieldsByNameAndType($this->defaultEntityFields, $excludedFieldNames, $excludedFieldTypes);
        }

        return $this->normalizeFieldsConfiguration('search', $entityConfiguration);
    }
}
