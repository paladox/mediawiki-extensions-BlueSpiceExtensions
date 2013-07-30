<?php
/**
 * PermissionManager extension for BlueSpice
 *
 * Provides information about an article for status bar.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice for MediaWiki
 * For further information visit http://www.blue-spice.org
 *
 * @author     Markus Glaser <glaser@hallowelt.biz>
 * @version    1.22.0
 * @version    $Id: PermissionManager.class.php 9932 2013-06-25 15:46:48Z mreymann $
 * @package    BlueSpice_Extensions
 * @subpackage PermissionManager
 * @copyright  Copyright (C) 2011 Hallo Welt! - Medienwerkstatt GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

// Last review MRG (01.07.11 12:51)

/**
 * Class for managing all usergroup permissions
 * @package BlueSpice_Extensions
 * @subpackage WikiAdmin
 */
class PermissionManager extends BsExtensionMW {

	/**
	 * Constructor of PermissionManager
	 */
	public function __construct() {
		wfProfileIn( 'BS::'.__METHOD__ );

		// Base settings
		$this->mExtensionFile = __FILE__;
		$this->mExtensionType = EXTTYPE::SPECIALPAGE;
		$this->mInfo = array(
			EXTINFO::NAME        => 'PermissionManager',
			EXTINFO::DESCRIPTION => 'Administration interface for editing user rights',
			EXTINFO::AUTHOR      => 'Sebastian Ulbricht',
			EXTINFO::VERSION     => '1.22.0 ($Rev: 9932 $)',
			EXTINFO::STATUS      => 'stable',
			EXTINFO::URL         => 'http://www.hallowelt.biz',
			EXTINFO::DEPS        => array('bluespice' => '1.22.0')
		);

		WikiAdmin::registerModule('PermissionManager', array(
			'image' => '/extensions/BlueSpiceExtensions/WikiAdmin/images/bs-btn_rechteverwaltung_v1.png',
			'level' => 'useradmin'
			)
		);

		$this->registerExtensionSchemaUpdate( 'bs_permission_templates', dirname(__FILE__).DS.'PermissionManager.sql' );

		$this->registerScriptFiles( BsConfig::get( 'MW::ScriptPath' ).'/extensions/BlueSpiceExtensions/PermissionManager/js', 'PermissionManager', false, true, false, 'MW::PermissionManagerShow' );
		$this->registerStyleSheet( BsConfig::get( 'MW::ScriptPath' ).'/extensions/BlueSpiceExtensions/PermissionManager/css/PermissionManager.css', true, 'MW::PermissionManagerShow' );

		BsCore::getInstance( 'MW' )->getAdapter()->addRemoteHandler('PermissionManager', $this, 'getIndexData', 'wikiadmin');
		BsCore::getInstance( 'MW' )->getAdapter()->addRemoteHandler('PermissionManager', $this, 'getPermissionArray', 'wikiadmin');
		BsCore::getInstance( 'MW' )->getAdapter()->addRemoteHandler('PermissionManager', $this, 'getTemplateData', 'wikiadmin');
		BsCore::getInstance( 'MW' )->getAdapter()->addRemoteHandler('PermissionManager', $this, 'setTemplateData', 'wikiadmin');
		BsCore::getInstance( 'MW' )->getAdapter()->addRemoteHandler('PermissionManager', $this, 'getData', 'wikiadmin');
		BsCore::getInstance( 'MW' )->getAdapter()->addRemoteHandler('PermissionManager', $this, 'setDataTemporary', 'wikiadmin');
		BsCore::getInstance( 'MW' )->getAdapter()->addRemoteHandler('PermissionManager', $this, 'setDataAbort', 'wikiadmin');
		BsCore::getInstance( 'MW' )->getAdapter()->addRemoteHandler('PermissionManager', $this, 'setData', 'wikiadmin');

		BsCore::registerClass('PermissionTemplates', dirname(__FILE__), 'PermissionTemplates.class.php');
		wfProfileOut( 'BS::'.__METHOD__ );
	}

