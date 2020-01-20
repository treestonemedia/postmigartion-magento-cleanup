<?php

namespace Treestone\Postmigration\Console;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Attributes extends Command {


    /**
     * @var \Magento\Eav\Model\AttributeSetRepository
     */
    private $attributeSetRepository;
    /**
     * @var \Magento\Eav\Api\AttributeSetRepositoryInterface
     */
    private $attributeSetRepositoryInterface;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    private $searchCriteriaInterface;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    private $filterGroup;
    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    private $filterGroupBuilder;
    /**
     * @var \Magento\Eav\Api\AttributeGroupRepositoryInterface
     */
    private $attributeGroupRepositoryInterface;
    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $attributeRepositoryInterface;

    private $initialDefaultAttributeSetId;
    private $initialDefaultAttributeSetName;

    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    private $eavSetupFactory;

    protected function configure()
    {
        $this->setName('treestone:postmigration:attributesets');
        $this->setDescription('command line script to run post migration');

        parent::configure();
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $initialDefaultAttributeSetId = $this->initialDefaultAttributeSetId;
        $initialDefaultAttributeSetName = $this->initialDefaultAttributeSetName;

        $output->writeln('The default Attribute set was initially ID: '. $initialDefaultAttributeSetId. ' Name: '.$initialDefaultAttributeSetName);

        $migrationAttributeSetId = $this->getAttributeSetIdByName('Migration_Default');
        $migrationAttributeSetName= 'Migration_Default';

        $output->writeln('The migration Attribute set is ID: '. $migrationAttributeSetId);
        $output->writeln('Now trying to set the default product attribute set to Migration_Default');

        //Set the default product attribute set to the migrated one if it isn't already
        if ($migrationAttributeSetId != $initialDefaultAttributeSetId){
            try {
                $this->setDefaultAttributeSet($migrationAttributeSetName);
                $output->writeln('Done. Lets Confirm');
            } catch (\Exception $e) {
            }
        }

        $output->writeln('The default Attribute set was initially ID: '. $initialDefaultAttributeSetId. ' Name: '.$initialDefaultAttributeSetName);

        //Confirm that it worked by re-getting the default attribute set
        try {
            $defaultAttributeSetId = $this->getDefaultAttributeSetId();
            $output->writeln('The default Attribute set is currently ID: '. $defaultAttributeSetId);
        } catch (LocalizedException $e) {
        }
        $defaultAttributeSetName = $this->getAttributeSetNameById($defaultAttributeSetId);
        $output->writeln('The default Attribute set is now ID: '. $defaultAttributeSetId. ' Name: '.$defaultAttributeSetName);

        //Delete the Default attributesetId
        $output->writeln('Lets Try deleting the initial default attribute set');
        try {
            $output->writeln($this->deleteInitialDefaultAttributeSet());
        } catch (InputException $e) {
        } catch (NoSuchEntityException $e) {
        }


        //Manage the attribute Groups
        // - Delete empty ones
        // - Remove the 'Migration_' prefix
        $output->writeln($this->UpdateAttributeGroups($defaultAttributeSetId));

        //rename the attribute set
        $this->renameAttributeSet($defaultAttributeSetId);

    }

    /**
     * @param \Magento\Eav\Model\AttributeSetRepository $attributeSetRepository
     * @param \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSetRepositoryInterface
     * @param \Magento\Eav\Api\AttributeGroupRepositoryInterface $attributeGroupRepositoryInterface
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepositoryInterface
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @throws LocalizedException
     */
    public function __construct(

        \Magento\Eav\Model\AttributeSetRepository $attributeSetRepository,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSetRepositoryInterface,
        \Magento\Eav\Api\AttributeGroupRepositoryInterface $attributeGroupRepositoryInterface,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepositoryInterface,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
    )
    {

        $this->attributeSetRepository = $attributeSetRepository;
        $this->attributeSetRepositoryInterface =$attributeSetRepositoryInterface;
        $this->attributeGroupRepositoryInterface = $attributeGroupRepositoryInterface;
        $this->attributeRepositoryInterface = $attributeRepositoryInterface;
        $this->searchCriteriaInterface = $searchCriteriaInterface;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroup = $filterGroup;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->eavSetupFactory =$eavSetupFactory;
        $this->setInitialDefaultAttributeSetId();
        $this->setInitialDefaultAttributeSetName();

        parent::__construct();
    }

