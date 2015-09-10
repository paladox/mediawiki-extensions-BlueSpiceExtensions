<?php
/**
 * Internationalisation file for PageTemplates
 *
 * Part of BlueSpice for MediaWiki
 *
 * @author     Stephan Muggli <muggli@hallowelt.biz>

 * @package    BlueSpice_Extensions
 * @subpackage PageTemplates
 * @copyright  Copyright (C) 2012 Hallo Welt! - Medienwerkstatt GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

$messages = array();

$messages['de'] = array(
	'bs-pagetemplates-extension-description'   => 'Es können vorgefertigte Seitenvorlagen angelegt werden, die wiederverwendet werden können.',
	'prefs-PageTemplates'                      => 'Seitenvorlagen',
	'bs-pagetemplates-similar-pages'           => 'Ähnliche Seiten',
	'bs-pagetemplates-empty-page'              => 'Leere Seite',
	'bs-pagetemplates-empty-page-desc'         => 'Beginne mit einer leeren Seite.',
	'bs-pagetemplates-suggest-page'            => 'Vorschlag',
	'bs-pagetemplates-suggest-page-desc'       => 'Eine Seite verschlagen.',
	'bs-pagetemplatesadmin-label'              => 'Seitenvorlagen',
	'bs-pagetemplates-create_new'              => 'Neue Vorlage eintragen',
	'bs-pagetemplates-not_allowed'             => 'Du bist nicht berechtigt, diese Aktion auszuf&uuml;hren.',
	'bs-pagetemplates-dlg_label'               => 'Beschriftung',
	'bs-pagetemplates-desc'                    => 'Beschreibung',
	'bs-pagetemplates-page'                    => 'Seite',
	'bs-pagetemplates-actions'                 => 'Aktionen',
	'bs-pagetemplates-edit'                    => 'Bearbeiten',
	'bs-pagetemplates-delete'                  => 'L&ouml;schen',
	'bs-pagetemplates-button_ok'               => 'OK',
	'bs-pagetemplates-button_cancel'           => 'Abbrechen',
	'bs-pagetemplates-desc_2long'              => 'Die Beschreibung ist zu lang. Gib maximal 255 Zeichen an!',
	'bs-pagetemplates-label_2long'             => 'Der Titel ist zu lang. Gib maximal 255 Zeichen an!',
	'bs-pagetemplates-label_empty'             => 'Bitte gib einen Titel ein.',
	'bs-pagetemplates-templatename_2long'      => 'Der Name der Vorlage ist zu lang. Gib maximal 255 Zeichen ein.',
	'bs-pagetemplates-templatename_empty'      => 'Bitte gib eine Vorlage an.',
	'bs-pagetemplates-no_id'                   => 'Bitte gib eine Seite an, die als Vorlage verwendet werden soll.',
	'bs-pagetemplates-no_old_id'               => 'Es ist ein Fehler aufgetreten, die ursprüngiche Seite wurde nicht übermittelt.',
	'bs-pagetemplates-no_label'                => 'Bitte gib eine Beschriftung ein.',
	'bs-pagetemplates-invalid_id_esc'          => 'Die Seite ist ung&uuml;ltig. Verwende keine Hochkommas oder Backslashes.',
	'bs-pagetemplates-invalid_desc_esc'        => 'Die Beschreibung ist ung&uuml;ltig. Verwende keine Hochkommas oder Backslashes.',
	'bs-pagetemplates-invalid_label_esc'       => 'Die Beschriftung ist ung&uuml;ltig. Verwende keine Hochkommas oder Backslashes.',
	'bs-pagetemplates-invalid_id_type'         => 'Die Seite ist ung&uuml;ltig. Verwende nur Zahlen.',
	'bs-pagetemplates-tpl_exists'              => 'Die Seite ist bereits als Template registriert.',
	'bs-pagetemplates-tpl_added'               => 'Das Template wurde eingetragen.',
	'bs-pagetemplates-invalid_url_spc'         => 'Die URL ist ung&uuml;ltig. Verwende keine Leerzeichen.',
	'bs-pagetemplates-invalid_pfx'             => 'Das Pr&auml;fix ist ung&uuml;ltig.',
	'bs-pagetemplates-invalid_url'             => 'Die URL ist ung&uuml;ltig.',
	'bs-pagetemplates-no_old_tpl'              => 'Das urspr&uuml;ngliche Template wurde nicht gefunden.',
	'bs-pagetemplates-tpl_edited'              => 'Die Änderungen wurden übernommen.',
	'bs-pagetemplates-db_error'                => 'Es ist ein Datenbankfehler aufgetreten.',
	'bs-pagetemplates-tpl_deleted'             => 'Das Template wurde gel&ouml;scht.',
	'bs-pagetemplates-del_question1'           => 'Willst Du das Template',
	'bs-pagetemplates-del_question2'           => 'wirklich l&ouml;schen?',
	'bs-pagetemplates-language'                => 'Sprache',
	'bs-pagetemplates-no-similar-pages'        => 'Es gibt bisher keine ähnlichen Seiten.',
	'bs-pagetemplates-general-section'         => 'Allgemein',
	'bs-pagetemplates-choose-template'         => 'Diese Seite existiert noch nicht. Du kannst hier einen neuen Artikel verfassen. '
													.'Falls Du nichts eingeben m&ouml;chtest, klicke auf den Zur&uuml;ck-Button des Browsers, '
													."um zu der letzten Seite zur&uuml;ckzukehren.\n\nDu kannst aus einer dieser Vorlagen ausw&auml;hlen:",
	'bs-pagetemplates-PageTemplates'           => 'Seitenvorlagen',
	'bs-pagetemplates-HideLinesAfterEmptyPage' => 'Leerzeilen zwischen den Abschnitten ausblenden',
	'bs-pagetemplates-ExcludeNs'               => 'In diesen Namensräumen keine Vorlagen anzeigen',
	'bs-pagetemplates-ForceNamespace'          => 'Ziel-Namensraum erzwingen',
	'bs-pagetemplates-HideIfNotInTargetNs'     => 'Vorlage verstecken, wenn die Seite nicht im Zielnamensraum angelegt wird',
	
	//Javascript
	'bs-pagetemplates-headerLabel'				=> 'Name',
	'bs-pagetemplates-headerDescription'		=> 'Beschreibung',
	'bs-pagetemplates-headerTargetNamespace'	=> 'Namensraum',
	'bs-pagetemplates-headerTemplate'			=> 'Vorlage',
	'bs-pagetemplates-headerActions'			=> 'Aktionen',
	'bs-pagetemplates-tipEditDetails'			=> 'Vorlage bearbeiten',
	'bs-pagetemplates-tipDeleteTemplate'		=> 'Vorlage löschen',
	'bs-pagetemplates-tipAddTemplate'			=> 'Vorlage hinzufügen',
	'bs-pagetemplates-btnOk'					=> 'Ok',
	'bs-pagetemplates-btnCancel'				=> 'Abbrechen',
	'bs-pagetemplates-titleError'				=> 'Fehler',
	'bs-pagetemplates-unknownError'				=> 'Ein unbekannter Fehler ist aufgetreten.',
	'bs-pagetemplates-titleAddTemplate'			=> 'Vorlage hinzufügen',
	'bs-pagetemplates-titleEditDetails'			=> 'Details bearbeiten',
	'bs-pagetemplates-labelLabel'				=> 'Name',
	'bs-pagetemplates-labelDescription'			=> 'Beschreibung',
	'bs-pagetemplates-labelTargetNamespace'		=> 'Namensraum',
	'bs-pagetemplates-labelTemplateNamespace'	=> 'Vorlagen Namensraum',
	'bs-pagetemplates-labelArticle'				=> 'Vorlage',
	'bs-pagetemplates-titleDeleteTemplate'		=> 'Vorlage löschen',
	'bs-pagetemplates-confirmDeleteTemplate'	=> 'Bist du sicher, dass du diese Vorlage löschen willst?',
	'bs-pagetemplates-showEntries'				=> 'Angezeigte Einträge {0} - {1} von {2}'
);

$messages['de-formal'] = array(
	'bs-pagetemplates-empty-page-desc'         => 'Beginnen Sie mit einer leeren Seite.',
	'bs-pagetemplates-not_allowed'             => 'Sie sind nicht berechtigt, diese Aktion auszuf&uuml;hren.',
	'bs-pagetemplates-desc_2long'              => 'Die Beschreibung ist zu lang. Geben Sie maximal 255 Zeichen an!',
	'bs-pagetemplates-label_2long'             => 'Der Titel ist zu lang. Geben Sie maximal 255 Zeichen an!',
	'bs-pagetemplates-label_empty'             => 'Bitte geben Sie einen Titel ein.',
	'bs-pagetemplates-templatename_2long'      => 'Der Name der Vorlage ist zu lang. Geben Sie maximal 255 Zeichen ein.',
	'bs-pagetemplates-templatename_empty'      => 'Bitte geben Sie eine Vorlage an.',
	'bs-pagetemplates-no_id'                   => 'Bitte geben Sie eine Seite an, die als Vorlage verwendet werden soll.',
	'bs-pagetemplates-no_label'                => 'Bitte geben Sie eine Beschriftung ein.',
	'bs-pagetemplates-invalid_id_esc'          => 'Die Seite ist ung&uuml;ltig. Verwenden Sie keine Hochkommas oder Backslashes.',
	'bs-pagetemplates-invalid_desc_esc'        => 'Die Beschreibung ist ung&uuml;ltig. Verwenden Sie keine Hochkommas oder Backslashes.',
	'bs-pagetemplates-invalid_label_esc'       => 'Die Beschriftung ist ung&uuml;ltig. Verwenden Sie keine Hochkommas oder Backslashes.',
	'bs-pagetemplates-invalid_id_type'         => 'Die Seite ist ung&uuml;ltig. Verwenden Sie nur Zahlen.',
	'bs-pagetemplates-invalid_url_spc'         => 'Die URL ist ung&uuml;ltig. Verwenden Sie keine Leerzeichen.',
	'bs-pagetemplates-del_question1'           => 'Wollen Sie das Template',
	'bs-pagetemplates-choose-template'         => 'Diese Seite existiert noch nicht. Sie k&ouml;nnen hier einen neuen Artikel verfassen. '
													.'Falls Sie nichts eingeben m&ouml;chten, klicken auf den Zur&uuml;ck-Button des Browsers, '
													."um zu der letzten Seite zur&uuml;ckzukehren.\n\nSie k&ouml;nnen aus einer dieser Vorlagen ausw&auml;hlen:",
	'bs-pagetemplates-confirmDeleteTemplate'	=> 'Sind Sie sicher, dass Sie diese Vorlage löschen wollen?',
);

$messages['en'] = array(
	'bs-pagetemplates-extension-description'   => 'Displays a list of templates marked as page templates.',
	'prefs-PageTemplates'                      => 'Page templates',
	'bs-pagetemplates-similar-pages'           => 'Similar pages',
	'bs-pagetemplates-empty-page'              => 'Empty page',
	'bs-pagetemplates-empty-page-desc'         => 'Start with an empty page.',
	'bs-pagetemplates-suggest-page'            => 'Suggestion',
	'bs-pagetemplates-suggest-page-desc'       => 'Suggest a new page.',
	'bs-pagetemplatesadmin-label'              => 'Page templates',
	'bs-pagetemplates-create_new'              => 'Create new page template',
	'bs-pagetemplates-not_allowed'             => 'You are not allowed to perform this action.',
	'bs-pagetemplates-dlg_label'               => 'Label',
	'bs-pagetemplates-desc'                    => 'Description',
	'bs-pagetemplates-page'                    => 'Page',
	'bs-pagetemplates-actions'                 => 'Actions',
	'bs-pagetemplates-edit'                    => 'Edit',
	'bs-pagetemplates-delete'                  => 'Delete',
	'bs-pagetemplates-button_ok'               => 'OK',
	'bs-pagetemplates-button_cancel'           => 'Cancel',
	'bs-pagetemplates-desc_2long'              => 'The description is too long. Please use a maximum of 255 characters!',
	'bs-pagetemplates-label_2long'             => 'The label is too long. Please use a maximum of 255 characters!',
	'bs-pagetemplates-label_empty'             => 'Please enter a label',
	'bs-pagetemplates-templatename_2long'      => 'The template name is too long. Please use a maximum of 255 characters.',
	'bs-pagetemplates-templatename_empty'      => 'Please enter a template name.',
	'bs-pagetemplates-no_id'                   => 'Please enter an article to be used as template.',
	'bs-pagetemplates-no_old_id'               => 'An error occurred. The original article could not be identified.',
	'bs-pagetemplates-no_label'                => 'Please enter a label.',
	'bs-pagetemplates-invalid_id_esc'          => 'The article name is invalid. Please do not use apostrophes or backslashes.',
	'bs-pagetemplates-invalid_desc_esc'        => 'The description is invalid. Please do not use apostrophes or backslashes.',
	'bs-pagetemplates-invalid_label_esc'       => 'The label is invalid. Please do not use apostrophes or backslashes.',
	'bs-pagetemplates-invalid_id_type'         => 'The article id is invalid. Please do use only numbers.',
	'bs-pagetemplates-tpl_exists'              => 'The article is already registered as page template.',
	'bs-pagetemplates-tpl_added'               => 'The template has been added.',
	'bs-pagetemplates-invalid_url_spc'         => 'The URL is invalid. Please do not use spaces.',
	'bs-pagetemplates-invalid_pfx'             => 'The prefix is invalid.',
	'bs-pagetemplates-invalid_url'             => 'The URL is invalid.',
	'bs-pagetemplates-no_old_tpl'              => 'The old template could not be found.',
	'bs-pagetemplates-tpl_edited'              => 'The changes have been saved.',
	'bs-pagetemplates-db_error'                => 'A database error occurred.',
	'bs-pagetemplates-tpl_deleted'             => 'The template has been deleted.',
	'bs-pagetemplates-del_question1'           => 'Do you really want to delete the template',
	'bs-pagetemplates-del_question2'           => '?',
	'bs-pagetemplates-language'                => 'Language',
	'bs-pagetemplates-no-similar-pages'        => 'There are no similar pages yet.',
	'bs-pagetemplates-general-section'         => 'General',
	'bs-pagetemplates-choose-template'         => 'This page doesn\'t exist yet. You can create a new page. '
													.'If you don\'t want to do that, click the back button of your browser to return to the last page visited. '
													."\n\nYou can choose from one of the following templates:",
	'bs-pagetemplates-PageTemplates'           => 'Page templates',
	'bs-pagetemplates-HideLinesAfterEmptyPage' => 'Hide empty lines between sections',
	'bs-pagetemplates-ExcludeNs'               => 'Do not show templates in these namespaces',
	'bs-pagetemplates-ForceNamespace'          => 'Force target namespace',
	'bs-pagetemplates-HideIfNotInTargetNs'     => 'Hide template if the article is not to be created in target namespace',
	
	//Javascript
	'bs-pagetemplates-headerLabel'				=> 'Label',
	'bs-pagetemplates-headerDescription'		=> 'Description',
	'bs-pagetemplates-headerTargetNamespace'	=> 'Namespace',
	'bs-pagetemplates-headerTemplate'			=> 'Template',
	'bs-pagetemplates-headerActions'			=> 'Actions',
	'bs-pagetemplates-tipEditDetails'			=> 'Edit template',
	'bs-pagetemplates-tipDeleteTemplate'		=> 'Delete template',
	'bs-pagetemplates-tipAddTemplate'			=> 'Add template',
	'bs-pagetemplates-btnOk'					=> 'Ok',
	'bs-pagetemplates-btnCancel'				=> 'Cancel',
	'bs-pagetemplates-titleError'				=> 'Error',
	'bs-pagetemplates-unknownError'				=> 'An unknown error occurred.',
	'bs-pagetemplates-titleAddTemplate'			=> 'Add template',
	'bs-pagetemplates-titleEditDetails'			=> 'Edit details',
	'bs-pagetemplates-labelLabel'				=> 'Template name',
	'bs-pagetemplates-labelDescription'			=> 'Description',
	'bs-pagetemplates-labelTargetNamespace'		=> 'Namespace',
	'bs-pagetemplates-labelTemplateNamespace'	=> 'Template namespace',
	'bs-pagetemplates-labelArticle'				=> 'Template',
	'bs-pagetemplates-titleDeleteTemplate'		=> 'Delete template',
	'bs-pagetemplates-confirmDeleteTemplate'	=> 'Are you sure, you want delete this template?',
	'bs-pagetemplates-showEntries'				=> 'Displaying {0} - {1} of {2}'
);

$messages['qqq'] = array();