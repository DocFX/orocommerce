<?php

namespace OroB2B\Bundle\ProductBundle\DataGrid\Extension\Theme;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;

class ThemeExtension extends AbstractExtension
{
    const GRID_NAME = 'frontend-products-grid';
    const METADATA_THEME_KEY = 'themeOptions';

    /**
     * @var DataGridThemeHelper
     */
    protected $themeHelper;

    /**
     * @param DataGridThemeHelper $themeHelper
     */
    public function __construct(DataGridThemeHelper $themeHelper)
    {
        $this->themeHelper = $themeHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getName() === self::GRID_NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $this->validateConfiguration(
            new Configuration(),
            ['theme' => $config->offsetGetByPath(Configuration::THEME_PATH)]
        );
    }
}
