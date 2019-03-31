<?php

class SearchResultsTableViewRowData
{
    protected $columnsData;
    public $elementValue;
    protected $hierarchyElements;
    public $itemThumbnailHtml;
    protected $searchResults;
    protected $showComingledResults;
    protected $useElasticsearch;

    public function __construct($item, SearchResultsTableView $searchResults)
    {
        $this->searchResults = $searchResults;
        $this->columnsData = $searchResults->getColumnsData();
        $this->hierarchyElements = SearchConfig::getOptionDataForTreeView();
        $this->useElasticsearch = $searchResults->getUseElasticsearch();
        $this->showComingledResults = $searchResults->getShowComingledResults();
        $this->initializeData($item);
    }

    protected function filterHierarchicalElementText($elementId, $text)
    {
        if (SearchConfig::isHierarchyElementThatDisplaysAs($elementId, 'leaf'))
        {
            $index = strrpos($text, ',', -1);

            if ($index !== false)
            {
                // Filter out the ancestry to leave just the leaf text.
                $text = trim(substr($text, $index + 1));
            }
        }

        return $text;
    }

    protected function generateDateRange()
    {
        $yearStartElementName = CommonConfig::getOptionTextForYearStart();
        $yearEndElementName = CommonConfig::getOptionTextForYearEnd();

        if (empty($yearStartElementName) || empty($yearEndElementName) || !isset($this->elementValue['Date']))
        {
            // This feature is only support for installations that have all three date elements.
            return;
        }

        $date = $this->elementValue['Date']['text'];
        $yearStartText = $this->elementValue[$yearStartElementName]['text'];
        $yearEndText = $this->elementValue[$yearEndElementName]['text'];

        if (empty($date) && !empty($yearStartText))
        {
            // The date is empty so show the year start/end range.
            $this->elementValue['Date']['text'] = "$yearStartText - $yearEndText";
        }
    }

    protected function generateDescription($item)
    {
        $hasHighlights = false;
        if ($this->useElasticsearch && isset($item['highlight']['element.description']))
        {
            $hasHighlights = true;
            $descriptionText = '';
            $highlights = $item['highlight']['element.description'];
            foreach ($highlights as $highlight)
            {

                $descriptionText .= $highlight;
            }
        }
        else
        {
            // Get the description text, making sure that the Description element is defined.
            $descriptionText = isset($this->elementValue['Description']['text']) ? $this->elementValue['Description']['text'] : '';
        }

        // Strip away line breaks;
        $descriptionText = str_replace('<br />', ' ', $descriptionText);
        $descriptionText = str_replace(array("\r", "\n", "\t"), ' ', $descriptionText);

        // Shorten the description text if it's too long.
        $maxLength = 250;
        $truncatedLength = 0;

        if ($hasHighlights)
        {
            // Allow a little more context for descriptions with highlighting. Also take into account
            // the fact that the <span> tags add length that is not part of the content.
            $maxLength = 300;
            $text = $descriptionText;
            $start = 0;
            while (true)
            {
                $start = strpos($text, '<span', $start);
                $end = strpos($text, 'span>', $start) + strlen('span>');
                if ($start === false || $end === false)
                {
                    break;
                }
                $x = substr($text, $start, $end - $start);
                if ($start > $maxLength)
                {
                    break;
                }
                if ($end > $maxLength)
                {
                    $truncatedLength = $end + 1;
                    break;
                }
                $start = $end;
            }
        }
        $truncatedLength = max($truncatedLength, $maxLength);

        $this->elementValue['Description']['text'] = $descriptionText;
        $descriptionText = $this->elementValue['Description']['text'];

        if (strlen($descriptionText) > $truncatedLength)
        {
            // Truncate the description at whitespace and add an elipsis at the end.
            $shortText = preg_replace("/^(.{1,$truncatedLength})(\\s.*|$)/s", '\\1', $descriptionText);
            $shortTextLength = strlen($shortText);
            $remainingText = '<span class="search-more-text">' . substr($descriptionText, $shortTextLength) . '</span>';
            $remainingText .= '<span class="search-show-more"> ['. __('show more') . ']</span>';
            $this->elementValue['Description']['text'] = $shortText . $remainingText;
        }

        $this->elementValue['Description']['detail'] = $this->searchResults->emitFieldDetail('Description', $this->elementValue['Description']['text']);
    }

    protected function generateIdentifierLink($item)
    {
        // Create a link for the identifier.
        if ($this->useElasticsearch)
        {
            $identifier = $item['_source']['element']['identifier'];
            if ($this->showComingledResults)
            {
                $ownerId = $item['_source']['ownerid'];
                $identifier = $ownerId . '-' . $identifier;
            }
            $itemUrl = $item['_source']['url'];
            $idLink = "<a href='$itemUrl'>$identifier</a>";
            $public = $item['_source']['public'];
        }
        else
        {
            $idLink = link_to_item(ItemMetadata::getItemIdentifierAlias($item));
            $public = $item->public == 0;
        }

        if (!$public)
        {
            // Indicate that this item is private.
            $idLink = '* ' . $idLink;
        }
        $this->elementValue[ItemMetadata::getIdentifierAliasElementName()]['text'] = $idLink;
    }

    protected function generateThumbnailHtml($item)
    {
        $itemPreview = new ItemPreview($item, $this->useElasticsearch, $this->showComingledResults);
        $this->itemThumbnailHtml = $itemPreview->emitItemHeader();
        $this->itemThumbnailHtml .= $itemPreview->emitItemThumbnail(false);
    }