	/**
	 * Provides the form content for the WikiAdmin special page.
	 * @return string the form content
	 */
	public function getForm() {
		BsExtensionManager::setContext('MW::PermissionManagerShow');
		$aTemplates = PermissionTemplates::getAll();
		$aDescriptions = array();
		foreach($aTemplates as $oTemplate) {
			$aDescriptions[$oTemplate->getName()] = $oTemplate->getDescription();
		}
		return '<script type="text/javascript">var bs_perm_mng_desc = '.json_encode($aDescriptions).'</script>
				<p><table>
				<caption>' . wfMessage( 'bs-permissionmanager-labelLegend' )->plain() . '</caption>
				<tr>
					<td class="clsPermissionAvailable">' . wfMessage( 'bs-permissionmanager-legendDescriptionAvailable' )->plain() . '</td>
				</tr>
				<tr>
					<td class="clsPermissionSet">' . wfMessage( 'bs-permissionmanager-legendDescriptionSet' )->plain() . '</td>
				</tr>
			</table></p>
			<div id="panelPermissionManager"></div>';
	}

	/**
	 * Calculate the data for the index combobox and put it to the ajax output.
	 * @param string $output tha ajax output (have to be JSON)
	 */
	public function getIndexData( &$output ) {
		$wgGroupPermissions = $this->mAdapter->get( 'GroupPermissions' );
		$wgContLang = $this->mAdapter->get( 'ContLang' );

		$sIdxType = BsCore::getParam( 'idxType', 'group', BsPARAM::REQUEST|BsPARAMTYPE::RAW );
		$aData = array();

		switch ( $sIdxType ) {
			case 'namespace':
				$aNamespaces = BsAdapterMW::getNamespacesForSelectOptions(
					array( NS_MEDIA, NS_SPECIAL )
				);
				foreach ( $aNamespaces as $iNs => $sName ) {
					$aData[] = array($sName, $iNs);
				}
				break;
			case 'permission':
				$aAvailablePermissions = $this->getAvailablePermissions();
				foreach ( $aAvailablePermissions as $sPermission ) {
					$aData[] = array( $sPermission, $sPermission );
				}
				break;
			case 'group':
			default:
				foreach ( $wgGroupPermissions as $sGroupname => $aPermissions ) {
					$aData[] = array($sGroupname, $sGroupname);
				}
				break;
		}

		$output = json_encode($aData);
	}

	/**
	 * Get the data for the PermissionManager store and put it to the ajax output.
	 * @param string $output the ajax output (have to be JSON)
	 */
	public function getData( &$output ) {
		$sIdxType = BsCore::getParam( 'idxType', 'group', BsPARAM::REQUEST|BsPARAMTYPE::RAW );
		$sIndex = BsCore::getParam( 'index', '*', BsPARAM::REQUEST|BsPARAMTYPE::RAW );
		$iStart = BsCore::getParam( 'start', 0, BsPARAM::REQUEST|BsPARAMTYPE::INT );
		$iLimit = BsCore::getParam( 'limit', 8, BsPARAM::REQUEST|BsPARAMTYPE::INT );

		switch( $sIdxType ) {
			case 'namespace':
				$output = json_encode( $this->getNamespaceData( intval( $sIndex ), $iStart, $iLimit ) );
				break;
			case 'permission':
				$output = json_encode( $this->getPermissionData( $sIndex, $iStart, $iLimit ) );
				break;
			case 'group':
			default:
				$output = json_encode( $this->getGroupData( $sIndex, $iStart, $iLimit ) );
				break;
		}
	}

