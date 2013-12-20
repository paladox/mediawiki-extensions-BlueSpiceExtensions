<?php
/**
 * Base class for ExtendedSearch for MediaWiki
 *
 * Part of BlueSpice for MediaWiki
 *
 * @author     Mathias Scheer <scheer@hallowelt.biz>
 * @author     Markus Glaser <glaser@hallowelt.biz>
 * @author     Stephan Muggli <muggli@hallowelt.biz>
 * @package    BlueSpice_Extensions
 * @subpackage ExtendedSearch
 * @copyright  Copyright (C) 2010 Hallo Welt! - Medienwerkstatt GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */
/* Changelog
 * v0.1
 * FIRST CHANGES
 */
/**
 * Base class for ExtendedSearch for MediaWiki
 * @package BlueSpice_Extensions
 * @subpackage ExtendedSearch
 */
class ExtendedSearchBase {

	/**
	 * Instance of current search service.
	 * @var $oSearchService
	 */
	protected $oSearchService = null;
	/**
	 * Instance of ExtendedSearchBase
	 * @var Object
	 */
	protected static $oInstance = null;

	/**
	 * Constructor of ExtendedSearchBase class
	 */
	public function __construct() {
		wfProfileIn( 'BS::'.__METHOD__ );
		try {
			$this->oSearchService = SearchService::getInstance();
		} catch ( BsException $e ) {
			wfProfileOut( 'BS::'.__METHOD__ );
			return null;
		}
		wfProfileOut( 'BS::'.__METHOD__ );
	}

	/**
	 * Return a instance of ExtendedSearchBase.
	 * @return ExtendedSearchBase Instance of ExtendedSearchBase
	 */
	public static function getInstance() {
		wfProfileIn( 'BS::'.__METHOD__ );
		if ( self::$oInstance === null ) {
			self::$oInstance = new self();
		}

		wfProfileOut( 'BS::'.__METHOD__ );
		return self::$oInstance;
	}

	/**
	 * Checks if curl-extension is activated
	 * @return boolean
	 */
	public static function isCurlActivated() {
		return in_array( 'curl', get_loaded_extensions() );
	}

	/**
	 * Magic getter method
	 * @param string $sName Name of variable to get.
	 * @return mixed Value of the requested member variable.
	 */
	public function __get( $sName ) {
		return ( isset( $this->$sName ) ) ? $this->$sName : null;
	}

	/**
	 * Renders the inner content of search result page.
	 * @return ViewBaseElement View with inner content of search result page.
	 */
	public function renderSpecialpage() {
		// Form and results views are added via addItem to a ViewBaseElement
		$oView = new ViewBaseElement();
		$aMonitor = array();

		$oResultView = $this->search( $aMonitor );

		$vNoOfResultsFound = new ViewNoOfResultsFound();
		$vNoOfResultsFound->setOptions( $aMonitor );
		$oView->addItem( $vNoOfResultsFound );

		$oView->addItem( $oResultView );

		return $oView;
	}