    /**
     * @throws LocalizedException
     */
    public function setInitialDefaultAttributeSetId()
    {
        $initialDefaultAttributeSetId = $this->getDefaultAttributeSetId();
        $this->initialDefaultAttributeSetId = $initialDefaultAttributeSetId;
    }

    /**
     * @param mixed $initialDefaultAttributeSetName
     */
    public function setInitialDefaultAttributeSetName()
    {
        $initialDefaultAttributeSetName = $this->getAttributeSetNameById($this->initialDefaultAttributeSetId);
        $this->initialDefaultAttributeSetName = $initialDefaultAttributeSetName;
    }

    /**
     * Set Default Attribute Set to Entity Type
     *
     * @param string $name
     * @
     */
    public function setDefaultAttributeSet( $name)
    {
        $entityType = 'catalog_product';
        $eavFactory = $this->eavSetupFactory->create();
        $eavFactory->setDefaultSetToEntityType($entityType,$name);

    }

    /**
     * Get the default product attribute set Id - getting it from the catalog resource model
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDefaultAttributeSetId()
    {
        $entityType = 'catalog_product';
        $eavFactory = $this->eavSetupFactory->create();
        $defaultAttributeSetId = $eavFactory->getDefaultAttributeSetId($entityType);
        return $defaultAttributeSetId;
    }

    /**
     * function to retrieve the name of an attribute set - given it's Id
     * @param $id int - the id of the attribute set for which we want the name
     * @return bool|string
     */
    public function getAttributeSetNameById($id) {

        try {
            $attributeSet = $this->attributeSetRepositoryInterface->get($id);
            return $attributeSet->getAttributeSetName();
        } catch (NoSuchEntityException $e) {
            return false;
        }


    }

    /**
     * function to retrieve the Id of an attribute set - given it's name
     * @param The Name of the attribute set for which we want the Id
     * @return int|null
     */
    public function  getAttributeSetIdByName($name) {

        //the filter for the attribute set name
        $attributeSetNameFilter = $this->filterBuilder
            ->setField('attribute_set_name')
            ->setValue($name)
            ->setConditionType('eq')
            ->create();

        //the filter for the  entity_type_id
        $attributeSetEntityTypeFilter = $this->filterBuilder
            ->setField('entity_type_id')
            ->setValue(4)
            ->setConditionType('eq')
            ->create();

        //we need to create a separate 'group' for each filter
        //because all queries that are included in a single group get OR
        //queries that are in separate groups get AND
        // see more here https://devdocs.magento.com/guides/v2.3/extension-dev-guide/searching-with-repositories.html
        $attributeSetNameFilterGroup = $this->filterGroupBuilder->setFilters([$attributeSetNameFilter])->create();
        $attributeSetEntityFilterGroup = $this->filterGroupBuilder->setFilters([$attributeSetEntityTypeFilter])->create();

        //now that we have our groups, let's use them for the search criteria
        $searchCriteriaInterface = $this->searchCriteriaInterface->setFilterGroups([$attributeSetNameFilterGroup, $attributeSetEntityFilterGroup]);

        //setting the pageSize - don't know that it's needed
        $searchCriteria = $searchCriteriaInterface->setPageSize(10);

        //Finally - let's get that data :)
        $attributeSets = $this->attributeSetRepositoryInterface->getList($searchCriteria);

        //make sure there's only one result
        //On stores that migrated with more than one attribute sets, there will be more than one result
        //Will need to figure out something at that pointg
        if ($attributeSets->getTotalCount() == 1){

            foreach ($attributeSets->getItems() as $attributeSet) {


                $migrationAttributeSet = $attributeSet->getAttributeSetId();

                return $migrationAttributeSet;



            }
        }


    }

