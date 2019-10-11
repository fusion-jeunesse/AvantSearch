<?php
$advancedFormAttributes['id'] = 'search-filter-form';
$advancedFormAttributes['action'] = url('find');
$advancedFormAttributes['method'] = 'GET';
$advancedSubmitButtonText = __('Search');

// Instantiate search results objects needed to get option values.
$searchResults = new SearchResultsView();
$searchResultsTable = new SearchResultsTableView();
$searchResultsIndex = new SearchResultsIndexView();
$searchResultsTree = new SearchResultsTreeView();

$selectedLayoutId = $searchResultsTable->getLayoutId();
$resultsPerPage = $searchResultsTable->getResultsLimit();
$keywords = $searchResults->getKeywords();
$searchTitlesOnly = $searchResultsTable->getSearchTitles();
$searchFilesOnly = $searchResultsTable->getSearchFiles();
$condition = $searchResults->getKeywordsCondition();

$showTitlesOption = get_option(SearchConfig::OPTION_TITLES_ONLY) == true;
$simpleFieldOption = SearchConfig::getOptionDataForSimpleField();
$selectFieldOption = ElementsConfig::getOptionDataForSelectField();
$showDateRangeOption = SearchConfig::getOptionSupportedDateRange();

$pageTitle = __('Advanced Search');

queue_js_file('js.cookie');
queue_js_file('select2/select2.min');
queue_css_file('jquery-ui');
queue_css_file('select2.min');
echo head(array('title' => $pageTitle, 'bodyclass' => 'avantsearch-advanced'));
echo "<h1>$pageTitle</h1>";
echo "<div id='avantsearch-container'>";
?>