	/**
	 * Triggers a search index update for a specified article.
	 * @param Article $oArticle MediaWiki article object of article to be indexed.
	 * @param string $sText Text to be indexed (optional, fetched from article if not present)
	 */
	public function updateIndexWiki( $oArticle ) {
		if ( $oArticle === null ) return;
		$oBuildIndexMainControl = BuildIndexMainControl::getInstance();
		$oBuildIndexMwArticles = new BuildIndexMwArticles( $oBuildIndexMainControl );

		$oTitle = $oArticle->getTitle();
		$oRevision = Revision::newFromTitle( $oTitle );

		wfRunHooks( 'BS::ExtendedSearch::UpdateIndexWiki', array( &$oTitle, &$oRevision ) );
		if ( $oTitle === null ) return;

		$iPageID = $oTitle->getArticleID();
		$iPageNamespace = $oTitle->getNamespace();
		$sPageTitle = $oTitle->getText();
		$iPageTimestamp = $oTitle->getTouched();
		$aPageCategories = $this->getCategoriesFromDbForCertainPageId( $iPageID );
		$aPageEditors = $this->getEditorsFromDbForCertainPageId( $iPageID );
		$bRedirect = $oTitle->isRedirect();

		$sPageContent = BsPageContentProvider::getInstance()->getContentFromRevision( $oRevision );

		if ( $bRedirect === true ) {
			$oRedirectTitle = ContentHandler::makeContent( $sPageContent, null, CONTENT_MODEL_WIKITEXT )->getUltimateRedirectTarget();
			if ( $oRedirectTitle instanceof Title ) {
				$oArticle = new Article( $oRedirectTitle );
				$this->updateIndexWiki( $oArticle );
			}
		}

		$aSections = $oBuildIndexMainControl->extractEditSections( $sPageContent );
		$sPageContent = $oBuildIndexMainControl->parseTextForIndex( $sPageContent, $oTitle );

		$aRedirects = $oBuildIndexMainControl->getRedirects( $oTitle );

		// http://www.mediawiki.org/wiki/Manual:WfTimestamp
		// wfTimestamp( TS_MW ) returns actual UTC in format YmdHis which results in gmdate( 'YmdHis', time() );
		// do not use date( 'YmdHis' ); it does not return GMT but timestamp with timezone-offset
		if ( strpos( $iPageTimestamp, '1970' ) === 0 ) $iPageTimestamp = wfTimestamp( TS_MW );

		$oSolrDocument = $oBuildIndexMwArticles->makeSingleDocument( $sPageTitle, $sPageContent, $iPageID, $iPageNamespace, $iPageTimestamp, $aPageCategories, $aPageEditors, $aRedirects, $bRedirect, $aSections );
		try {
			$this->oSearchService->addDocument( $oSolrDocument );
		} catch ( Exception $e ) {
			$oBuildIndexMainControl->logFile( 'write', __METHOD__ . ' - Error in _sendRawPost ' . $e->getMessage() );
		}

		try {
			// Indexing file links 
			$oBuildIndexMainControl->buildIndexLinked( '', $iPageID );
		} catch ( Exception $e ) {}

		$oBuildIndexMainControl->commitAndOptimize();
	}

	/**
	 * Triggers a search index update for a file.
	 * @param File $oFile file object.
	 */
	public function updateIndexFile( $oFile ) {
		$oBuildIndexMainControl = BuildIndexMainControl::getInstance();
		$oIndexFile = new BuildIndexMwSingleFile( $oBuildIndexMainControl, $oFile );
		try {
			$oIndexFile->indexCrawledDocuments();
		} catch ( Exception $e ) {
			$oBuildIndexMainControl->logFile( 'write', __METHOD__ . ' - Error in _sendRawPost ' . $e->getMessage() );
		}

		$oBuildIndexMainControl->commitAndOptimize();

		return true;
	}

	/**
	 * Triggers deletion of a specified file from search index.
	 * @param int $id Article id of page to be deleted.
	 * @param string $path path to the file.
	 */
	public function deleteIndexFile( $iID, $sPath ) {
		$oBuildIndexMainControl = BuildIndexMainControl::getInstance();
		$sUniqueID = $oBuildIndexMainControl->getUniqueId( $iID, $sPath );
		try {
			$this->oSearchService->deleteByQuery( 'uid:'.$sUniqueID );
		} catch ( Exception $e ) {
			$oBuildIndexMainControl->logFile( 'write', __METHOD__ . ' - Error in _sendRawPost ' . $e->getMessage() );
		}

		$oBuildIndexMainControl->commitAndOptimize();

		return true;
	}

	/**
	 * Triggers deletion of a specified item from search index.
	 * @param int $iID Article id of page to be deleted.
	 */
	public function deleteFromIndexWiki( $iID ) {
		$oBuildIndexMainControl = BuildIndexMainControl::getInstance();
		$sUniqueID = $oBuildIndexMainControl->getUniqueId( $iID );
		try {
			$this->oSearchService->deleteById( $sUniqueID );
		} catch ( Exception $e ) {
			$oBuildIndexMainControl->logFile( 'write', __METHOD__ . ' - Error in _sendRawPost ' . $e->getMessage() );
		}

		$oBuildIndexMainControl->commitAndOptimize();

		return true;
	}

	/**
	 * Triggers search index update for a given title.
	 * @param Title $title MediaWiki title object of article to be updated.
	 */
	public function updateIndexWikiByTitleObject( $oTitle ) {
		$oArticle = new Article( $oTitle );
		$this->updateIndexWiki( $oArticle );

		return true;
	}