    protected function generateTitles($item)
    {
        // Create a link for the Title followed by a list of AKA (Also Known As) titles.

        if ($this->useElasticsearch)
        {
            if (isset($item['_source']['element']['title']))
            {
                $texts = $item['_source']['element']['title'];
                $titles = is_array($texts) ? $texts : array($texts);
                $itemUrl =  $item['_source']['url'];
                $titleLink = "<a href='$itemUrl'>$titles[0]</a>";
            }
            else
            {
                $titles = [];
                $titleLink = __('[Untitled]');
            }
            $this->elementValue['Title']['text'] = $titleLink;
        }
        else
        {
            $titleLink = link_to_item(ItemMetadata::getItemTitle($item));
            $this->elementValue['Title']['text'] = $titleLink;
            $titles = ItemMetadata::getAllElementTextsForElementName($item, 'Title');
        }

        foreach ($titles as $key => $title)
        {
            if ($key == 0)
            {
                continue;
            }
            $this->elementValue['Title']['text'] .= '<div class="search-title-aka">' . html_escape($title) . '</div>';
        }

        if ($this->showComingledResults)
        {
            $ownerSite = $item['_source']['ownersite'];
            $this->elementValue['Title']['text'] .= "<div class='search-owner-site'>$ownerSite</div>";
        }
    }

    public static function getElementDetail($data, $elementName)
    {
        return $data->elementValue[$elementName]['detail'];
    }

    protected function getElementTextsAsHtml($item, $elementId, $elementName, $elementTexts, $filtered)
    {
        if (!empty($elementTexts) && plugin_is_active('AvantElements'))
        {
            // If the element is specified as a checkbox using AvantElements, then return its display value for true.
            // By virtue of the element being displayed, its value must be true. By virtue of being a checkbox, there's
            // no meaning to having multiple instance of the value, so simply return the value for true e.g. "Yes".
            $checkboxFieldsData = ElementsConfig::getOptionDataForCheckboxField();
            if (array_key_exists($elementId, $checkboxFieldsData))
            {
                $definition = $checkboxFieldsData[$elementId];
                return $definition['checked'];
            }
        }

        $texts = '';

        // Determine whether HTML characters within the text should be escaped. Don't escape them if the element
        // allows HTML and the element's HTML checkbox is checked. Note that the getElementTexts function returns
        // an ElementTexts object which is different than the $elementTexts array passed to this function.
        if ($this->useElasticsearch)
        {
            $htmlFields = $item['_source']['html'];
            $isHtmlElement = in_array(strtolower($elementName), $htmlFields);
        }
        else
        {
            $elementSetName = ItemMetadata::getElementSetNameForElementName($elementName);
            $isHtmlElement = count($elementTexts) > 0 && $item->getElementTexts($elementSetName, $elementName)[0]->isHtml();
        }

        foreach ($elementTexts as $key => $elementText)
        {
            if ($key != 0)
            {
                $texts .= '<br/>';
            }

            $text = $filtered ? $this->filterHierarchicalElementText($elementId, $elementText) : $elementText;
            $texts .= $isHtmlElement ? $text : html_escape($text);
        }

        return $texts;
    }

    protected function initializeData($item)
    {
        $this->elementValue = array();

        $this->readMetadata($item);
        $this->generateDescription($item);
        $this->generateDateRange();
        $this->generateIdentifierLink($item);
        $this->generateTitles($item);
        $this->generateThumbnailHtml($item);
    }

    protected function readMetadata($item)
    {
        $elasticSearchElementTexts = $this->useElasticsearch ? $item['_source']['element'] : null;

        foreach ($this->columnsData as $elementId => $column)
        {
            $elementName = $column['name'];

            if ($elementName != 'Title')
            {
                $elementTexts = array();

                if ($this->useElasticsearch)
                {
                    $elasticSearchFieldName = strtolower($elementName);
                    if (isset($elasticSearchElementTexts[$elasticSearchFieldName]))
                    {
                        $texts = $elasticSearchElementTexts[$elasticSearchFieldName];
                        if (is_array($texts))
                        {
                            $elementTexts = $texts;
                        }
                        else
                        {
                            $elementTexts[0] = $texts;
                        }
                    }
                }
                else
                {
                    $elementTexts = ItemMetadata::getAllElementTextsForElementName($item, $elementName);
                }
                $filteredText =  $this->getElementTextsAsHtml($item, $elementId, $elementName, $elementTexts, true);

                if ($elementName != 'Description')
                {
                    $this->elementValue[$elementName]['detail'] = $this->searchResults->emitFieldDetail($column['alias'], $filteredText);
                }
            }

            $this->elementValue[$elementName]['text'] = $filteredText;
        }

        // Create a psuedo element value for tags since there is no actual tags element.
        if ($this->useElasticsearch)
        {
            $tags = $item['_source']['tags'];
            $tags = implode(', ', $tags);
            $score =  $this->userIsAdmin() ? $item['_score'] : '';
        }
        else
        {
            $tags = metadata('item', 'has tags') ? tag_string('item', 'find') : '';
            $score = '';
        }
        $this->elementValue['<tags>']['text'] = '';
        $this->elementValue['<tags>']['detail'] = $this->searchResults->emitFieldDetail(__('Tags'),  $tags);
        $this->elementValue['<score>']['detail'] = $this->searchResults->emitFieldDetail(__('Score'),  $score);;
    }

    protected function userIsAdmin()
    {
        $user = current_user();

        if (empty($user))
            return false;

        if ($user->role == 'researcher')
            return false;

        return true;
    }

}