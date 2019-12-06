<?php

namespace A5sys\EasyAdminPopupBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityNotFoundException;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Form\Type\DateType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use tbn\JsonAnnotationBundle\Configuration\Json;

/**
 *
 * @author Thomas BEAUJEAN
 *
 */
class AdminController extends BaseAdminController
{
    /**
     * @Route("/json/", name="easyadmin_json")
     *
     * @param Request $request
     *
     * @Json
     *
     * @return array
     */
    public function indexJsonAction(Request $request)
    {
        $adminResponse = $this->indexAction($request);

        $html = null;
        $redirect = null;

        if ($adminResponse instanceof RedirectResponse) {
            $redirect = $adminResponse->getTargetUrl();
        } else {
            $html = $adminResponse->getContent();
        }

        return array('html' => $html, 'redirect' => $redirect);
    }

    /**
     *
     * @param type  $data
     * @param array $options
     * @return type
     */
    public function createFormBuilder($data = null, array $options = array())
    {
        $formBuilder = parent::createFormBuilder($data, $options);

        $url = $this->getFormUrl($options);

        if ($url !== null) {
            $formBuilder->setAction($url);
        }

        return $formBuilder;
    }

    /**
     * Get an url by the options of the form
     *
     * @param arrau $options
     *
     * @return string The url
     */
    protected function getFormUrl($options)
    {
        $url = null;

        //there might not be any options
        //case of the delete form
        if (isset($options['attr']['id'])) {
            //get the form id
            $formId = $options['attr']['id'];

            //the form id is composed of $view-form
            list($view)  = explode('-', $formId);
            $id = $this->request->query->get('id');
            //custom URL for ajax
            $urlParameters = array_merge(
                $this->getUrlParameters($view),
                ['id' => $id]
            );

            $url = $this->generateUrl($this->getJsonRouteName(), $urlParameters);
        }

        return $url;
    }

    /**
     * Utility method which initializes the configuration of the entity on which
     * the user is performing the action.
     *
     * @param Request $request
     *
     * @return null
     */
    protected function initialize(Request $request)
    {
        if (!$request->query->has('sortField')) {
            $noSort = true;
        } else {
            $noSort = false;
        }

        parent::initialize($request);

        //the admin controller parent uses the ajax property to toggle a property
        //but we uses the ajax to post form
        //so we simulate the fact that all post are standart post
        if ($this->request) {
            $this->request->headers->remove('X-Requested-With');
        }

        //we do not want a default sort
        if ($noSort) {
            $request->query->set('sortField', null);
        }
    }

    /**
     * Creates the form used to delete an entity. It must be a form because
     * the deletion of the entity are always performed with the 'DELETE' HTTP method,
     * which requires a form to work in the current browsers.
     *
     * @param string $entityName
     * @param int    $entityId
     *
     * @return Form
     */
    protected function createDeleteForm($entityName, $entityId)
    {
        $urlParameters = $this->getUrlParameters('delete');
        $urlParameters['id'] = $entityId;
        $url = $this->generateUrl($this->getJsonRouteName(), $urlParameters);

        return $this->createFormBuilder()
            ->setAction($url)
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * The method that is executed when the user performs a 'delete' action to
     * remove any entity.
     *
     * @return RedirectResponse
     */
    protected function deleteAction()
    {
        $return = null;

        //simulate configuration for the twig extension
        $easyadmin = $this->request->attributes->get('easyadmin');
        $easyadmin['entity']['delete']['fields'] = [];
        $this->request->attributes->set('easyadmin', $easyadmin);

        $id = $this->request->query->get('id');
        if (!$entity = $this->em->getRepository($this->entity['class'])->find($id)) {
            throw new EntityNotFoundException(array('action' => 'delete', 'entity' => $this->entity, 'entity_id' => $id));
        }

        $fields = [];

        $form = $this->createDeleteForm($this->entity['name'], $id);
        $form->handleRequest($this->request);

        if ('DELETE' === $this->request->getMethod()) {
            $this->dispatch(EasyAdminEvents::PRE_DELETE);

            if ($form->isValid()) {
                $this->dispatch(EasyAdminEvents::PRE_REMOVE, array('entity' => $entity));

                if (method_exists($this, $customMethodName = 'preRemove'.$this->entity['name'].'Entity')) {
                    $this->{$customMethodName}($entity);
                } else {
                    $this->preRemoveEntity($entity);
                }

                $this->em->remove($entity);
                $this->em->flush();

                $this->dispatch(EasyAdminEvents::POST_REMOVE, array('entity' => $entity));

                $urlParameters = $this->getUrlParameters('list');
                $return = $this->redirect($this->generateUrl($this->getAdminRouteName(), $urlParameters));
            } else {
                throw new \LogicException('The delete form is not valid');
            }

            $this->dispatch(EasyAdminEvents::POST_DELETE);
        } else {
            $return = $this->render("@EasyAdminPopup/default/delete.html.twig", array(
                'form'          => $form->createView(),
                'entity_fields' => $fields,
                'entity'        => $entity,
            ));
        }

        return $return;
    }

    /**
     * The method that is executed when the user performs a 'list' action on an entity.
     *
     * @return Response
     */
    protected function listAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_LIST);

        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'));

