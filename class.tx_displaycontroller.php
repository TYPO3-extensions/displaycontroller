<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter (Cobweb) <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
* $Id$
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('basecontroller', 'class.tx_basecontroller.php'));

/**
 * Plugin 'Display Controller (cached)' for the 'displaycontroller' extension.
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_displaycontroller
 */
class tx_displaycontroller extends tslib_pibase {
	public $prefixId	= 'tx_displaycontroller';		// Same as class name
	public $extKey		= 'displaycontroller';	// The extension key.
	protected $controller; // Contains a reference to a controller object
	protected static $consumer; // Contains a reference to the Data Consumer object
	protected $passStructure = true; // Set to false if Data Consumer should not receive the structure

	/**
	 * This method performs various initialisations
	 *
	 * @return	void
	 */
	protected function init($conf) {
		$this->conf = $conf;
		$this->controller = t3lib_div::makeInstance('tx_basecontroller');
	}

	/**
	 * The main method of the plugin
	 * This method uses a controller object to find the appropriate Data Provider
	 * The data structure from the Data Provider is then passed to the appropriate Data Consumer for rendering
	 *
	 * @param	string		$content: the plugin's content
	 * @param	array		$conf: the plugin's TS configuration
	 * @return	string		The content to display on the website
	 */
	public function main($content, $conf) {
//		$content = t3lib_div::view_array($conf);
//		$content .= t3lib_div::view_array($this->cObj->data);
		$this->init($conf);

		// Define the filter (if any)
		try {
			$filter = $this->defineFilter();
		}
		catch (Exception $e) {
			// TODO: Issue warning (error?) if a problem occurred with the filter
		}

		// Get the list of referenced data providers
		$availableProviders = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_displaycontroller_providers_mm', "uid_local = '".$this->cObj->data['uid']."'", '', 'sorting ASC');
		if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$availableProviders[] = $row;
			}

			// Get the actual data provider, if necessary
			try {
				if ($this->passStructure) {
					$provider = $this->controller->getDataProvider($availableProviders);
					$provider->setDataFilter($filter);
				}
	
				// Get the data consumer
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_displaycontroller_consumers_mm', "uid_local = '".$this->cObj->data['uid']."'");
				if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
					$availableConsumer = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					try {
						self::$consumer = $this->controller->getDataConsumer($availableConsumer);
							// Pass reference to current object and appropriate TypoScript to consumer
						self::$consumer->setParentReference($this);
						self::$consumer->setTypoScript($GLOBALS['TSFE']->tmpl->setup['plugin.'][self::$consumer->tsKey.'.']);
						self::$consumer->setDataFilter($filter);
							// If the structure shoud be passed to the consumer, do it now and get the rendered content
						if ($this->passStructure) {
								// Check if provided data structure is compatible with Data Consumer
							if (self::$consumer->acceptsDataStructure($provider->getProvidedDataStructure())) {
									// Pass the data structure, a reference to the controller and load the TypoScript
								self::$consumer->setDataStructure($provider->getDataStructure());
									// Start the processing and get the rendered data
								self::$consumer->startProcess();
								$content = self::$consumer->getResult();
							}
							else {
								// TODO: Issue error if data structures are not compatible between provider and consumer
							}
						}
							// If no structure should be passed (see defineFilter()),
							// don't pass structure :-), but still do the rendering
							// (this gives the opportunity to the consumer to render it's own error content, for example)
							// This is achieved by not calling startProcess(), but just getResult()
						else {
							$content = self::$consumer->getResult();
						}
					}
					catch (Exception $e) {
						echo $e->getMessage();
					}
				}
				else {
					// TODO: An error occurred querying the database
				}
			}
			catch (Exception $e) {
				echo $e->getMessage();
			}
		}
		else {
			// TODO: An error occurred querying the database
		}
		return $content;
	}

	/**
	 * This method defines the Data Filter to use depending on the values stored in the database record
	 * It returns the Data Filter structure
	 *
	 * @return	array	Data Filter structure
	 */
	protected function defineFilter() {
		$filter = array();
		if (!empty($this->cObj->data['tx_displaycontroller_filtertype'])) {
			switch ($this->cObj->data['tx_displaycontroller_filtertype']) {
				case 'single':
					$filter['filters'] = array(
											'0' => array(
												'table' => $this->piVars['table'],
												'field' => 'uid',
												'conditions' => array(
													0 => array(
														'operator' => '=',
														'value' => $this->piVars['showUid'],
													)
												)
											)
										);
					break;
				case 'list':
					$filter['limit'] = array(
											'max' => $this->piVars['limit'],
											'offset' => $this->piVars['page']
										);
					break;
				case 'filter':
					// Get the data filter
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_displaycontroller_filters_mm', "uid_local = '".$this->cObj->data['uid']."'");
					if ($res && $availableFilter = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						$datafilter = $this->controller->getDataFilter($availableFilter);
							// Load plug-in's variables into the filter
						$datafilter->setVars($this->piVars);
						try {
							$filter = $datafilter->getFilter();
								// Here handle case where the "filters" part of the filter is empty
								// If the display nothing flag has been set, we must somehow stop the process
								// The Data Provider should not even be called at all
								// and the Data Consumer should receive an empty (special?) structure
								if (count($filter['filters']) == 0 && empty($this->cObj->data['tx_displaycontroller_emptyfilter'])) {
									$this->passStructure = false;
								}
						}
						catch (Exception $e) {
							echo 'Error getting filter: '.$e->getMessage();
						}
					}
					else {
						throw new Exception('No data filter found');
					}
					break;
			}
		}
		return $filter;
	}

	/**
	 * This method can be called instead of main() for rendering nested elements of a data structure
	 * It avoids the full initialisation by refering to the consumer stored in a static variable
	 *
	 * @param	string		$content: the plugin's content
	 * @param	array		$conf: limited TS configuration for the rendering of the nested element
	 * @return	string		The content to display on the website
	 */
	public function sub($content, $conf) {
		self::$consumer->setTypoScript($conf);
		$content = self::$consumer->getSubResult();
		return $content;
	}
}


   
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/displaycontroller/class.tx_displaycontroller.php']);
}

?>