<form <?php echo tag_attributes($advancedFormAttributes); ?>>

	<!-- Left Panel -->
	<div id="avantsearch-primary">
		<div class="search-form-section">
			<div class="search-field">
				<div class="avantsearch-label-column">
					<?php echo $this->formLabel('keywords', __('Keywords')); ?><br>
				</div>
				<div class="avantsearch-option-column inputs">
					<?php echo $this->formText('keywords', $keywords, array('id' => 'keywords')); ?>
				</div>
			</div>
            <?php if ($showTitlesOption): ?>
            <div class="search-field">
                <div class="avantsearch-label-column">
                    <?php echo $this->formLabel('title-only', __('Search in')); ?><br>
                </div>
                <div class="avantsearch-option-column">
                	<div class="search-radio-buttons">
						<?php echo $this->formRadio('titles', $searchTitlesOnly, null, $searchResults->getKeywordSearchTitlesOptions()); ?>
					</div>
                </div>
            </div>
            <?php endif; ?>
            <div class="search-field">
				<div class="avantsearch-label-column">
					<?php echo $this->formLabel('keyword-conditions', __('Condition')); ?><br>
				</div>
				<div class="avantsearch-option-column">
					<div class="search-radio-buttons">
						<?php echo $this->formRadio('condition', $condition, null, $searchResults->getKeywordsConditionOptions()); ?>
					</div>
				</div>
			</div>
		</div>

		<?php if( !empty($simpleFieldOption) ): ?>
		<div id="search-simple-fields" class="search-form-section">
			<?php foreach($simpleFieldOption as $element_id => $element_name): ?>
			<div class="search-field">
				<div class="avantsearch-label-column">
					<?php echo $this->formLabel("element[$element_id]", __($element_name)); ?><br>
				</div>
				<div class="avantsearch-option-column inputs">
					<?php
						$inputName = "simple[$element_id]";
						if(isset($_GET['simple'][$element_id]))
							$value = $_GET['simple'][$element_id];
						else
							$value = '';
						$isSelect = false;
						$isMultiple = true;
						if( array_key_exists($element_id, $selectFieldOption) ) {
							$vocabulary = AvantElements::getSimpleVocabTerms($element_id);
							$isSelect = !empty($vocabulary);
						}
						if($isSelect) {
							$selectTerms = array();
							if(!$isMultiple) $selectTerms[''] = __('Select Below'); // + array_combine($vocabulary, $vocabulary);
							// Split options into optgroups
							$optgroup = null;
							foreach($vocabulary as $term) {
									if(defined('SimpleVocab_Controller_Plugin_SelectFilter::MATCH_OPTGROUP')
									and preg_match(SimpleVocab_Controller_Plugin_SelectFilter::MATCH_OPTGROUP,$term,$match)) {
											$optgroup = $match[1];
											$selectTerms[$optgroup] = array();
									} elseif(!is_null($optgroup)) {
											$selectTerms[$optgroup][$term] = $term;
									} else {
											$selectTerms[$term] = $term;
									}
							}
							echo get_view()->formSelect(
								$inputName,
								$value,
								array(
										'id' => 'element_'.$element_id,
										'data-element' => $element_id,
										'class' => 'simple-search-terms',
										'style' => 'width:100%'.($isMultiple?';height:auto':''),
										'multiple' => $isMultiple,
								),
								$selectTerms
							);
						} else {
							echo $this->formText(
								$inputName,
								$value,
								array(
									'id' => 'element_'.$element_id,
									'data-element' => $element_id,
									'class' => 'simple-search-terms',
									'style' => 'width:100%'
								)
							);
						}
					?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<div  id="search-narrow-by-fields" class="search-form-section">
			<div>
				<div class="avantsearch-label-column">
					<label><?php echo __('Fields'); ?></label>
				</div>
				<div class="avantsearch-option-column inputs">
					<?php
					// If the form has been submitted, retain the number of search fields used and rebuild the form
					if (!empty($_GET['advanced']))
						$search = $_GET['advanced'];
					else
						$search = array(array('field' => '', 'type' => '', 'value' => ''));

					foreach ($search as $i => $rows): ?>
						<div class="search-entry">
							<?php
							echo $this->formSelect(
								"advanced[$i][joiner]",
								@$rows['joiner'],
								array(
									'title' => __("Search Joiner"),
									'id' => null,
									'class' => 'advanced-search-joiner'
								),
								array(
									'and' => __('AND'),
									'or' => __('OR'),
								)
							);
							echo $this->formSelect(
								"advanced[$i][element_id]",
								@$rows['element_id'],
								array(
									'title' => __("Search Field"),
									'id' => null,
									'class' => 'advanced-search-element'
								),
								$searchResults->getAdvancedSearchFields()
							);
							echo $this->formSelect(
								"advanced[$i][type]",
								empty($rows['type']) ? 'contains' : $rows['type'],
								array(
									'title' => __("Search Type"),
									'id' => null,
									'class' => 'advanced-search-type'
								),
								array(
									'contains' => __('Contains'),
									'does not contain' => __('Does not contain'),
									'does not match' => __('Does not match'),
									'ends with' => __('Ends with'),
									'is empty' => __('Is empty'),
									'is exactly' => __('Is exactly'),
									'is not empty' => __('Is not empty'),
									'is not exactly' => __('Is not exactly'),
									'matches' => __('Matches'),
									'starts with' => __('Starts with'),
								)
							);
							echo $this->formText(
								"advanced[$i][terms]",
								@$rows['terms'],
								array(
									'size' => '20',
									'title' => __("Search Terms"),
									'id' => null,
									'class' => 'advanced-search-terms'
								)
							);
							?>
							<button type="button" class="remove_search" disabled="disabled"
									style="display: none;"><?php echo __('Remove field'); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
            <button type="button" class="add_search"><?php echo __('Add field'); ?></button>
        </div>

        <div class="search-form-section">
            <div>
                <div class="avantsearch-label-column">
                    <?php echo $this->formLabel('tag-search', __('Tags')); ?>
                </div>
                <div class="avantsearch-option-column inputs">
                    <?php echo $this->formText('tags', @$_REQUEST['tags'], array('size' => '40', 'id' => 'tags')); ?>
                </div>
            </div>
        </div>

        <?php if ($showDateRangeOption): ?>
        <div class="search-form-section">
			<div>
				<div class="avantsearch-label-column">
					<?php echo $this->formLabel('year-start', CommonConfig::getOptionTextForYearStart()); ?>
				</div>
				<div class="avantsearch-option-column inputs">
					<?php echo $this->formText('year_start', @$_REQUEST['year_start'], array('size' => '40', 'id' => 'year-start', 'title' => 'Four digit start year'));	?>
				</div>
			</div>

			<div>
				<div class="avantsearch-label-column">
					<?php echo $this->formLabel('year-end', CommonConfig::getOptionTextForYearEnd()); ?>
				</div>
				<div class="avantsearch-option-column inputs">
					<?php echo $this->formText('year_end', @$_REQUEST['year_end'], array('size' => '40', 'id' => 'year-end', 'title' => 'Four digit end year'));	?>
				</div>
			</div>
		</div>
        <?php endif; ?>
	</div>

	<!-- Right Panel -->
	<div id="avantsearch-secondary">
		<div id="search-button" class="panel">
			<input type="submit" class="submit button" value="<?php echo $advancedSubmitButtonText; ?>">
		</div>

        <?php echo $this->formLabel('view-label', __('Show search results in:')); ?>
        <div class="search-radio-buttons">
            <?php echo $this->formRadio('view', $searchResults->getViewId(), null, $searchResults->getViewOptions()); ?>
        </div>

        <div id="table-view-options" class="search-view-options">
            <div class="table-view-layout-option search-view-option">
                <?php
                echo $this->formLabel('layout', __('Table Layout'));
                $layoutSelectOptions = $searchResultsTable->getLayoutSelectOptions();
                echo $this->formSelect('layout', $selectedLayoutId, array(), $layoutSelectOptions);
                ?>
            </div>
        </div>

        <div id="index-view-options" class="search-view-options">
        	<div class="index-view-field-option search-view-option">
				<?php
				echo $this->formLabel('index-label', __('Index Field'));
				echo $this->formSelect('index', @$_REQUEST['index'], array(), $searchResultsIndex->getIndexFieldOptions());
				?>
            </div>
        </div>

        <div id="tree-view-options" class="search-view-options">
            <div class="tree-view-field-option search-view-option">
            <?php
            echo $this->formLabel('tree-label', __('Tree Field'));
            echo $this->formSelect('tree', @$_REQUEST['tree'], array(), $searchResultsTree->getTreeFieldOptions());
            ?>
            </div>
        </div>

        <div id="results-limit-options" class="search-view-options">
            <div class="table-view-limit-option search-view-option">
                <?php
                echo $this->formLabel('limit', __('Results Per Page'));
                echo $this->formSelect('limit', @$_REQUEST['limit'], array(), $searchResultsTable->getLimitOptions());
                ?>
            </div>
        </div>

        <div class="search-images-only-option">
            <?php echo $this->formLabel('view-label', __('Search:')); ?>
            <div class="search-radio-buttons">
                <?php echo $this->formRadio('files', $searchFilesOnly, null, $searchResults->getFilesOnlyOptions()); ?>
            </div>
        </div>

        <div class="search-form-reset-button">
            <?php echo '<a href="' . WEB_ROOT . '/find/advanced">'.__('Reset all search options').'</a>'; ?>
        </div>
    </div>
