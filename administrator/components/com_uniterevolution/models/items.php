<?php
/**
 * @package Unite Revolution Slider for Joomla 1.7-2.5
 * @author UniteCMS.net
 * @copyright (C) 2012 Unite CMS, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
**/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.modellist');

class UniteRevolutionModelItems extends JModelList
{
	
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'a.id',
				'title', 'a.title',
				'alias', 'a.alias',
				'ordering', 'a.ordering',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'published', 'a.published',
				'access', 'a.access', 'access_level',
				'created', 'a.created',
				'created_by', 'a.created_by',
				'language', 'a.language'
			);
		}

		parent::__construct($config);
	}
	
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		$published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);				
		
		// List state information.
		parent::populateState('a.ordering', 'asc');
		$this->setState('list.limit', 0);
	}

	
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id	.= ':'.$this->getState('filter.published');
		
		return parent::getStoreId($id);
	}
	
	/**
	 * 
	 * get sliders array
	 */
	public function getArrSliders(){
		$arrSliders = HelperUniteRev::getArrSliders();
		return($arrSliders);
	}
	
	/**
	 * 
	 * get slider id
	 */
	public function getSliderID(){
		$sliderID = JRequest::getCmd("id");
		if(empty($sliderID))
			UniteFunctionsRev::throwError("Slider ID url argument not found (id)");
		return($sliderID);
	}
	
	protected function getListQuery()
	{
		
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.*'
			)
		);
		$query->from('#__uniterevolution_slides AS a');
		
		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
		
		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('a.published = ' . (int) $published);
		}
		else if ($published === '') {
			$query->where('(a.published = 0 OR a.published = 1)');
		}
		
		// Filter by category state
		$category = $this->getState('filter.category');
		if (is_numeric($category)) {
			$query->where('a.catid = ' . (int) $category);
		}
		
		//Filter by search in title.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('a.id = '.(int) substr($search, 3));
			}
			else {
				$search = $db->Quote('%'.$db->getEscaped($search, true).'%');
				$query->where('(a.title LIKE '.$search.' OR a.alias LIKE '.$search.')');
			}
		}
				
		$sliderID = $this->getSliderID();
				
		$query->where("a.sliderid='$sliderID'");
		
		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction',"asc");
		
		$orderBy = $orderCol.' '.$orderDirn;
		
		$query->order($orderBy);
				
		return $query;
	}
	
	
	/**
	 * get items rewrited, add slider title to slide properties
	 */
	public function getItems(){
		$items = parent::getItems();
		if(empty($items))
			return($items);
			
		$arrSlidersAssoc = HelperUniteRev::getArrSlidersAssoc();
		
		foreach ($items as $key=>$item){
			if(!isset($arrSlidersAssoc[$item->sliderid]))
				throw new Exception("Slider with id: {$item->sliderid} not found");
			
			$slider = $arrSlidersAssoc[$item->sliderid];			
			$items[$key]->slider_name = $slider["title"];
		}
		
		return($items);
	}
	
	
}