	/**
	 * Reads out table %dbPrefix%categorylinks for certain page_id
	 * @param int $pageId ID of article that category links should be read for.
	 * @return array Categorynames as values
	 */
	public function getCategoriesFromDbForCertainPageId( $iPageID ) {
		$oDbr = wfGetDB( DB_SLAVE );

		// returns false on failure
		$oDbResTableCategories = $oDbr->select(
				'categorylinks',
				'DISTINCT cl_to',
				array( 'cl_from' => $iPageID )
		);

		$aCategories = array();
		if ( $oDbResTableCategories && $oDbr->numRows( $oDbResTableCategories ) > 0 ) {
			while ( $rowTableCategories = $oDbr->fetchObject( $oDbResTableCategories ) ) {
				$aCategories[] = $rowTableCategories->cl_to;
			}
		}
		$oDbr->freeResult( $oDbResTableCategories );

		return $aCategories;
	}

	/**
	 * Reads out table %dbPrefix%revision for certain page_id
	 * @param int $pageId ID of article that revisions should be read for.
	 * @return array editors as values
	 */
	public function getEditorsFromDbForCertainPageId( $iPageID ) {
		$oDbr = wfGetDB( DB_SLAVE );

		// returns false on failure
		$oDbResTableRevision = $oDbr->select(
				'revision',
				'DISTINCT rev_user_text',
				array( 'rev_page' => $iPageID )
		);

		$aEditors = array();
		if ( $oDbr->numRows( $oDbResTableRevision ) > 0 ) {
			$oUser = null;
			$sEditor = '';
			while ( $rowTableRevision = $oDbr->fetchObject( $oDbResTableRevision ) ) {
				$sEditor = $rowTableRevision->rev_user_text;
				$oUser = User::newFromName( $sEditor );
				if ( !is_object( $oUser ) ) $sEditor = 'unknown';
				$aEditors[] = $sEditor;
			}
		}
		$oDbr->freeResult( $oDbResTableRevision );

		return $aEditors;
	}

	/**
	 * Reads in searchstring and checks if a namespace is in it
	 * @param string $sSearchString given searchstring
	 * @param string $sSolrSearchString the solr searchstring
	 * @return int id of namespace
	 */
	public function checkSearchstringForNamespace( $sSearchString, &$sSolrSearchString ) {
		if ( empty( $sSearchString ) ) {
			return false;
		}

		if ( substr_count( $sSearchString, ':' ) === 0 ) {
			return false;
		}

		$aParts = explode( ':', $sSearchString );
		if ( count( $aParts ) !== 2 ) {
			return false;
		}

		if ( empty( $aParts[0] ) || empty( $aParts[1] ) ) {
			return false;
		}

		$iNamespace = BsNamespaceHelper::getNamespaceIndex( $aParts[0] );
		if ( empty( $iNamespace ) || !is_int( $iNamespace ) ) {
			return false;
		}

		// Check for special namespace
		if ( $iNamespace === NS_SPECIAL ) {
			$iNamespace = 1000;
		}

		$sSolrSearchString = $aParts[1];

		return $iNamespace;
	}

	/**
	 * Starts a search for a given search request.
	 * @param array $aMonitor Set of options.
	 * @return ViewBaseElement View for search results.
	 */
	public function search( &$aMonitor ) {
		try {
			$vItem = SearchIndex::getInstance()->search( $this->oSearchService, $aMonitor );
		} catch ( BsException $e ) {
			if ( $e->getMessage() == 'redirect' ) throw $e;
			$vItem = new ViewBaseElement();
			$vItem->setTemplate( 'Error: {error}' );
			$vItem->addData( array( 'error' => $e->getMessage() ) );
		}

		return $vItem;
	}

