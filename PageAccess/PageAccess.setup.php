<?php

BsExtensionManager::registerExtension('PageAccess', BsRUNLEVEL::FULL|BsRUNLEVEL::REMOTE, BsACTION::LOAD_SPECIALPAGE);

$wgAutoloadClasses['PageAccess'] = __DIR__ . '/PageAccess.class.php';

$wgMessagesDirs['PageAccess'] = __DIR__ . '/i18n';

$wgAutoloadClasses['SpecialPageAccess'] = __DIR__ . '/includes/specials/SpecialPageAccess.class.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles['PageAccessAlias'] = __DIR__ . '/includes/specials/SpecialPageAccess.alias.php'; # Location of an aliases file (Tell MediaWiki to load this file)
$wgSpecialPages['PageAccess'] = 'SpecialPageAccess'; # Tell MediaWiki about the new special page and its class name

$wgLogTypes[] = 'bs-pageaccess';
$wgFilterLogTypes['bs-pageaccess'] = true;
$wgLogActionsHandlers['bs-pageaccess/*'] = 'LogFormatter';