	/**
	 * Save the permission settings temporary in the session.
	 * @param string $output the ajax output (have to be JSON)
	 */
	public function setDataTemporary( &$output ) {
		$wgGroupPermissions = $this->mAdapter->get( 'GroupPermissions' );
		$wgContLang = $this->mAdapter->get( 'ContLang' );
		$wgNamespacePermissionLockdown = $this->mAdapter->get( 'NamespacePermissionLockdown' );

		$oData = json_decode( BsCore::getParam( 'data', '{}', BsPARAMTYPE::RAW|BsPARAM::POST ) );

		if( !isset( $oData->group ) ) {
			$output = 'group';
			return;
		}
		$sGroup = $oData->group;
		$sNamespace = $oData->namespace;
		if( $sNamespace == wfMessage( 'bs-permissionmanager-LabelMainspace' )->plain() ) {
			$iNS = 0;
		}
		elseif ( is_numeric( $sNamespace ) ) {
			$iNS = $sNamespace;
		}
		else {
			$iNS = $wgContLang->getNSIndex( $sNamespace );
		}

		$bAddPermissions = $oData->checked;
		if ( $iNS === false ) {
			$output = 'ns';
			return;
		}

		if ( $oData->index == wfMessage( 'bs-permissionmanager-LabelTemplate' )->plain() ) {
			$aPermissions = PermissionTemplates::getPermissionsFromName( $oData->value );
		}
		else {
			$aPermissions = array( $oData->value );
		}

		if( !isset( $_SESSION['pmTemp'] ) ) {
			$aGroupPermissions = $wgGroupPermissions;
			$aLockdown = $wgNamespacePermissionLockdown;
		}
		else {
			$aGroupPermissions = $_SESSION['pmTemp']['aGroupPermissions'];
			$aLockdown = $_SESSION['pmTemp']['aLockdown'];
		}

		if( !$iNS ) {
			if($bAddPermissions) {
				$this->addPermissionsToMainspace($sGroup, $aPermissions, $aGroupPermissions, $aLockdown);
			}
			else {
				$this->removePermissionsFromMainspace($sGroup, $aPermissions, $aGroupPermissions, $aLockdown);
			}
		}
		else {
			if($bAddPermissions) {
				$this->addPermissionsToNamespace($iNS, $sGroup, $aPermissions, $aGroupPermissions, $aLockdown);
			}
			else {
				$this->removePermissionsFromNamespace($iNS, $sGroup, $aPermissions, $aGroupPermissions, $aLockdown);
			}
		}

		$_SESSION['pmTemp'] = array(
			'aGroupPermissions' => $aGroupPermissions,
			'aLockdown' => $aLockdown
		);
	}

	/**
	 * Delete all permission settings which are saved temporary.
	 * @param string $output the ajax output (have to be JSON)
	 */
	public function setDataAbort( &$output ) {
		if(isset($_SESSION['pmTemp'])) {
			unset($_SESSION['pmTemp']);
		}
	}

	/**
	 * Save the permission settings permanently in bluespice-core/config/pm-settings.php.
	 * @param string $output the ajax output (have to be JSON)
	 */
	public function setData( &$output ) {
		if ( wfReadOnly() ) {
			global $wgReadOnly;
			$output = json_encode( array(
				'success' => false,
				'msg' => wfMessage( 'bs-readonly', $wgReadOnly )->plain()
				) );
			return;
		}

		if(!isset($_SESSION['pmTemp'])) {
			$output = json_encode(array('success' => true));
			return;
		}
		else {
			$wgGroupPermissions = $_SESSION['pmTemp']['aGroupPermissions'];
			$wgNamespacePermissionLockdown = $_SESSION['pmTemp']['aLockdown'];
		}

		wfRunHooks( 'BsNamespacemanageOnSavePermission', array( &$wgNamespacePermissionLockdown, &$wgGroupPermissions ) );

		$sSaveContent = "<?php\n";
		foreach($wgGroupPermissions as $sGroup => $aPermissions) {
			foreach($aPermissions as $sPermission => $bValue) {
				$sSaveContent .= "\$wgGroupPermissions['{$sGroup}']['{$sPermission}'] = ".($bValue ? 'true' : 'false').";\n";
			}
		}

		if(is_array($wgNamespacePermissionLockdown)) {
			foreach($wgNamespacePermissionLockdown as $iNS => $aPermissions) {
				$isReadLockdown = false;
				foreach($aPermissions as $sPermission => $aGroups) {
					$sSaveContent .= "\$wgNamespacePermissionLockdown[$iNS]['$sPermission'] = array('".implode("','", $aGroups)."');\n";
					if( $sPermission == 'read' ) $isReadLockdown = true;
				}
				if( $isReadLockdown ) {
					$sSaveContent .= "\$wgNonincludableNamespaces[] = $iNS;\n";
				}
			}
		}

		$res = file_put_contents(BSROOTDIR.DS.'config'.DS.'pm-settings.php', $sSaveContent);
		if($res) {
			$output = json_encode(array('success' => true));
			if(isset($_SESSION['pmTemp'])) {
				unset($_SESSION['pmTemp']);
			}
		}
		else {
			$output = json_encode(array(
				'success' => false,
				// TODO SU (04.07.11 12:06): i18n
				'msg' => 'Not able to create or write "'.BSROOTDIR.DS.'config'.DS.'pm-settings.php".'
			));
		}
	}

