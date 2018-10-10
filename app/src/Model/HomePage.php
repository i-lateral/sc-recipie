<?php

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverCommerce\CatalogueAdmin\Forms\GridField\GridFieldConfig_CatalogueRelated;
use SilverCommerce\CatalogueAdmin\Model\CatalogueProduct;
use SilverCommerce\CatalogueAdmin\Model\CatalogueCategory;

class HomePage extends Page
{
    private static $db = [];

    private static $has_one = [];

    private static $has_many = [
        'Sections' => Page::class,
        'FeaturedProducts' => Product::class,
        'FeaturedCategories' => Category::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab(
            'Root.Products',
            GridField::create(
                'FeaturedProducts',
                'Products',
                $this->FeaturedProducts()
            )->setConfig(GridFieldConfig_CatalogueRelated::create(CatalogueProduct::class))
        );

        $fields->addFieldToTab(
            'Root.Categories',
            GridField::create(
                'FeaturedCategories',
                'Categories',
                $this->FeaturedCategories()
            )->setConfig(GridFieldConfig_CatalogueRelated::create(CatalogueCategory::class))
        );

        $config = GridFieldConfig_RelationEditor::create();
        $config->addComponent(new GridFieldVersionedOrderableRows('HomeSort'))
            ->removeComponentsByType(GridFieldEditButton::class)
            ->addComponent(new GridFieldSiteTreeState())
            ->addComponent(new GridFieldSiteTreeEditButton());

        $fields->addFieldToTab(
            'Root.Sections',
            GridField::create(
                'Sections',
                'Sections',
                $this->Sections()
            )->setConfig($config)
        );
        
        return $fields;
    }
}
