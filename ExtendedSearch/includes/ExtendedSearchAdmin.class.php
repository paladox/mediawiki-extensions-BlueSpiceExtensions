<?php
/**
 * Admin section for ExtendedSearch
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
 * Base class for ExtendedSearch admin section
 * @package BlueSpice_Extensions
 * @subpackage ExtendedSearch
 */
class ExtendedSearchAdmin {

	/**
	 * Constructor of ExtendedSearchAdmin class
	 */
	public function __construct() {
		wfProfileIn( 'BS::'.__METHOD__ );

		BsCore::getInstance( 'MW' )->getAdapter()->addRemoteHandler( 'ExtendedSearchAdmin', $this, 'getProgressBar', 'editadmin' );

		wfProfileOut( 'BS::'.__METHOD__ );
	}

	/**
	 * Return progress information when update index is called.
	 * called with action=remote&mod=ExtendedSearchAdmin&rf=getProgressBar
	 * @param string $sOutput Ready rendered output
	 */
	public function getProgressBar( &$sOutput ) {
		// todo: add new mechanism
		$sParamMode = BsCore::getParam( 'mode', false, BsPARAM::GET | BsPARAMTYPE::STRING );
		switch ( $sParamMode ) {
			case 'createForm' :
				$sOutput = $this->getCreateForm();
				break;
			case 'create':
				$sOutput = $this->getCreateFeedback();
				break;
			case 'delete':
				$sOutput = $this->getDeleteFeedback();
				break;
			case 'deleteLock':
				$sOutput = $this->checkLockExistence( $sParamMode );
				break;
		}
	}

	/**
	 * Renders the HTML for the admin section within WikiAdmin
	 * @return string HTML output to be displayed
	 */
	public function getForm() {
		if ( wfReadOnly() ) {
			throw new ReadOnlyError;
		}

		$sForm = '';

		$oSearchService = null;
		try {
			// throws BsException if BsSearchService could not be instanciated AND ping to Server successful
			$oSearchService = SearchService::getInstance();
		} catch ( BsException $e ) {
			// todo: i18n
			$sForm .= '<font color=\'red\'>' . wfMessage( $e->getMessage() )->plain() . '</font><br /><br />';
		}
		if ( !ExtendedSearchBase::isCurlActivated() )
			$sForm .= '<font color=\'red\'>' . wfMessage( 'bs-extendedsearch-curl-not-active' )->plain() . '</font><br /><br />';

		$sScriptPath = BsConfig::get( 'MW::ScriptPath' );

		if ( $this->checkLockExistence() === false ) {
			$aSearchAdminButtons = array(
				'create' => array(
					'href'    => '#',
					'onclick' => 'BsCore.toggleMessage(\'' . $sScriptPath . '/index.php?action=remote&mod=ExtendedSearchAdmin&rf=getProgressBar&mode=createForm\', \'' . addslashes( wfMessage( 'bs-extendedsearch-create-index' )->plain() ) . '\', 400, 300);setTimeout(\'bsExtendedSearchStartCreate()\', 1000);',
					'label'   => wfMessage( 'bs-extendedsearch-create-index' )->plain(),
					'image'   => "$sScriptPath/extensions/BlueSpiceExtensions/ExtendedSearch/resources/images/bs-searchindex-rebuild.png"
				),
				'delete' => array(
					'href'    => '#',
					'onclick' => 'BsCore.toggleMessage(\'' . $sScriptPath . '/index.php?action=remote&mod=ExtendedSearchAdmin&rf=getProgressBar&mode=delete\', \'' . addslashes( wfMessage( 'bs-extendedsearch-delete-index' )->plain() ) . '\', 400, 300);',
					'label'   => wfMessage( 'bs-extendedsearch-delete-index' )->plain(),
					'image'   => "$sScriptPath/extensions/BlueSpiceExtensions/ExtendedSearch/resources/images/bs-searchindex-delete.png"
				),
				'overwrite' => array(
					'href'    => '#',
					'onclick' => 'BsCore.toggleMessage(\'' . $sScriptPath . '/index.php?action=remote&mod=ExtendedSearchAdmin&rf=getProgressBar&mode=createForm\', \'' . addslashes( wfMessage( 'bs-extendedsearch-overwrite-index' )->plain() ) . '\', 400, 300);setTimeout(\'bsExtendedSearchStartCreate()\', 1000);',
					'label'   => wfMessage( 'bs-extendedsearch-overwrite-index' )->plain(),
					'image'   => "$sScriptPath/extensions/BlueSpiceExtensions/ExtendedSearch/resources/images/bs-searchindex-optimization.png"
				)
			);
		} else {
			$aSearchAdminButtons = array(
				'deleteLock' => array(
					'href'    => '#',
					'onclick' => 'bsExtendedSearchConfirm( \'' . wfMessage( 'bs-extendedsearch-warning' )->plain() . '\', \'' . wfMessage( 'bs-extendedsearch-lockfiletext' )->plain() . '\')',
					'label'   => wfMessage( 'bs-extendedsearch-delete-lock' )->plain(),
					'image'   => "$sScriptPath/extensions/BlueSpiceExtensions/ExtendedSearch/resources/images/bs-searchindex-delete.png"
				)
			);
			$sForm .= '<h3><font color=\'red\'>' . wfMessage( 'bs-extendedsearch-indexinginprogress' )->plain() . '</font></h3><br />';
		}

		wfRunHooks( 'BSExtendedSearchAdminButtons', array( $this, &$aSearchAdminButtons ) );

		if ( ExtendedSearchBase::isCurlActivated() && !is_null( $oSearchService ) ) {
			foreach( $aSearchAdminButtons as $key => $params ) {
				$sForm .= '<div class="bs-admincontrolbtn">';
				$sForm .= '<a href="'.$params['href'].'"';
				if( $params['onclick'] ) $sForm .= ' onclick="'.$params['onclick'].'"';
				$sForm .= '>';
				$sForm .= '<img src="'.$params['image'].'" alt="'.$params['label'].'" title="'.$params['label'].'">';
				$sForm .= '<div class="bs-admin-label">';
				$sForm .= $params['label'];
				$sForm .= '</div>';
				$sForm .= '</a>';
				$sForm .= '</div>';
			}
		}

		return $sForm;
	}

