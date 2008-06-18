<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_div::loadTCA('tt_content');

// Add new columns to tt_content

$tempColumns = array(
	'tx_displaycontroller_provider' => array(		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_provider',		
		'config' => array (
			'type' => 'group',	
			'internal_type' => 'db',	
			'allowed' => '',	
			'size' => 2,	
			'minitems' => 1,
			'maxitems' => 2,
			'prepend_tname' => 1,
			'MM' => 'tx_displaycontroller_providers_mm',
		)
	),
	'tx_displaycontroller_consumer' => array(		
		'exclude' => 0,		
		'label' => 'LLL:EXT:displaycontroller/locallang_db.xml:tt_content.tx_displaycontroller_consumer',		
		'config' => array(
			'type' => 'group',	
			'internal_type' => 'db',	
			'allowed' => '',	
			'size' => 1,	
			'minitems' => 1,
			'maxitems' => 1,
			'prepend_tname' => 1,
			'MM' => 'tx_displaycontroller_consumers_mm',
		)
	),
);
t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, 1);

// Define showitem property for both plug-ins

$showItem = 'CType;;4;button,hidden,1-1-1, header;;3;;2-2-2,linkToTop;;;;3-3-3';
$showItem .= ', --div--;LLL:EXT:displaycontroller/locallang_db.xml:tabs.dataobjects, tx_displaycontroller_provider, tx_displaycontroller_consumer';
$showItem .= ', --div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime';

$TCA['tt_content']['types'][$_EXTKEY.'_pi1']['showitem'] = $showItem;
$TCA['tt_content']['types'][$_EXTKEY.'_pi2']['showitem'] = $showItem;
$TCA['tt_content']['ctrl']['typeicons'][$_EXTKEY.'_pi1'] = t3lib_extMgm::extRelPath($_EXTKEY).'ext_typeicon.gif';
$TCA['tt_content']['ctrl']['typeicons'][$_EXTKEY.'_pi2'] = t3lib_extMgm::extRelPath($_EXTKEY).'ext_typeicon.gif';

// Register plug-ins (pi1 is cached, pi2 is not cached)

t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi1', $_EXTKEY.'_pi1', t3lib_extMgm::extRelPath($_EXTKEY).'ext_typeicon.gif'), 'CType');
t3lib_extMgm::addPlugin(array('LLL:EXT:displaycontroller/locallang_db.xml:tt_content.CType_pi2', $_EXTKEY.'_pi2', t3lib_extMgm::extRelPath($_EXTKEY).'ext_typeicon.gif'), 'CType');
?>