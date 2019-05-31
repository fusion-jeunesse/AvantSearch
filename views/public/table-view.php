<?php
/* @var $searchResults SearchResultsTableView */

$results = $searchResults->getResults();
$totalResults = $searchResults->getTotalResults();
$showRelationships = $searchResults->getShowRelationships();
$resultsMessage = SearchResultsView::getSearchResultsMessage();

// Values passed to table-view-script.php
$imageFilterId = $searchResults->getSelectedImageFilterId();
$layoutId = $searchResults->getSelectedLayoutId();
$limitId = $searchResults->getSelectedLimitId();
$sortId = $searchResults->getSelectedSortId();
$viewId = $searchResults->getSelectedViewId();

$layoutsData = $searchResults->getLayoutsData();
$detailLayoutData = $searchResults->getDetailLayoutData();
$column1 =  isset($detailLayoutData[0]) ? $detailLayoutData[0] : array();
$column2 =  isset($detailLayoutData[1]) ? $detailLayoutData[1] : array();

$useElasticsearch = $searchResults->getUseElasticsearch();

echo head(array('title' => $resultsMessage));
echo "<div class='search-results-container'>";
$paginationLinks = pagination_links();
echo "<div class='search-results-title'><span>$resultsMessage</span>$paginationLinks</div>";

$resultControlsHtml = '';
if ($totalResults)
{
    // The order here is the left to right order of these controls on the Search Results page.
    $resultControlsHtml .= $searchResults->emitSelectorForView();
    $resultControlsHtml .= $searchResults->emitSelectorForLayout($layoutsData);
    $resultControlsHtml .= $searchResults->emitSelectorForLimit();
    $resultControlsHtml .= $searchResults->emitSelectorForSort();
    $resultControlsHtml .= $searchResults->emitSelectorForImageFilter();
}
?>

<div class="search-results-buttons">
    <?php
    if (!$useElasticsearch)
    {
        echo $searchResults->emitModifySearchButton();
    }
    ?>
</div>

<?php echo $searchResults->emitSearchFilters($resultControlsHtml, ''); ?>

<?php if ($totalResults > 0): ?>
    <?php if ($useElasticsearch): ?>
        <section id="search-table-elasticsearch-sidebar">
            <?php
            $query = $searchResults->getQuery();
            $facets = $searchResults->getFacets();
            echo $this->partial('/elasticsearch-facets.php', array(
                    'query' => $query,
                    'aggregations' => $facets,
                    'totalResults' => $totalResults
                )
            );
            ?>
        </section>
        <section id="search-table-elasticsearch-results">
    <?php endif; ?>
    <table id="search-table-view">
        <thead>
        <tr>
            <?php echo $this->partial('/table-view-header.php', array('searchResults' => $searchResults)); ?>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($results as $result)
        {
            if (!$useElasticsearch)
            {
                set_current_record('Item', $result);
            }
            echo $this->partial(
                '/table-view-row.php',
                array(
                    'item' => $result,
                    'searchResults' => $searchResults,
                    'column1' => $column1,
                    'column2' => $column2)
            );
        }
        ?>
        </tbody>
    </table>
    <?php if ($useElasticsearch): ?>
        </section>
    <?php endif; ?>
    <?php
        echo $this->partial('/table-view-script.php', array('imageFilterId' => $imageFilterId, 'layoutId' => $layoutId, 'limitId' => $limitId, 'sortId' => $sortId, 'viewId' => $viewId));
        echo "<div id='search-pagination-bottom'>$paginationLinks</div>";
        echo '</div>';
    ?>
<?php else: ?>
    <div id="no-results">
        <p>
            <?php
            $error = $searchResults->getError();
            $message = empty($error) ? __('Your search returned no results.') : $error;
            echo $message;
            ?>
        </p>
    </div>
<?php endif; ?>
<?php echo foot(); ?>