    /**
     * Delete the initial Magento Default Attribute Set
     * @return string - Either success or failure
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function deleteInitialDefaultAttributeSet() {


        if($initialAttributeSetId = $this->getAttributeSetIdByName('Default'))
        {
            $this->attributeSetRepository->deleteById($initialAttributeSetId);

            return 'Deleted Initial Default Attribute Set';
        }

        else return 'No initial Default Attribute Set found';

    }


    public function  UpdateAttributeGroups($attributeSetId){

        $output = '';

        //first get all the attribute groups
        $attributeSetIdFilter = $this->filterBuilder
            ->setField('attribute_set_id')
            ->setValue($attributeSetId)
            ->setConditionType('eq')
            ->create();
        $attributeSetIdFilterGroup = $this->filterGroupBuilder->setFilters([$attributeSetIdFilter])->create();
        $searchCriteriaInterface = $this->searchCriteriaInterface->setFilterGroups([$attributeSetIdFilterGroup]);
        $searchCriteria = $searchCriteriaInterface->setPageSize(25);

        try {
            $groupCollection = $this->attributeGroupRepositoryInterface->getList($searchCriteria);

            //Loop through each group
            foreach ($groupCollection->getItems() as $group) {

                $groupName = $group->getAttributeGroupName();

                $groupId = $group->getAttributeGroupId();

                //Check how many attributes are in this group
                $attributesInGroup = $this->getAttributesInGroup($groupId);

                if (count($attributesInGroup) < 1) { //If no attributes in this group

                    $message = 'deleting attribute group ' . $groupName . ' because it has no attributes in it' . PHP_EOL;
                    $output .= $message;
                    $this->deleteAttributeGroup($groupId);

                    //If we didn't delete it, let's rename it
                } elseif (strpos($groupName, 'Migration_') !== false) {

                    $exploded = explode('Migration_', $groupName);
                    $newName = $exploded[1];

                    $message = 'renaming attribute group ' . $groupName . ' to ' . $newName . PHP_EOL;
                    $output .= $message;
                    $this->renameAttributeGroup($groupId, $newName);

                }
            }

        } catch (NoSuchEntityException $e) {
        } catch (StateException $e) {
        }

        return $output;

    }

    /**
     * function to get all the attributes that are part of any given group
     * @param $groupId
     * @return array
     */
    public function getAttributesInGroup($groupId) {

        $attributeFilter = $this->filterBuilder
            ->setField('attribute_group_id')
            ->setValue($groupId)
            ->setConditionType('eq')
            ->create();
        $attributeFilterGroup = $this->filterGroupBuilder->setFilters([$attributeFilter])->create();

        $searchCriteriaInterface = $this->searchCriteriaInterface->setFilterGroups([$attributeFilterGroup]);
        $searchCriteria = $searchCriteriaInterface->setPageSize(25);

        $attributes = $this->attributeRepositoryInterface->getList('catalog_product', $searchCriteria);

        $result = [];
        foreach ($attributes->getItems() as $attribute) {

            $attribute->getDefaultFrontendLabel();
            $result[] = $attribute;
        }

        return $result;

    }

    /**
     * simple function for deleting an attribute group
     * @param $groupId
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function deleteAttributeGroup($groupId) {

        $this->attributeGroupRepositoryInterface->deleteById($groupId);

    }

    /**
     * Function to rename the attribute group
     *
     * @param $groupId
     * @param $newName
     * @return string|null
     */
    public function renameAttributeGroup($groupId, $newName){

        try {
            $attributeGroup = $this->attributeGroupRepositoryInterface->get($groupId);

            $attributeGroup->setAttributeGroupName($newName);
            try {
                $this->attributeGroupRepositoryInterface->save($attributeGroup);
            } catch (NoSuchEntityException $e) {
            } catch (StateException $e) {
            }
            return $attributeGroup->getAttributeGroupName();
        } catch (NoSuchEntityException $e) {
        }

        return true;
    }

    public function renameAttributeSet($id){

        try {
            $attributeSet = $this->attributeSetRepositoryInterface->get($id);
            $attributeSet->setAttributeSetName('Default');
            $attributeSet->save();
        } catch (NoSuchEntityException $e) {
        }
    }

}