</form>
</div>

<?php echo js_tag('items-search'); ?>

<script type="text/javascript">
    var tableViewOptions = jQuery('#table-view-options');
    var indexViewOptions = jQuery('#index-view-options');
    var treeViewOptions = jQuery('#tree-view-options');
    var resultsLimitOptions = jQuery('#results-limit-options');

    var DEFAULT_LAYOUT = '<?php echo SearchResultsTableView::DEFAULT_LAYOUT; ?>';
    var RELATIONSHIPS_LAYOUT = '<?php echo SearchResultsTableView::RELATIONSHIPS_LAYOUT; ?>';

    function disableDefaultRadioButton(name, defaultValue)
    {
        var checkedButton = jQuery("input[name='" + name + "']:checked");
        var value = checkedButton.val();
        if (value === defaultValue)
        {
            checkedButton.prop("disabled", true);
        }
    }

    function disableEmptyField(selector)
    {
        var field = jQuery(selector);
        var fieldExists = field.size() > 0;
        if (fieldExists && field.val().trim().length === 0)
        {
            field.prop("disabled", true);
        }
    }

    function disableHiddenInput(selector)
    {
        var input = jQuery(selector);
        var hidden = input.is(':hidden');
        if (hidden)
        {
            input.prop("disabled", true);
        }
    }

    function disableHiddenSelection(selector)
    {
        var select = jQuery(selector);
        var selectedOption = select.find(":selected");
        var hidden = select.is(':hidden');
        if (hidden)
        {
            select.prop("disabled", true);
        }
    }

    function setView(viewId)
    {
        viewId = parseInt(viewId, 10);

        // Hide all options.
        tableViewOptions.hide();
        indexViewOptions.hide();
        treeViewOptions.hide();
        resultsLimitOptions.hide();

        var selectedViewOptions = null;

        // Show the options for the selected view.
        if (viewId === <?php echo SearchResultsViewFactory::TABLE_VIEW_ID; ?>)
            selectedViewOptions = tableViewOptions;
        else if (viewId === <?php echo SearchResultsViewFactory::INDEX_VIEW_ID; ?>)
            selectedViewOptions = indexViewOptions;
        else if (viewId === <?php echo SearchResultsViewFactory::TREE_VIEW_ID; ?>)
            selectedViewOptions = treeViewOptions;

        if (selectedViewOptions)
        {
            selectedViewOptions.slideDown('slow');
        }
        if (viewId === <?php echo SearchResultsViewFactory::TABLE_VIEW_ID; ?> ||
            viewId === <?php echo SearchResultsViewFactory::IMAGE_VIEW_ID; ?>)
        {
            resultsLimitOptions.slideDown('slow');
        }
    }

    function updateRelationshipsOption(changed)
    {
        var showRelationships = jQuery("#relationships").prop('checked');
        var layoutSelector = jQuery('#layout');
        var selectedLayoutId = layoutSelector.val();

        if (changed)
        {
            if (showRelationships)
            {
                // The user checked the Show Relationships box.
                // Automatically change the selection to show the Relationships layout option.
                selectedLayoutId = RELATIONSHIPS_LAYOUT;
            }
            else
            {
                // The user unchecked the Show Relationships box.
                // Make sure that the Relationships layout option is not selected.
                if (selectedLayoutId === RELATIONSHIPS_LAYOUT)
                    selectedLayoutId = DEFAULT_LAYOUT;
            }
        }

        // Show the selected layout option and enable/disable the Relationships option.
        layoutSelector.val(selectedLayoutId);
        jQuery("#layout option[value='" + RELATIONSHIPS_LAYOUT + "']").attr("disabled", !showRelationships);
    }

    jQuery(document).ready(function () {
        Omeka.Search.activateSearchButtons();

        // Show the options for the selected view.
        var viewSelection = jQuery("[name='view']:checked").val();
        setView(viewSelection);

        var userChangedOption = false;
        updateRelationshipsOption(userChangedOption);

        limitSelector = jQuery('#limit');
        limitSelector.val(<?php echo $resultsPerPage; ?>);

        jQuery("[name='view']").change(function (e)
        {
            // The user changed the results view.
            var viewSelection = jQuery(this).val();
            setView(viewSelection);
        });

        jQuery("[name='relationships']").change(function (e)
        {
            var userChangedOption = true;
            updateRelationshipsOption(userChangedOption);
        });

        limitSelector.change(function (e)
        {
            // The user changed results per page. Save the selection in a cookie.
            var resultsSelection = jQuery(limitSelector, 'option:selected').val();
            jQuery('#simple-results').text(resultsSelection);
            Cookies.set('SEARCH-LIMIT', resultsSelection, {expires: 7});
        });

        jQuery('#search-filter-form').submit(function()
        {
            // Disable fields that should not get emitted as part of the query string because:
            // * The user provided no value, or
            // * The default value is selected as does not need to be in the query string

            var field0Id = jQuery("select[name='advanced[0][element_id]']");
            var field0Condition = jQuery("select[name='advanced[0][type]']");
            if (field0Id.val() === '' || field0Condition.val() === '')
            {
                var field0Joiner = jQuery("select[name='advanced[0][joiner]']");
                var field0Value = jQuery("input[name='advanced[0][terms]']");

                field0Joiner.prop("disabled", true);
                field0Id.prop("disabled", true);
                field0Condition.prop("disabled", true);
                field0Value.prop("disabled", true);
            }

            disableEmptyField('#keywords');
            disableEmptyField('#tags');
            disableEmptyField('#year-start');
            disableEmptyField('#year-end');

            disableDefaultRadioButton('files', '<?php echo SearchResultsView::DEFAULT_SEARCH_FILES; ?>');
            disableDefaultRadioButton('titles', '<?php echo SearchResultsView::DEFAULT_SEARCH_TITLES; ?>');
            disableDefaultRadioButton('condition', '<?php echo SearchResultsView::DEFAULT_KEYWORDS_CONDITION; ?>');
            disableDefaultRadioButton('view', '<?php echo SearchResultsView::DEFAULT_VIEW; ?>');

            disableHiddenSelection('#layout');
            disableHiddenSelection('#index');
            disableHiddenSelection('#tree');

            disableHiddenInput('#limit');

            if (navigator.cookieEnabled)
            {
                // When cookies are disabled, pass the limit option on the query string.
                var limitSelector = jQuery("#limit");
                limitSelector.prop("disabled", true);
            }
        });
    });
