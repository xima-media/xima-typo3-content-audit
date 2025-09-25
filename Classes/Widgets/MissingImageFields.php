<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class MissingImageFields implements WidgetInterface
{
    protected ServerRequestInterface $request;

    public function __construct(
        protected readonly WidgetConfigurationInterface $configuration,
        protected readonly ListDataProviderInterface $dataProvider,
        protected readonly ?ButtonProviderInterface $buttonProvider = null,
        protected array $options = []
    ) {
    }

    public function renderWidgetContent(): string
    {
        $template = GeneralUtility::getFileAbsFileName('EXT:xima_typo3_content_audit/Resources/Private/Templates/MissingImageFields.html');

        // preparing view
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setFormat('html');
        $view->setTemplateRootPaths(['EXT:xima_typo3_content_audit/Resources/Private/Templates/']);
        $view->setPartialRootPaths(['EXT:xima_typo3_content_audit/Resources/Private/Partials/']);
        $view->setTemplatePathAndFilename($template);

        $missingField = $this->options['missingField'] ?? 'alternative';
        $this->dataProvider->setMissingField($missingField);

        $resultSet = $this->dataProvider->getItems();
        $view->assignMultiple([
            'configuration' => $this->configuration,
            'records' => $resultSet['results'],
            'button' => $this->buttonProvider,
            'options' => $this->options,
            'missingFieldCount' => $resultSet['missingFieldCount'],
            'totalCount' => $resultSet['totalCount'],
            'missingFieldLabel' => $GLOBALS['LANG']->sL($GLOBALS['TCA']['sys_file_metadata']['columns'][$missingField]['label'] ?? $missingField),
            'version' => GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion(),
        ]);
        return $view->render();
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