	/**
	 * Add the given permissions to the main namespace.
	 * @param string $sGroup the usergroup
	 * @param array $aPermissions the permissions to set
	 * @param array $aGroupPermissions the array which holds the group permissions (normally $wgGroupPermissions)
	 * @param array $aLockdown the array which holds the lockdown permissions (normally $wgNamespacePermissionLockdown)
	 */
	protected function addPermissionsToMainspace($sGroup, $aPermissions, &$aGroupPermissions, &$aLockdown) {
		foreach($aPermissions as $sPermission) {
			$aGroupPermissions[$sGroup][$sPermission] = true;
		}
	}

	/**
	 * Remove the given permissions from the main namespace.
	 * @param string $sGroup the usergroup
	 * @param array $aPermissions the permissions to remove
	 * @param array $aGroupPermissions the array which holds the group permissions (normally $wgGroupPermissions)
	 * @param array $aLockdown the array which holds the lockdown permissions (normally $wgNamespacePermissionLockdown)
	 */
	protected function removePermissionsFromMainspace($sGroup, $aPermissions, &$aGroupPermissions, &$aLockdown) {
		foreach($aPermissions as $sPermission) {
			$aGroupPermissions[$sGroup][$sPermission] = false;
		}
		if(is_array($aLockdown)) {
			foreach($aLockdown as $iNS => $aPermissionMatrix) {
				foreach($aPermissions as $sPermission) {
					if(!isset($aLockdown[$iNS][$sPermission])) {
						continue;
					}

					$iMatch = array_search($sGroup, $aLockdown[$iNS][$sPermission]);
					if($iMatch === false) {
						continue;
					}

					while($iMatch !== false) {
						if(!$iMatch && count($aLockdown[$iNS][$sPermission]) == 1) {
							unset($aLockdown[$iNS][$sPermission]);
							$iMatch = false;
						}
						else {
							$aLockdown[$iNS][$sPermission] = array_splice($aLockdown[$iNS][$sPermission], $iMatch, 1);
							$iMatch = array_search($sGroup, $aLockdown[$iNS][$sPermission]);
						}
					}
				}
			}
		}
	}

	/**
	 * Add the given permissions to a given namespace.
	 * @param int $iNS the namespace id
	 * @param string $sGroup the usergroup
	 * @param array $aPermissions the permissions to set
	 * @param array $aGroupPermissions the array which holds the group permissions (normally $wgGroupPermissions)
	 * @param array $aLockdown the array which holds the lockdown permissions (normally $wgNamespacePermissionLockdown)
	 */
	protected function addPermissionsToNamespace($iNS, $sGroup, $aPermissions, &$aGroupPermissions, &$aLockdown) {
		foreach($aPermissions as $sPermission) {
			if(!isset($aGroupPermissions) || !isset($aGroupPermissions[$sGroup][$sPermission]) || !$aGroupPermissions[$sGroup][$sPermission]) {
				$aGroupPermissions[$sGroup][$sPermission] = true;
			}
			if(!isset($aLockdown[$iNS]) || !isset($aLockdown[$iNS][$sPermission]) || array_search($sGroup, $aLockdown[$iNS][$sPermission]) === false) {
				$aLockdown[$iNS][$sPermission][] = $sGroup;
			}
		}
	}