</script>
<?php
// Enable suggestions to AvantElements Suggest fields
$suggestFields = null;
if(plugin_is_active('AvantElements'))
	$suggestFields = ElementSuggest::getIdsForSuggestElements();
if(!empty($suggestFields)) : ?>
<script type="text/javascript">
	// Enable select2 on multiple select fields
	jQuery('#search-simple-fields select.simple-search-terms[multiple]').select2();
	var suggestFields = [<?php echo $suggestFields; ?>];

	// Enable auto-complete on simple text fields
  jQuery('#search-simple-fields input.simple-search-terms').each(function() {
		var element = jQuery(this).data('element');
		if( suggestFields.indexOf(element) > -1 ) {
			jQuery(this).autocomplete({
				source: '<?php echo url('/elements/suggest/'); ?>' + element,
				minLenght: 1
			});
		}
	});

	// Enable auto-complete for the terms input of each
	// avantsearch-option-column input set.
	// Source is determined the id of selected field.
	function updateAutoComplete() {

		jQuery('#search-narrow-by-fields .search-entry').each(function() {
			var element = jQuery(this).find('select.advanced-search-element');
			var terms = jQuery(this).find('input.advanced-search-terms');

			if( suggestFields.indexOf(parseInt(element.val())) > -1 ) {
				terms.autocomplete({
					source: '<?php echo url('/elements/suggest/'); ?>' + element.val(),
					minLength: 1
				});
			} else {
				terms.autocomplete({ source: [] });
			}

		});

	}

	// Initialize on initial form load
	jQuery(document).ready( updateAutoComplete );
	// Update on element selection change
	jQuery('select.advanced-search-element').change( updateAutoComplete)
	// Update new rows
	jQuery('.add_search').click(function() {
		setTimeout(function() {
			jQuery('.search-entry:last-of-type select.advanced-search-element').change( updateAutoComplete );
			updateAutoComplete();
		}, 200);
	});

</script>
<?php endif; ?>

<?php echo foot(); ?>