        //add url parameter as hidden input in the search form
        $urlParameters = $this->getUrlParameters('search');
        $this->request->request->set('extraParameters', $urlParameters);

        $this->dispatch(EasyAdminEvents::POST_LIST, array('paginator' => $paginator));

        if (method_exists($this, $customMethodName = 'create'.$this->entity['name'].'SearchForm')) {
            $searchForm = $this->{$customMethodName}();
        } else {
            $searchForm =  $this->createSearchForm();
        }

        return $this->render($this->entity['templates']['list'], array(
            'paginator' => $paginator,
            'fields'    => $fields,
            'searchForm' => $searchForm->createView(),
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }


    /**
     * Performs a database query to get all the records related to the given
     * entity. It supports pagination and field sorting.
     *
     * @param string      $entityClass
     * @param int         $page
     * @param int         $maxPerPage
     * @param string|null $sortField
     * @param string|null $sortDirection
     *
     * @return Pagerfanta The paginated query results
     */
    protected function findAll($entityClass, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null)
    {
        if (method_exists($this, $customMethodName = 'create'.$this->entity['name'].'QueryBuilder')) {
            $query = $this->{$customMethodName}($entityClass);
        } else {
            $query = $this->em->createQueryBuilder()->select('entity')->from($entityClass, 'entity');
        }

        if (method_exists($this, $customMethodName = 'order'.ucfirst($this->entity['name']).'By')) {
            $this->{$customMethodName}($query, $sortField, $sortDirection);
        } else {
            $this->orderBy($query, $sortField, $sortDirection);
        }

        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     *
     * @param QueryBuilder $query
     * @param type         $sortField
     * @param string       $sortDirection
     */
    protected function orderBy(QueryBuilder $query, $sortField = null, $sortDirection = null)
    {
        if (!empty($sortField)) {
            if (empty($sortDirection) || !in_array(strtoupper($sortDirection), array('ASC', 'DESC'))) {
                $sortDirection = 'DESC';
            }

            $isVirtual = $querySort = $this->entity['list']['fields'][$sortField]['virtual'];
            $fieldType = $querySort = $this->entity['list']['fields'][$sortField]['fieldType'];

            if ($fieldType === 'entity' || $isVirtual) {
                if (!isset($this->entity['list']['fields'][$sortField]['querySort'])) {
                    throw new \LogicException('The property '.$sortField.' requires a querySort');
                }
                $querySort = $this->entity['list']['fields'][$sortField]['querySort'];
            } else {
                $querySort = 'entity.'.$sortField;
            }

            $query->orderBy($querySort, $sortDirection);
        }
    }

    /**
     * Performs a database query based on the search query provided by the user.
     * It supports pagination and field sorting.
     *
     * @param string $entityClass
     * @param string $searchQuery
     * @param array  $searchableFields
     * @param int    $page
     * @param int    $maxPerPage
     *
     * @return Pagerfanta The paginated query results
     */
    protected function findBy($entityClass, $searchQuery, array $searchableFields, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null)
    {
        if (method_exists($this, $customMethodName = 'create'.$this->entity['name'].'QueryBuilder')) {
            $query = $this->{$customMethodName}($entityClass);
        } else {
            $query = $this->em->createQueryBuilder()->select('entity')->from($entityClass, 'entity');
        }

        foreach ($searchQuery as $searchname => $searchValue) {
            //0 is a false positiv with the empty method
            if ($searchValue !== null && $searchValue !== '') {
                if (!isset($searchableFields[$searchname])) {
                    throw new \LogicException('The search field '.$searchname.' is not in the list of search fields');
                }

                $metadata = $searchableFields[$searchname];
                if (method_exists($this, $customMethodName = 'add'.ucfirst($this->entity['name']).'FilterToFindBy')) {
                    $this->{$customMethodName}($query, $metadata, $searchname, $searchValue);
                } else {
                    $this->addFilterToFindBy($query, $metadata, $searchname, $searchValue);
                }
            }
        }

        if (method_exists($this, $customMethodName = 'order'.ucfirst($this->entity['name']).'By')) {
            $this->{$customMethodName}($query, $sortField, $sortDirection);
        } else {
            $this->orderBy($query, $sortField, $sortDirection);
        }

        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * Add a filter for the search action
     *
     * @param QueryBuilder $qb
     * @param array        $metadata
     * @param string       $name
     * @param array        $search
     */
    protected function addFilterToFindBy(QueryBuilder $queryBuilder, array $metadata, $name, $search)
    {
        if ($metadata['dataType'] === 'association') {
            //the word are reserved, so we add a prefix
            if (in_array($name, ['member', 'group'])) {
                $associationName = 'entity_'.$name;
            } else {
                $associationName = $name;
            }

            $id = $search->getId();
            $queryBuilder->andWhere($associationName.'.id = :'.$name);
            $queryBuilder->setParameter($name, $id);
        } elseif (in_array($metadata['type'], array('text', 'string'))) {
            $queryBuilder->andWhere('entity.'.$name.' LIKE :'.$name);
            $queryBuilder->setParameter($name, '%'.$search.'%');
        } else {
            $queryBuilder->andWhere('entity.'.$name.' IN (:'.$name.')');
            $queryBuilder->setParameter($name, $search);
        }
    }

    /**
     * The method that is executed when the user performs a query on an entity.
     *
     * @return Response
     */
    protected function searchAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_SEARCH);

        $searchableFields = $this->entity['search']['fields'];

        if (method_exists($this, $customMethodName = 'create'.$this->entity['name'].'SearchForm')) {
            $searchForm = $this->{$customMethodName}();
        } else {
            $searchForm =  $this->createSearchForm();
        }

        //add url parameter as hidden input in the search form
        $urlParameters = $this->getUrlParameters('search');
        $this->request->request->set('extraParameters', $urlParameters);

        $searchForm->handleRequest($this->request);
        $searchData = $searchForm->getData();

        //there might be no data
        if ($searchData === null) {
            $searchData = [];
        }

        if (method_exists($this, $customMethodName = 'find'.ucfirst($this->entity['name']).'By')) {
            $paginator = $this->{$customMethodName}($this->entity['class'], $searchData, $searchableFields, $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'));
        } else {
            $paginator = $this->findBy($this->entity['class'], $searchData, $searchableFields, $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'));

        }

        $fields = $this->entity['list']['fields'];

        $this->dispatch(EasyAdminEvents::POST_SEARCH, array(
            'fields' => $fields,
            'paginator' => $paginator,
        ));

        return $this->render($this->entity['templates']['list'], array(
            'paginator' => $paginator,
            'fields'    => $fields,
            'searchForm' => $searchForm->createView(),
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }

    /**
     * Creates the form used to create or edit an entity.
     *
     * @param object $entity
     * @param array  $entityProperties
     * @param string $view             The name of the view where this form is used ('new' or 'edit')
     *
     * @return Form
     */
    protected function createSearchForm()
    {
        $entityProperties = $this->entity['search']['fields'];

        $formBuilder = $this->get('form.factory')->createNamedBuilder('search', FormType::class, array());

        foreach ($entityProperties as $name => $metadata) {
            $this->addSearchFormField($name, $metadata, $formBuilder);
        }

        $url = $this->getSearchFormUrl();

        $formBuilder->setMethod('GET');
        $formBuilder->setAction($url);

        return $formBuilder->getForm();
    }

    /**
     * Add a field for a search form
     *
     * @param type        $name
     * @param array       $metadata
     * @param FormBuilder $formBuilder
     */
    protected function addSearchFormField($name, array $metadata, FormBuilder $formBuilder)
    {
        $addField = true;

        if ('association' === $metadata['fieldType'] && in_array($metadata['associationType'], array(ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY))) {
            $addField = false;
        }

        if ($addField) {
            if (isset($metadata['targetEntity']) && $metadata['targetEntity'] !== null) {
                $fieldType = EntityType::class;
            } elseif ('date' === $metadata['type']) {
                $fieldType = DateType::class;
            } else {
                $fieldType = $this->getFormTypeFqcn($metadata['fieldType']);
            }

            $formFieldOptions = $this->getSearchFormFieldOptions($name, $metadata, $fieldType);

            $formBuilder->add($name, $fieldType, $formFieldOptions);
        }
    }

    /**
     * It returns the FQCN of the given short type.
     * Example: 'text' -> 'Symfony\Component\Form\Extension\Core\Type\TextType'
     *
     * @param string $shortType
     *
     * @return string
     */
    protected function getFormTypeFqcn($shortType)
    {
        $typeNames = array(
            'base', 'birthday', 'button', 'checkbox', 'choice', 'collection',
            'country', 'currency', 'datetime', 'date', 'email', 'file', 'form',
            'hidden', 'integer', 'language', 'money', 'number', 'password',
            'percent', 'radio', 'repeated', 'reset', 'search', 'submit',
            'textarea', 'text', 'time', 'timezone', 'url',
        );

        if (!in_array($shortType, $typeNames)) {
            return $shortType;
        }

        // take into account the irregular class name for 'datetime' type
        $typeClassName = 'datetime' === $shortType ? 'DateTime' : ucfirst($shortType);
        $typeFqcn = sprintf('Symfony\\Component\\Form\\Extension\\Core\\Type\\%sType', $typeClassName);

        return $typeFqcn;
    }

    /**
     * Get the url for a search form
     *
     * @return strin The url
     */
    protected function getSearchFormUrl()
    {
        $urlParameters = $this->getUrlParameters('search');

        if (method_exists($this, $customMethodName = 'generate'.ucfirst($this->entity['name']).'Url')) {
            $url = $this->{$customMethodName}($this->getAdminRouteName(), $urlParameters);
        } else {
            $url = $this->generateUrl($this->getAdminRouteName(), $urlParameters);
        }

        return $url;
    }

    /**
     * Get the url for a search form
     *
     * @return strin The url
     */
    protected function getUrlParameters($action)
    {
        $urlParameters = array(
            'action' => $action,
            'entity' => $this->entity['name'],
        );

        if (method_exists($this, $customMethodName = 'get'.ucfirst($this->entity['name']).'UrlParameters')) {
            $urlParameters = $this->{$customMethodName}($urlParameters);
        }

        return $urlParameters;
    }

    /**
     * Get field options for a search form field
     *
     * @param string $name      The field name
     * @param array  $metadata  The field metadata
     * @param string $fieldType The field type
     *
     * @return array The fieldOptions
     */
    protected function getSearchFormFieldOptions($name, $metadata, $fieldType)
    {
        $formFieldOptions = array();

        $translator = $this->get('translator');

        if (isset($metadata['targetEntity']) && $metadata['targetEntity'] !== null) {
            $formFieldOptions['class'] = $metadata['targetEntity'];
            $formFieldOptions['attr']['field_type'] = $fieldType;
        } else {
            $formFieldOptions['attr']['field_type'] = $fieldType;
        }

        $formFieldOptions['attr']['field_type'] = $fieldType;
        $formFieldOptions['attr']['field_help'] = $metadata['help'];
        $formFieldOptions['required'] = false;

        //translate field label
        $labelIndex = $this->entity['search']['fields'][$name]['label'];

        $label = $translator->trans(/** @Ignore */$labelIndex, [], 'EasyAdminBundle');
        $formFieldOptions['label'] = $label;

        return $formFieldOptions;
    }

    /**
     * Retrieves the list of form options before sending them to the form builder.
     * This allows adding dynamic logic to the default form options.
     *
     * @param object $entity
     * @param string $view
     *
     * @return array
     */
    protected function getEntityFormOptions($entity, $view)
    {
        $formOptions = $this->entity[$view]['form_options'];
        $formOptions['entity'] = $this->entity['name'];
        $formOptions['view'] = $view;

        $urlParameters = $this->getUrlParameters($view);

        $entityId = $entity->getId();

        if (null !== $entityId) {
            $urlParameters['id'] = $entityId;
        }
        $url = $this->generateUrl($this->getJsonRouteName(), $urlParameters);
        $formOptions['action'] = $url;

        return $formOptions;
    }

    /**
     *
     * @param type  $entity
     * @param array $entityProperties
     * @return Form
     */
    protected function createCustomForm($form, $entity, array $entityProperties, $view, array $options = [])
    {
        $formCssClass = array_reduce($this->config['design']['form_theme'], function ($previousClass, $formTheme) {
            return sprintf('theme-%s %s', strtolower(str_replace('.html.twig', '', basename($formTheme))), $previousClass);
        });

        $urlParameters = $this->getUrlParameters($view);

        if ($view === 'edit') {
            $urlParameters['id'] = $entity->getId();
        }

        $url = $this->generateUrl($this->getJsonRouteName(), $urlParameters);

        $formOptions = array_merge($options, array(
            'data_class' => $this->entity['class'],
            'attr' => array('class' => $formCssClass, 'id' => $view.'-form'),
            'method' => 'POST',
            'action' => $url,
        ));

        return $this->createForm($form, $entity, $formOptions);
    }

    /**
     * Get the name of the json route to use
     * @return string
     */
    protected function getJsonRouteName()
    {
        return 'easyadmin_json';
    }

    /**
     * Get the name of the route to use
     * @return string
     */
    protected function getAdminRouteName()
    {
        return 'easyadmin';
    }

    /**
     * The method that is executed when the user performs a 'edit' action on an entity.
     *
     * @return RedirectResponse|Response
     */
    protected function editAction()
    {
        $adminResponse = parent::editAction();

        if ($adminResponse instanceof RedirectResponse) {
            $url = $this->generateUrl($this->getAdminRouteName(), $this->getUrlParameters('list'));
            $adminResponse->setTargetUrl($url);
        }

        return $adminResponse;
    }

    /**
     * The method that is executed when the user performs a 'new' action on an entity.
     *
     * @return RedirectResponse|Response
     */
    protected function newAction()
    {
        $adminResponse = parent::newAction();

        if ($adminResponse instanceof RedirectResponse) {
            $url = $this->generateUrl($this->getAdminRouteName(), $this->getUrlParameters('list'));
            $adminResponse->setTargetUrl($url);
        }

        return $adminResponse;
    }
}