	/**
	 * Starts a search for Autocomplete
	 * @param String $sSearchString The string to be searched for.
	 * @return String JSON of search results.
	 */
	public function searchAutocomplete( $sSearchString ) {
		if ( self::isCurlActivated() === false ) return '';
		$oSearchOptions = SearchOptions::getInstance();

		$sSearchString = urldecode( $sSearchString );
		$sSolrSearchString = SearchService::preprocessSearchInput( $sSearchString );

		$vNamespace = $this->checkSearchstringForNamespace( $sSearchString, $sSolrSearchString );

		$aQuery = $oSearchOptions->getSolrAutocompleteQuery();
		$aQuery['searchString'] = 'titleEdge:('.$sSolrSearchString.')';
		$aQuery['searchLimit'] = BsConfig::get( 'MW::ExtendedSearch::AcEntries' );

		if ( $vNamespace !== false ) {
			$i = 0;
			foreach ( $aQuery['searchOptions']['fq'] as $key ) {
				if ( preg_match( '#.*?namespace:.*?#', $key ) ) {
					break;
				}
				$i++;
			}
			$aQuery['searchOptions']['fq'][$i] = '{!tag=na}+namespace:(' . $vNamespace . ')';
		}

		try {
			$oHits = $this->oSearchService->search(
				$aQuery['searchString'],
				$aQuery['offset'],
				$aQuery['searchLimit'],
				$aQuery['searchOptions']
			);
		} catch ( Exception $e ) {
			return '';
		}

		$oDocuments = $oHits->response->docs;

		$bEscalateToFuzzy = ( $oHits->response->numFound == 0 ); // boolean!
		// Escalate to fuzzy
		if ( $bEscalateToFuzzy ) {
			$oSearchOptions->setOption( 'scope', 'title' );

			$aFuzzyQuery = $oSearchOptions->getSolrFuzzyQuery( $sSolrSearchString );
			$aFuzzyQuery['searchLimit'] = BsConfig::get( 'MW::ExtendedSearch::AcEntries' );
			$aFuzzyQuery['searchOptions']['facet'] = 'off';
			$aFuzzyQuery['searchOptions']['hl'] = 'off';

			try {
				$oHits = $this->oSearchService->search(
					$aFuzzyQuery['searchString'],
					$aFuzzyQuery['offset'],
					$aFuzzyQuery['searchLimit'],
					$aFuzzyQuery['searchOptions']
				);
			} catch ( Exception $e ) {
				return '';
			}

			$oDocuments = $oHits->response->docs;
		}

		$aResults = array();
		$iID      = 0;

		if ( !empty( $oDocuments ) ) {
			$oTitle = null;
			$iPosition = 0;
			$sPartOfTitle = '';
			$sModifiedSearchString = '';
			$sLabelText = '';
			$sEscapedPattern = '';
			$aSearchStringParts = array();

			foreach ( $oDocuments as $oDoc ) {
				if ( $oDoc->namespace != '999' ) {
					$iNamespace = ( $oDoc->namespace == '1000' ) ? NS_SPECIAL : $oDoc->namespace;
					$oTitle = Title::makeTitle( $iNamespace, $oDoc->title );
				} else {
					continue;
				}

				if ( !$oTitle->userCan( 'read' ) ) continue;

				$sLabelText = $this->highlightTitle( $oTitle, $sSearchString );

				// Adding namespace
				$sLabelText = BsNamespaceHelper::getNamespaceName( $oTitle->getNamespace() ) . ':' .$sLabelText;

				if ( $vNamespace == $oTitle->getNamespace() ) {
					$sNamespace = BsNamespaceHelper::getNamespaceName( $vNamespace );
					$sLabelText = str_replace( $sNamespace.':', '', $sLabelText );
				}

				$oItem = new stdClass();
				$oItem->id = ++$iID;
				$oItem->value = $oTitle->getPrefixedText();
				$oItem->label = $sLabelText;
				$oItem->type = $oDoc->type;
				$oItem->link = $oTitle->getFullURL();
				$oItem->attr = '';

				$aResults[] = $oItem;
			}
		}

		$iSearchfiles = ( BsConfig::get( 'MW::ExtendedSearch::SearchFiles' ) ) ? '1' : '0' ;

		$sShortAndEscaped = SearchService::sanitzeSearchString(
			BsStringHelper::shorten(
				$sSearchString,
				array(
					'max-length' => '60',
					'position' => 'middle',
					'ellipsis-characters' => '...'
				)
			)
		);

		$sLabel = wfMessage( 'bs-extendedsearch-searchfulltext' )->escaped() . '<br />';
		$sLabel .= '<b>' . $sShortAndEscaped . '</b>';

		$bTitleExists = $oSearchOptions->titleExists( $sSearchString );

		wfRunHooks( 'BSExtendedSearchAutocomplete', array( &$aResults, $sSearchString, &$iID, $bTitleExists ) );

		$sSearchString = SearchService::sanitzeSearchString( $sSearchString );

		$aLinkParams = array(
			'search_origin' => 'titlebar',
			'search_scope' => 'text',
			'search_input' => $sSearchString,
			'search_files' => $iSearchfiles,
			'autocomplete' => true
		);

		$oItem = new stdClass();
		$oItem->id = ++$iID;
		$oItem->value = $sSearchString;
		$oItem->label = $sLabel;
		$oItem->type = '';
		$oItem->link = SpecialPage::getTitleFor( 'SpecialExtendedSearch' )->getFullUrl( $aLinkParams );
		$oItem->attr = 'bs-extendedsearch-ac-noresults';

		$aResults[] = $oItem;

		return json_encode( $aResults );
	}