	/**
	 * Remove the given permissions from a given namespace.
	 * @param int $iNS the namespace id
	 * @param string $sGroup the usergroup
	 * @param array $aPermissions the permissions to remove
	 * @param array $aGroupPermissions the array which holds the group permissions (normally $wgGroupPermissions)
	 * @param array $aLockdown the array which holds the lockdown permissions (normally $wgNamespacePermissionLockdown)
	 */
	protected function removePermissionsFromNamespace($iNS, $sGroup, $aPermissions, &$aGroupPermissions, &$aLockdown) {
		$aAllGroups = array_keys($aGroupPermissions);

		foreach($aPermissions as $sPermission) {
			if(!isset($aLockdown[$iNS][$sPermission])) {
				foreach($aAllGroups as $group) {
					if(isset($aGroupPermissions[$group][$sPermission]) && $aGroupPermissions[$group][$sPermission]) {
						$aLockdown[$iNS][$sPermission][] = $group;
					}
				}
			}
			$iMatch = array_search($sGroup, $aLockdown[$iNS][$sPermission]);
			if($iMatch === false) {
				continue;
			}

			if($iMatch == 0 && count($aLockdown[$iNS][$sPermission]) == 1) {
				unset($aLockdown[$iNS][$sPermission]);
			}
			else {
				while($iMatch !== false) {
					if($iMatch == 0) {
						array_shift($aLockdown[$iNS][$sPermission]);
					}
					elseif($iMatch == (count($aLockdown[$iNS][$sPermission]) - 1)) {
						array_pop($aLockdown[$iNS][$sPermission]);
					}
					else {
						$aLockdown[$iNS][$sPermission] = array_splice($aLockdown[$iNS][$sPermission], $iMatch, 1);
					}
					$iMatch = array_search($sGroup, $aLockdown[$iNS][$sPermission]);
				}
			}
		}
	}

	/**
	 * Get all available Permissions and put them to ajax output
	 * @param string $output the ajax output (have to be JSON)
	 */
	public function getPermissionArray( &$output ) {
		$aAvailablePermissions = $this->getAvailablePermissions();
		$aData = array();
		foreach($aAvailablePermissions as $sPermission) {
			$aData[] = array($sPermission, false);
		}
		$output = json_encode($aData);
	}

	/**
	 * Get all available permission templates and put them to the ajax output.
	 * @param string $output the ajax output (have to be JSON)
	 */
	public function getTemplateData( &$output ) {
		$aPermissionTemplates = PermissionTemplates::getAll();
		$aData = array();
		foreach($aPermissionTemplates as $oTemplate) {
			$aData[] = array(
				'text' => $oTemplate->getName(),
				'id'   => $oTemplate->getId(),
				'leaf' => true,
				'pm'   => $oTemplate->getPermissions(),
				'desc' => $oTemplate->getDescription()
			);
		}
		usort( $aData, array( $this, 'sortTemplateData' ) );
		$output = json_encode($aData);
	}

	/**
	 * Save changes in an existing permission template or create a new one.
	 * @param string $output the ajax output (have to be JSON)
	 */
	public function setTemplateData( &$output ) {
		$aSaveMap = json_decode(BsCore::getParam('saveMap', array(), BsPARAMTYPE::RAW|BsPARAM::POST));
		$aPermissionTemplates = PermissionTemplates::getAll();
		$aIds = array();

		foreach($aSaveMap as $aNode) {
			if(strstr($aNode->id, 'xnode')) {
				PermissionTemplates::addTemplate($aNode->text, $aNode->pm, $aNode->desc);
			}
			else {
				PermissionTemplates::editTemplate($aNode->id, $aNode->text, $aNode->pm, $aNode->desc);
				$aIds[] = $aNode->id;
			}
		}
		foreach($aPermissionTemplates as $oTemplate) {
			if(array_search($oTemplate->getId(), $aIds) === false) {
				PermissionTemplates::removeTemplate($oTemplate->getId());
			}
		}

		$output = json_encode(array(
			'success' => true,
			'msg' => wfMessage( 'bs-permissionmanager-CreateTemplateSuccess' )->plain()
		));
	}

