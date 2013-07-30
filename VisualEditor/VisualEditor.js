/**
 * VisualEditor extension
 *
 * @author     Markus Glaser <glaser@hallowelt.biz>
 * @version    1.20.0
 * @version    $Id: VisualEditor.js 9477 2013-05-22 13:54:41Z pwirth $
 * @package    Bluespice_Extensions
 * @subpackage VisualEditor
 * @copyright  Copyright (C) 2011 Hallo Welt! - Medienwerkstatt GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

/*
Ideen:
- Links rot oder blau. Per Ajax nachfragen.
/*
 * Determine mode of editor (full or reduced)
 */
// merge(extends/overwrites) standard config with overwrite config 
// if a property of the first object is itself an object or array, it will be completely overwritten by a property with the same key in the second object.
var temp = {};
$.extend(true, temp, bsVisualEditorConfigStandard, bsVisualEditorConfigOverwrite);
bsVisualEditorConfigOverwrite = temp;

if( bsVisualEditorConfigOverwrite && (bsVisualEditorUseLimited || bsVisualEditorUseForceLimited) ) {
	tinymce.init(bsVisualEditorConfigOverwrite);
	bsVisualEditorGuiMode = 'bn';
} else {
	tinymce.init(bsVisualEditorConfigStandard);
	bsVisualEditorGuiMode = 'tm';
}
if(bsVisualEditorConfigOverwrite) {
	bsVisualEditorGuiSwitchable = true;
}
/**
 * Is the editor currently running?
 * @var bool True if the editor is running.
 */
var VisualEditorMode = false;

/**
 * Starts the editor, but only after toolbars are rendered.
 */
function startEditor() {
	if (document.getElementById('toolbar') && document.getElementById('hw-toolbar')) {
		toggleEditorMode('wpTextbox1');
	}
	else {
		setTimeout("startEditor()", 100);
	}
}

/**
 * Actually displays the editor and removes the toolbars.
 */
toggleEditorMode = function(sEditorID) {
	try {
		if(VisualEditorMode) {
			$(document).trigger('BSVisualEditorBeforeToggleEditor', ['MW']);
			tinymce.execCommand('mceRemoveControl', false, sEditorID);
			if(document.getElementById('toolbar')) {document.getElementById('toolbar').style.display = "block";}
			if(document.getElementById('hw-toolbar')) {document.getElementById('hw-toolbar').style.display = "block";}
			VisualEditorMode = false;
			$(document).trigger('BSVisualEditorToggleEditor', ['MW']);
		} else {
			$(document).trigger('BSVisualEditorBeforeToggleEditor', ['VisualEditor']);
			tinymce.execCommand('mceAddControl', false, sEditorID);
			if(document.getElementById('toolbar')) {document.getElementById('toolbar').style.display = "none";}
			if(document.getElementById('hw-toolbar')) {document.getElementById('hw-toolbar').style.display = "none";}
			VisualEditorMode = true;
			$(document).trigger('BSVisualEditorToggleEditor', ['VisualEditor']);
		}
	} catch(e) {
		//error handling
	}
}

/**
 * Switches betwenn full and reduced mode of the editor.
 */
toggleGuiMode = function() {
	if(bsVisualEditorGuiSwitchable) {
		tinymce.execCommand('mceRemoveControl', false, 'wpTextbox1');
		if(document.getElementById('toolbar')) {document.getElementById('toolbar').style.display = "block";}
		if(document.getElementById('hw-toolbar')) {document.getElementById('hw-toolbar').style.display = "block";}
		VisualEditorMode = false;
		if(bsVisualEditorGuiMode == 'bn') {
			tinymce.init(bsVisualEditorConfigStandard);
			bsVisualEditorGuiMode = 'tm';
		}
		else {
			tinymce.init(bsVisualEditorConfigOverwrite);
			bsVisualEditorGuiMode = 'bn';
		}
		tinymce.execCommand('mceAddControl', false, 'wpTextbox1');
		if(document.getElementById('toolbar')) {document.getElementById('toolbar').style.display = "none";}
		if(document.getElementById('hw-toolbar')) {document.getElementById('hw-toolbar').style.display = "none";}
		VisualEditorMode = true;
	}
	else {
		alert("no switch");
	}
}

BsVisualEditor = {
	//Init loadMask and loadMaskTask with minimal stub objects to avoid code 
	//breaking in case of IE8 runtime error
	loadMask: {
		show: function() {},
		hide: function() {}
	},

	loadMaskTask:{
		cancel: function() {},
		delay: function( time, callback ) {}
	},

	init: function() {
		if (bsVisualEditorUse == true) {
			setTimeout("startEditor()", 100);
		}
		
		//TODO: User TinyMCE LoadMask: http://www.tinymce.com/wiki.php/API3:method.tinymce.Editor.setProgressState
	},

	/**
	* Starts the editor, but only after toolbars are rendered.
	*/
	startEditor: function() {
		
	},
	
	/**
	* Actually displays the editor and removes the toolbars.
	*/
	toggleEditorMode: function(sEditorID) {
		
	},

	/**
	* Switches betwenn full and reduced mode of the editor.
	*/
	toggleGuiMode: function() {
		
	}
}

$(document).bind('BSVisualEditorBeforeArticleSave', function( event, plugin, ajaxParams, ajaxUrl ) {
	tinymce.getInstanceById('wpTextbox1').setProgressState( true );
});

$(document).bind('BSVisualEditorAfterArticleSave', function( event, plugin, success, response, opts ) {
	tinymce.getInstanceById('wpTextbox1').setProgressState( false );
});

$(document).bind('BSVisualEditorBeforeWikiToHtml', function( event, textObject ) {
	//HINT: http://www.tinymce.com/wiki.php/API3:method.tinymce.Editor.setProgressState
	tinymce.getInstanceById('wpTextbox1').setProgressState( true );
});

$(document).bind('BSVisualEditorAfterWikiToHtml', function( event, textObject ) {
	tinymce.getInstanceById('wpTextbox1').setProgressState( false );
});

$(document).bind('BSVisualEditorBeforeHtmlToWiki', function( event, textObject ) {
	tinymce.getInstanceById('wpTextbox1').setProgressState( true );
});

$(document).bind('BSVisualEditorAfterHtmlToWiki', function( event, textObject ) {
	tinymce.getInstanceById('wpTextbox1').setProgressState( false );
});

$(document).ready(function(){
	BsVisualEditor.init();
});