	/**
	 * Highlights title for a given search string
	 * @param string $sSearchString search string
	 * @param object $oTitle Title object which should be highlieghtes
	 * @return string highlighted title
	 */
	public function highlightTitle( $oTitle, $sSearchString ) {
		$sModifiedSearchString = str_replace( '/', ' ', $sSearchString );
		$sLabelText = BsStringHelper::shorten(
			$oTitle->getText(),
			array( 'max-length' => '54', 'position' => 'middle', 'ellipsis-characters' => '...' )
		);

		$iPosition = mb_stripos( $sLabelText, $sSearchString );

		if ( $iPosition !== false ) {
			$sPartOfTitle = mb_substr( $sLabelText, $iPosition, mb_strlen( $sSearchString ) );
			$sEscapedPattern = preg_quote( $sPartOfTitle, '#' );
			$sLabelText = preg_replace( '#'.$sEscapedPattern.'#i', '<b>'.$sPartOfTitle.'</b>', $sLabelText , 1 );
		} else {
			$aOccurrences = array();
			$aSearchStringParts = explode( ' ', $sModifiedSearchString );

			foreach ( $aSearchStringParts as $sPart ) {
				if ( empty( $sPart ) ) continue;

				$sModifiedPart = mb_strtolower( $sPart );
				if ( in_array( $sModifiedPart, $aOccurrences ) ) continue;

				$iPosition = mb_stripos( $sLabelText, $sPart );

				if ( $iPosition !== false ) {
					$sPartOfTitle = mb_substr( $sLabelText, $iPosition, mb_strlen( $sPart ) );
					$sEscapedPattern = preg_quote( $sPartOfTitle, '#' );
					$sLabelText = preg_replace( '#'.$sEscapedPattern.'#i', '['.$sPartOfTitle.']', $sLabelText, 1 );

					$aOccurrences[] = $sModifiedPart;
				}
			}

			$sLabelText = str_replace( array( '[', ']' ), array( '<b>', '</b>' ), $sLabelText );
		}

		return $sLabelText;
	}

	/**
	 * Creates MoreLinkThis View
	 * @param Title $oTitle Current title object
	 * @param string $sOrigin origin of request
	 * @return View $oViewMlt MoreLikeThis view
	 */
	public function getViewMoreLikeThis( $oTitle, $sOrigin ) {
		$oViewMlt = new ViewMoreLikeThis;
		if ( $oTitle->isSpecialPage() ) return $oViewMlt;

		if ( $sOrigin === 'widgetbar' ) {
			global $wgTitle;
			$oTitle = $wgTitle;
		}

		$oViewMlt->setOption( 'origin', $sOrigin );
		$aMltQuery = SearchOptions::getInstance()->getSolrMltQuery( $oTitle );
		try {
			$oResults = SearchService::getInstance()->mlt( $aMltQuery['searchString'], $aMltQuery['offset'], $aMltQuery['searchLimit'], $aMltQuery['searchOptions'] );
		} catch ( Exception $e ) {
			return $oViewMlt;
		}

		$aMlt = array();
		//$aMlt[] = implode( ', ', $oResults->interestingTerms );
		if ( !empty( $oResults->response->docs ) ) {
			foreach ( $oResults->response->docs as $oRes ) {
				if ( count( $aMlt )  === 5 ) break;

				if ( $oRes->namespace != 999 ) {
					$oMltTitle = Title::makeTitle( $oRes->namespace, $oRes->title );
				} else {
					$oMltTitle = Title::makeTitle( NS_FILE, $oRes->title );
				}

				if ( !$oMltTitle->userCan( 'read' ) ) continue;
				if ( $oMltTitle->getArticleID() == $oTitle->getArticleID() ) continue;

				$sHtml = $oMltTitle->getPrefixedText();
				if ( $sOrigin === 'widgetbar' ) {
					$sHtml = BsStringHelper::shorten( $oMltTitle->getPrefixedText(), array( 'max-length' => 18, 'position' => 'middle' ) );
				}
				$aMlt[] = BsLinkProvider::makeLink( $oMltTitle, $sHtml );
			}
		}

		if ( empty( $aMlt ) ) {
			$aMlt[] = wfMessage( 'bs-extendedsearch-no-mlt-found' )->plain();
		}
		$oViewMlt->setOption( 'mlt', $aMlt );

		return $oViewMlt;
	}