	/**
	 * A helper method to sort the template data.
	 * @param array $aDataSet1 a template dataset
	 * @param array $aDataSet2 a template dataset
	 * @return int returns the result of strnatcmp between $aDataSet1 and $aDataSet2
	 */
	protected function sortTemplateData($aDataSet1, $aDataSet2) {
		return strnatcmp($aDataSet1['text'], $aDataSet2['text']);
	}

	/**
	 * Calculate the data for the PermissionManager store in group view.
	 * @param string $sGroup the groupname
	 * @param int $iStart a start offset
	 * @param int $iLimit a amount limiter
	 * @return array returns an array which contains the total amount of datasets and the data for the given range.
	 */
	protected function getGroupData($sGroup, $iStart, $iLimit) {

		if(isset($_SESSION['pmTemp'])) {
			$wgGroupPermissions = $_SESSION['pmTemp']['aGroupPermissions'];
			$wgNamespacePermissionLockdown = $_SESSION['pmTemp']['aLockdown'];
		}
		else {
			global $wgGroupPermissions, $wgNamespacePermissionLockdown;
		}

		$aNamespaces = $this->getNamespaces($iStart, $iLimit);

		$aAvailablePermissions = $this->getAvailablePermissions();

		$iTotal = count(
			BsAdapterMW::getNamespacesForSelectOptions(
				array( NS_MEDIA, NS_SPECIAL )
			)
		);

		$aPermissionMatrix = $this->buildPermissionMatrix(
			$aNamespaces,
			array($sGroup => $wgGroupPermissions[$sGroup]),
			$aAvailablePermissions,
			$wgNamespacePermissionLockdown,
			true
		);

		return array(
			'total' => $iTotal,
			'rows'  => $aPermissionMatrix
		);
	}

	/**
	 * Calculate the namespaces for a given range.
	 * @param int $iStart a start offset
	 * @param int $iLimit a amount limiter
	 * @return array the data of all namespacen in the given range
	 */
	protected function getNamespaces($iStart, $iLimit) {
		$aNamespaces = BsAdapterMW::getNamespacesForSelectOptions(
			array( NS_MEDIA, NS_SPECIAL )
		);

		//This is a pretty ugly implementation for limit offset. Unfortunately
		//there is no such thing as array_splice_assoc and normal array_splice
		//does a reindexing and we loose the namespace ids...
		$iCount = 0;
		$aCroppedNSMPs = array();
		foreach($aNamespaces as $iIdx => $sName){
			$iCount++;
			if( $iCount < $iStart + 1 ) continue;
			if( $iCount >= $iStart + $iLimit +1 ) break;
			$aCroppedNSMPs[$iIdx] = $sName;
		}

		return $aCroppedNSMPs;
	}

