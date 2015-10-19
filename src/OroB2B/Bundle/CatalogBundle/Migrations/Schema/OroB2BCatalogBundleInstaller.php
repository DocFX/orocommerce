<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroB2BCatalogBundleInstaller implements Installation, NoteExtensionAwareInterface
{
    const ORO_B2B_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME = 'orob2b_catalog_cat_short_desc';
    const ORO_B2B_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME = 'orob2b_catalog_cat_long_desc';
    const ORO_B2B_CATALOG_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const ORO_B2B_FALLBACK_LOCALIZE_TABLE_NAME ='orob2b_fallback_locale_value';

    /** @var NoteExtension */
    protected $noteExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * Sets the NoteExtension
     *
     * @param NoteExtension $noteExtension
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BCatalogCategoryTable($schema);
        $this->createOroB2BCatalogCategoryTitleTable($schema);
        $this->createOrob2BCategoryToProductTable($schema);
        $this->createOroB2BCatalogCategoryShortDescriptionTable($schema);
        $this->createOroB2BCatalogCategoryLongDescriptionTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BCatalogCategoryForeignKeys($schema);
        $this->addOroB2BCatalogCategoryTitleForeignKeys($schema);
        $this->addOrob2BCategoryToProductForeignKeys($schema);
        $this->addOroB2BCatalogCategoryShortDescriptionForeignKeys($schema);
        $this->addOroB2BCatalogCategoryLongDescriptionForeignKeys($schema);
    }

    /**
     * Create orob2b_catalog_category table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCatalogCategoryTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_catalog_category');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $this->noteExtension->addNoteAssociation($schema, 'orob2b_catalog_category');
    }

    /**
     * Create orob2b_catalog_category_title table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCatalogCategoryTitleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_catalog_category_title');
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create orob2b_category_to_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCategoryToProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_category_to_product');
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['category_id', 'product_id']);
        $table->addUniqueIndex(['product_id']);
    }

    /**
     * Add orob2b_catalog_category foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCatalogCategoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_catalog_category');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['parent_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_catalog_category_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCatalogCategoryTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_catalog_category_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_fallback_locale_value'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_category_to_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCategoryToProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_category_to_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_catalog_category_short_description table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCatalogCategoryShortDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->addIndex(['category_id'], 'idx_a2b14ef512469de2', []);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_a2b14ef5eb576e89');
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
    }

    /**
     * Create orob2b_catalog_category_long_description table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCatalogCategoryLongDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME);
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_4f7c279feb576e89');
        $table->addIndex(['category_id'], 'idx_4f7c279f12469de2', []);
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
    }

    /**
     * Add orob2b_catalog_category_short_description foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCatalogCategoryShortDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_catalog_category_long_description foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCatalogCategoryLongDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