	/**
	 * Generates list of most searched terms
	 * @return string list of most searched terms
	 */
	public function recentSearchTerms( $iCount, $iTime ) {
		$oDbr = wfGetDB( DB_SLAVE );
		$iCount = intval( $iCount );
		$iTime = intval( $iTime );

		$aConditions = array();
		if ( $iTime !== 0 ) {
			$iTimeInSec = $iTime * 24 * 60 * 60;
			$iTimeStamp = wfTimestamp( TS_UNIX ) - $iTimeInSec;
			$iTimeStamp = wfTimestamp( TS_MW, $iTimeStamp );
			$aConditions = array( 'stats_ts >= '.$iTimeStamp );
		}

		$res = $oDbr->select(
				'bs_searchstats',
				'stats_term',
				$aConditions
		);

		$aResults = array();
		if ( $oDbr->numRows( $res ) > 0 ) {
			$aTerms = array();

			foreach ( $res as $row ) {
				$sTerm = str_replace( array( '*', '\\' ), '', $row->stats_term );
				if ( substr_count( $sTerm, '~' ) > 0 ) {
					$aTermParts = explode( '~', $sTerm );
					$sFuzzy = array_pop( $aTermParts );
					$sTerm = implode( '', $aTermParts );
				}

				$sTerm = mb_strtolower( $sTerm );
				if ( array_key_exists( $sTerm, $aTerms ) ) {
					$aTerms[$sTerm] = $aTerms[$sTerm] + 1;
				} else {
					$aTerms[$sTerm] = 1;
				}
			}

			arsort( $aTerms );
			$aResults[] = '<ol>';
			$i = 1;

			foreach ( $aTerms as $key => $value ) {
				if ( $i > $iCount ) break;
				$aResults[] = '<li>' . htmlspecialchars( $key, ENT_QUOTES, 'UTF-8' ) . ' (' . $value . ')</li>';
				$i++;
			}

			$aResults[] = '</ol>';
		}

		return implode( "\n", $aResults );
	}

	/**
	 * Writes a given search request to database log.
	 * @param string $term Search term
	 * @param int $iNumFound Number of hits
	 * @param string $scope What was the scope of the search?
	 * @param string $files Were files searched as well?
	 * @return bool always false.
	 */
	public function logSearch( $term, $iNumFound, $scope, $files ) {
		if ( !BsConfig::get( 'MW::ExtendedSearch::Logging' ) ) return false;
		global $wgDBtype;

		$oDbw = wfGetDB( DB_MASTER );

		$term = BsCore::sanitize( $term, '', BsPARAMTYPE::SQL_STRING );

		$user = ( BsConfig::get( 'MW::ExtendedSearch::LogUsers' ) )
			? RequestContext::getMain()->getUser()->getId()
			: '';

		$effectiveScope = ( $files ) ? $scope.'-files' : $scope;
		$data = array(
			'stats_term' => $term,
			'stats_ts' => wfTimestamp( TS_MW ),
			'stats_user' => $user,
			'stats_hits' => $iNumFound,
			'stats_scope' => $effectiveScope
		);

		$oDbw->insert( 'bs_searchstats', $data );

		return true;
	}

	/**
	 * Renders error message
	 * @param string $message I18N key of error message
	 * @return ViewBaseElement Renders error message.
	 */
	public function createErrorMessageView( $sMessage ) {
		$res = new ViewBaseElement();
		$res->setTemplate( '<div id="bs-es-searchterm-error">' . wfMessage( 'bs-extendedsearch-error' )->plain() . ': {message}</div>' );
		$res->addData( array( 'message' => wfMessage( $sMessage )->plain() ) );
		return $res;
	}

}