	/*
	 * Build up the permission matrix for all given combinations of namespaces, groups and permissions.
	*/
	protected function buildPermissionMatrix($aNamespaces, $aGroups, $aAvailablePermissions, $aLockdownSettings, $bCalculateWithTemplates = false) {
		// setup the arrays to store the matrix entries
		$aTemplateMatrix   = array();
		$aPermissionMatrix = array();

		// setup the grouping labels
		$sTemplateLabel             = wfMessage( 'bs-permissionmanager-LabelTemplate' )->plain();
		$sPermissionLabel           = wfMessage( 'bs-permissionmanager-LabelPermission' )->plain();
		$sAdditionalPermissionLabel = wfMessage( 'bs-permissionmanager-LabelAdditionalPermissions' )->plain();

		// load all templates
		$aTemplates = $aTemplates = PermissionTemplates::getAll();

		// find the sort id for the additional permission row
		$iAdditionalPermissionsId = count($aTemplates);

		foreach($aNamespaces as $iNS => $sNamespace) {

			$aPermissionsEnabled = array(); // setup an array to store all permissions which are enabled

			if($iNS == 0) { // we're doing calculation on main namespace?
				foreach($aGroups as $sGroup => $aGroupPermissions) {
					$aPermissionsEnabled = array(); // setup an array to store all permissions which are enabled

					foreach($aAvailablePermissions as $iPermissionSortId => $sPermission) {
						$aPermissionMatrixEntry = $this->buildMatrixEntry(
							$iNS,
							$sNamespace,
							$sGroup,
							$sPermission,
							$iPermissionSortId,
							0,
							$sPermissionLabel,
							2
						);

						if(isset($aGroupPermissions[$sPermission]) && $aGroupPermissions[$sPermission]) {
							$aPermissionMatrixEntry['iPermissionSet'] = 1;
							/*
							 * Add the permission as enabled.
							 * It's set to false because the templates have to checkup each permission
							 * and when the permission is contained in a template, it have to set it to true.
							*/
							$aPermissionsEnabled[$sPermission] = false;
						}

						$aPermissionMatrix[] = $aPermissionMatrixEntry;
					}

					if($bCalculateWithTemplates) {
						foreach($aTemplates as $iTemplateId => $oTemplate) {
							$aTemplateMatrix[] = $this->buildMatrixEntry(
								$iNS,
								$sNamespace,
								$sGroup,
								$oTemplate->getName(),
								$iTemplateId,
								intval($oTemplate->checkPermissions($aPermissionsEnabled)),
								$sTemplateLabel,
								1
							);
						}
						$aTemplateMatrix[] = $this->buildMatrixEntry(
							$iNS,
							$sNamespace,
							$sGroup,
							$sAdditionalPermissionLabel,
							$iAdditionalPermissionsId,
							(array_search(false, $aPermissionsEnabled) !== false ? 1 : 0),
							$sTemplateLabel,
							1
						);
					}
				}
			}
			else {
				foreach($aGroups as $sGroup => $aGroupPermissions) {
					$aPermissionsEnabled = array(); // setup an array to store all permissions which are enabled

					foreach($aAvailablePermissions as $iPermissionSortId => $sPermission) {
						$aPermissionMatrixEntry = $this->buildMatrixEntry(
							$iNS,
							$sNamespace,
							$sGroup,
							$sPermission,
							$iPermissionSortId,
							0,
							$sPermissionLabel,
							2
						);

						if(isset($aGroupPermissions[$sPermission]) && $aGroupPermissions[$sPermission]) {
							if(isset($aLockdownSettings[$iNS]) && isset($aLockdownSettings[$iNS][$sPermission])) {
								if(array_search($sGroup, $aLockdownSettings[$iNS][$sPermission]) !== false) {
									$aPermissionMatrixEntry['iPermissionSet'] = 1;
									$aPermissionsEnabled[$sPermission] = false;
								}
							}
							else {
								$aPermissionMatrixEntry['iPermissionSet'] = 2;
								$aPermissionsEnabled[$sPermission] = false;
							}
						}

						$aPermissionMatrix[] = $aPermissionMatrixEntry;
					}


					if($bCalculateWithTemplates) {
						foreach($aTemplates as $iTemplateId => $oTemplate) {
							$aTemplateMatrix[] = $this->buildMatrixEntry(
								$iNS,
								$sNamespace,
								$sGroup,
								$oTemplate->getName(),
								$iTemplateId,
								intval($oTemplate->checkPermissions($aPermissionsEnabled)),
								$sTemplateLabel,
								1
							);
						}
						$aTemplateMatrix[] = $this->buildMatrixEntry(
							$iNS,
							$sNamespace,
							$sGroup,
							$sAdditionalPermissionLabel,
							$iAdditionalPermissionsId,
							intval(array_search(false, $aPermissionsEnabled) !== false),
							$sTemplateLabel,
							1
						);
					}
				}
			}
		}

		return array_merge($aTemplateMatrix, $aPermissionMatrix);
	}

