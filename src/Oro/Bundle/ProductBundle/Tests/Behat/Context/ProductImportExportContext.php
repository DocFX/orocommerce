<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ImportExportBundle\Tests\Behat\Context\ImportExportContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Symfony\Component\Finder\Finder;

/**
 * Behat context for product import/export functionality.
 */
class ProductImportExportContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    const PRODUCT_ENTITY = 'Products';
    const PRODUCT_PROCESSOR = 'oro_product_product';
    const PRODUCT_ATTRIBUTES_PROCESSOR = 'oro_entity_config_attribute.export_template';

    /** @var ImportExportContext */
    private $importExportContext;

    /**
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->importExportContext = $environment->getContext(ImportExportContext::class);
    }

    /**
     * This method makes non-strict comparison of data from the downloaded file for exported Products.
     *
     * Checks whether the listed columns (in any order) and corresponding data is present.
     *
     * @Given /^Exported file with Products contains at least the following data:$/
     *
     * @param TableNode $expectedEntities
     */
    public function exportedFileWithProductsContainsAtLeastFollowingData(TableNode $expectedEntities)
    {
        $this->importExportContext->exportedFileForEntityWithProcessorContainsAtLeastFollowingColumns(
            self::PRODUCT_ENTITY,
            $expectedEntities,
            self::PRODUCT_PROCESSOR
        );
    }

    /**
     * This method prepares product image for product image import.
     *
     * @Given I upload product images files
     */
    public function copyImageFromFixturesToImageImportExportDir()
    {
        $this->copyFilesToStorage(
            __DIR__ . '/../Features/Fixtures/product_images_import',
            $this->getProductImportImagesFileManager()
        );
    }

    /**
     * This method prepares product image for product image import.
     *
     * @Given I copy product fixture files to upload directories
     */
    public function copyProductFixtureFilesToUploadDirs(): void
    {
        $sourcePath = sprintf('%s%s', __DIR__, '/../Features/Fixtures/files_import');
        // used for test a relative path
        $this->copyFilesToStorage($sourcePath, $this->getProductImportImagesFileManager());
        // used for test an URL
        $this->copyFilesToStorage($sourcePath, $this->getPublicMediaCacheFileManager(), 'test_import');
        // used for test an absolute path
        $this->copyFiles(
            $sourcePath,
            $this->getContainer()->getParameter('kernel.project_dir') . '/var/data/test_import/'
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: Given I copy product fixture "000.png" to import upload dir as "091.png"
     *
     * @Given /^I copy product fixture "(?P<filename>(?:[^"]|\\")*)" to import upload dir as "(?P<newFilename>(?:[^"]|\\")*)"$/
     *
     * @param string $filename
     * @param string $newFilename
     */
    //@codingStandardsIgnoreEnd
    public function copyProductFixtureFileToImportFilesDir(string $filename, string $newFilename): void
    {
        $this->getProductImportImagesFileManager()->writeFileToStorage(
            __DIR__ . '/../Features/Fixtures/files_import/' . $this->fixStepArgument($filename),
            $newFilename
        );
    }

    /**
     * Download product attributes' data template from attributes grid page
     *
     * @When /^(?:|I )download Product Attributes' Data Template file$/
     */
    public function downloadProductAttributesDataTemplate()
    {
        $this->importExportContext->downloadTemplateFileByProcessor(self::PRODUCT_ATTRIBUTES_PROCESSOR);
    }

    /**
     * Fill import csv file
     * Example: And I fill product import file with data:
     *            | Account Customer name | Channel Name        | Opportunity name | Status Id   |
     *            | Charlie               | First Sales Channel | Opportunity one  | in_progress |
     *            | Samantha              | First Sales Channel | Opportunity two  | in_progress |
     *
     * @Given /^(?:|I )fill product import file with data:$/
     */
    public function iFillImportFileWithData(TableNode $table)
    {
        $websiteUrl = $this->getContainer()->get('oro_website.resolver.website_url_resolver')->getWebsiteUrl();

        $this->importExportContext->setAbsoluteUrl($websiteUrl);
        $this->importExportContext->iFillImportFileWithData($table);
        $this->importExportContext->setAbsoluteUrl(null);
    }

    /**
     * @param string      $filesPath
     * @param FileManager $fileManager
     * @param string|null $directory
     */
    private function copyFilesToStorage(string $filesPath, FileManager $fileManager, string $directory = null): void
    {
        $finder = new Finder();
        /** @var \SplFileInfo[] $files */
        $files = $finder->files()->in($filesPath);
        foreach ($files as $file) {
            $fileName = $file->getFilename();
            if ($directory) {
                $fileName = $directory . '/' . $fileName;
            }
            $fileManager->writeFileToStorage($file->getPathname(), $fileName);
        }
    }

    /**
     * @return FileManager
     */
    private function getProductImportImagesFileManager(): FileManager
    {
        return $this->getContainer()->get('oro_product.importexport.file_manager.product_images');
    }

    /**
     * @return FileManager
     */
    private function getPublicMediaCacheFileManager(): FileManager
    {
        return $this->getContainer()->get('oro_attachment.manager.public_mediacache');
    }
}
