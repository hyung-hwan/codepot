<?php
class IssueHelper
{
	var $TYPE_DEFECT       = 'defect';
	var $TYPE_REQUEST      = 'request';
	var $TYPE_OTHER        = 'other';

	// a newly created issue is set to 'new'.
	var $STATUS_NEW        = 'new';
	var $STATUS_OTHER      = 'other';  // other to default

	// the issue created is either accepted or rejected
	var $STATUS_ACCEPTED   = 'accepted';
	var $STATUS_REJECTED   = 'rejected'; 

	// one accepted, it is worked on and be resolved eventually.
	var $STATUS_STARTED    = 'started';
	// the work can be stalled for various reasons during progress
	var $STATUS_STALLED    = 'stalled'; 
	var $STATUS_RESOLVED   = 'resolved';

	var $PRIORITY_CRITICAL = 'critical';
	var $PRIORITY_HIGH     = 'high';
	var $PRIORITY_MEDIUM   = 'medium';
	var $PRIORITY_LOW      = 'low';
	var $PRIORITY_OTHER    = 'other';

	function IssueHelper ()
	{
	}

	function _get_type_array ($lang)
	{
		return array (
			$this->TYPE_DEFECT  => 
				$lang->line('ISSUE_TYPE_DEFECT'),
			$this->TYPE_REQUEST => 
				$lang->line('ISSUE_TYPE_REQUEST'),
			$this->TYPE_OTHER   => 
				$lang->line('ISSUE_TYPE_OTHER')
		);
	}

	function _get_status_array ($lang)
	{
		return array (
			$this->STATUS_NEW       => 
				$lang->line('ISSUE_STATUS_NEW'),
			$this->STATUS_OTHER     => 
				$lang->line('ISSUE_STATUS_OTHER'),
			$this->STATUS_ACCEPTED  => 
				$lang->line('ISSUE_STATUS_ACCEPTED'),
			$this->STATUS_REJECTED  => 
				$lang->line('ISSUE_STATUS_REJECTED'),
			$this->STATUS_STARTED => 
				$lang->line('ISSUE_STATUS_STARTED'),
			$this->STATUS_STALLED   => 
				$lang->line('ISSUE_STATUS_STALLED'),
			$this->STATUS_RESOLVED  => 
				$lang->line('ISSUE_STATUS_RESOLVED')
		);
	}

	function _get_open_status_array ($lang)
	{
		return array (
			$this->STATUS_NEW       => 
				$lang->line('ISSUE_STATUS_NEW'),
			$this->STATUS_ACCEPTED  => 
				$lang->line('ISSUE_STATUS_ACCEPTED'),
			$this->STATUS_STARTED => 
				$lang->line('ISSUE_STATUS_STARTED'),
			$this->STATUS_STALLED   => 
				$lang->line('ISSUE_STATUS_STALLED')
		);
	}

	function _get_priority_array ($lang)
	{
		return array (
			$this->PRIORITY_CRITICAL => 
				$lang->line('ISSUE_PRIORITY_CRITICAL'),
			$this->PRIORITY_HIGH     => 
				$lang->line('ISSUE_PRIORITY_HIGH'),
			$this->PRIORITY_MEDIUM   => 
				$lang->line('ISSUE_PRIORITY_MEDIUM'),
			$this->PRIORITY_LOW      => 
				$lang->line('ISSUE_PRIORITY_LOW'),
			$this->PRIORITY_OTHER    => 
				$lang->line('ISSUE_PRIORITY_OTHER')
		);

	}
}
?>