	/**
	 * Checks if lock file exists
	 * @param String $sMode 
	 * @return bool existence
	 */
	public function checkLockExistence( $sMode = '' ) {
		if ( file_exists( BSDATADIR.DS.'ExtendedSearch.lock' ) ) {
			if ( $sMode == 'deleteLock' ) {
				unlink( BSDATADIR.DS.'ExtendedSearch.lock' );
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Returns content of create index dialogue.
	 * @return string HTML to be rendered.
	 */
	public function getCreateForm() {
		return '<div id="hwstatus">
					<span id="BsExtendedSearchMode">'. wfMessage( 'bs-extendedsearch-status' )->plain().'</span>:
					<span id="BsExtendedSearchMessage">' . wfMessage( 'bs-extendedsearch-about_to_start' )->plain() . '</span>
				</div>
				<div id="BsExtendedSearchProgress">&nbsp;</div>';
	}

	/**
	 * Returns status information of create index progress.
	 * Error is indicated by return false or return null
	 * An ApacheAjaxResponse is expected
	 * If you return a string $s a new ApacheAjaxResponse($s) is created
	 * @return string Progress in percent or error message.
	 */
	public function getCreateFeedback() {
		// delete the old Index
		$this->getDeleteFeedback();
		// build the new Index
		$vRes = BuildIndexMainControl::getInstance()->buildIndex();
		/* Beware of returntype:
		 * Error is indicated by return false or return null
		 * An ApacheAjaxResponse is expected
		 * If you return a string $s a new ApacheAjaxResponse($s) is created
		 */

		return $vRes;
	}

	/**
	 * Returns status information of delete index progress.
	 * Error is indicated by return false or return null
	 * @return string information about the Progress or error message.
	 */
	public function getDeleteFeedback() {
		$sForm = '';
		$oSolr = SearchService::getInstance();
		if ( $oSolr === null ) return '';

		try {
			$iStatus = $oSolr->deleteIndex();
			if ( $iStatus == 200 ) {
				$iStatus = $oSolr->deleteIndex();
				$sForm .= wfMessage( 'bs-extendedsearch-index-successfully-deleted' )->plain() . '<br />';
			} else {
				$sForm .= wfMessage( 'bs-extendedsearch-index-error-deleting', $iStatus )->plain() . '<br />';
			}
		} catch ( Exception $e ) {
			$sForm .= wfMessage( 'bs-extendedsearch-no-success-deleting' )->plain() . '<br />';
			$sForm .= $e->getMessage();
		}

		return $sForm;
	}

}