	/**
	 * Build a array for use in the data matrix for the PermissionManager store.
	 */
	protected function buildMatrixEntry($iNS, $sNamespace, $sGroup, $sPermission, $iPermissionSortId, $iPermissionSet, $sGroupingLabel, $iGroupingId) {
		return array(
			'iNS' =>$iNS,
			'sNamespace'         => $sNamespace,
			'sGroup'             => $sGroup,
			'sPermission'        => $sPermission, //User::getRightDescription($sPermission),
			'iPermissionSortId'  => $iPermissionSortId,
			'iPermissionSet'     => $iPermissionSet,
			'sGroupingLabel'     => $sGroupingLabel,
			'iGroupingId'        => $iGroupingId
		);
	}

	/**
	 * Calculate the data for the PermissionManager store in namespace view.
	 * @param int $iNS the namespace id
	 * @param int $iStart a start offset
	 * @param int $iLimit a amount limiter
	 * @return array returns an array which contains the total amount of datasets and the data for the given range.
	 */
	protected function getNamespaceData($iNs, $iStart, $iLimit) {
		$wgContLang = $this->mAdapter->get('ContLang');
		$wgGroupPermissions = $this->mAdapter->get('GroupPermissions');
		$wgNamespacePermissionLockdown = $this->mAdapter->get('NamespacePermissionLockdown');

		$iTotal = count($wgGroupPermissions);

		if(isset($_SESSION['pmTemp'])) {
			$wgGroupPermissions = $_SESSION['pmTemp']['aGroupPermissions'];
			$wgNamespacePermissionLockdown = $_SESSION['pmTemp']['aLockdown'];
		}

		$aNamespaces = $wgContLang->getNamespaces();
		$aNamespace = array($iNs => $aNamespaces[$iNs]);

		$aAvailablePermissions = $this->getAvailablePermissions();

		$aGroups = array_splice($wgGroupPermissions, $iStart, $iLimit);

		$aPermissionMatrix = $this->buildPermissionMatrix(
			$aNamespace,
			$aGroups,
			$aAvailablePermissions,
			$wgNamespacePermissionLockdown,
			true
		);

		return array(
			'total' => $iTotal,
			'rows'  => $aPermissionMatrix
		);
	}

	/**
	 * Calculate the data for the PermissionManager store in permission view.
	 * @param string $sPermission the permission name
	 * @param int $iStart a start offset
	 * @param int $iLimit a amount limiter
	 * @return array returns an array which contains the total amount of datasets and the data for the given range.
	 */
	protected function getPermissionData($sPermission, $iStart, $iLimit) {
		$wgContLang = $this->mAdapter->get('ContLang');
		$wgGroupPermissions = $this->mAdapter->get('GroupPermissions');
		$wgNamespacePermissionLockdown = $this->mAdapter->get('NamespacePermissionLockdown');

		$iTotal = count($wgGroupPermissions);

		if(isset($_SESSION['pmTemp'])) {
			$wgGroupPermissions = $_SESSION['pmTemp']['aGroupPermissions'];
			$wgNamespacePermissionLockdown = $_SESSION['pmTemp']['aLockdown'];
		}

		$aNamespaces = BsAdapterMW::getNamespacesForSelectOptions(
			array( NS_MEDIA, NS_SPECIAL )
		);
		$aGroups = array_splice($wgGroupPermissions, $iStart, $iLimit);

		$aPermissionMatrix = $this->buildPermissionMatrix(
			$aNamespaces,
			$aGroups,
			array($sPermission),
			$wgNamespacePermissionLockdown,
			false
		);

		return array(
			'total' => $iTotal,
			'rows'  => $aPermissionMatrix
		);
	}

	/**
	 * Calculate all permissions which can be managed with the PermissionManager.
	 * @return array an array what contains the permission names
	 */
	protected function getAvailablePermissions() {
		$wgGroupPermissions = $this->mAdapter->get('GroupPermissions');

		$aAvailablePermissions = array();
		foreach($wgGroupPermissions as $sGroup => $aPermissions) {
			foreach($aPermissions as $sName => $bValue) {
				if (!in_array($sName, WikiAdmin::get('ExcludeRights'))) {
					$aAvailablePermissions[] = $sName;
				}
			}
		}
		natsort($aAvailablePermissions);
		return array_unique($aAvailablePermissions);
	}
}
