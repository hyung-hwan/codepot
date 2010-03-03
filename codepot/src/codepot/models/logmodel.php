<?php

class LogModel extends Model
{
	function LogModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function getNumEntries ($projectid = '')
	{
		$this->db->trans_start ();

		//$this->db->where ('type', 'code');
		//$this->db->where ('action', 'commit');
		if ($projectid != '') $this->db->where ('projectid', $projectid);
		$num = $this->db->count_all ('log');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $num;
	}

	function getEntries ($offset, $limit, $projectid = '')
	{
		$this->db->trans_start ();

		//$this->db->where ('type', 'code');
		//$this->db->where ('action', 'commit');
		if ($projectid != '') $this->db->where ('projectid', $projectid);
		$this->db->order_by ('createdon', 'desc');
		$query = $this->db->get ('log', $limit, $offset);

		$result = $query->result ();
		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		$count = 0;
		$commits = array ();
		foreach ($result as $row)
		{

			/* $row->project must be equal to $repo */
			$commits[$count]['createdon'] = $row->createdon;
			$commits[$count]['type'] = $row->type;
			$commits[$count]['action'] = $row->action;
			$commits[$count]['projectid'] = $row->projectid;
			$commits[$count]['userid'] = $row->userid;

			if ($row->type == 'code')
			{
				list($type,$repo,$rev) = split('[,]', $row->message);
				$tmp['type'] = $type;
				$tmp['repo'] = $repo;
				$tmp['rev'] = $rev;

				$log = @svn_log (
					'file:///'.CODEPOT_SVNREPO_DIR."/{$repo}",
					$rev, $rev, 1,SVN_DISCOVER_CHANGED_PATHS);
				if ($log === FALSE || count($log) < 1)
				{
					$tmp['time'] = '';
					$tmp['author'] = '';
					$tmp['message'] = '';
				}
				else
				{
					$tmp['time'] = $log[0]['date'];
					$tmp['author'] = $log[0]['author'];
					$tmp['message'] = $log[0]['msg'];
				}
	
				$commits[$count]['message'] = $tmp;
			}
			else
			{
				$commits[$count]['message'] = $row->message;
			}

			$count++;
		}	
	
		return $commits;
	}

	function writeCodecommit ($type, $repo, $rev, $userid)
	{
		$log->type = 'code';
		$log->action = 'commit';
		$log->projectid = $repo;
		$log->userid = $userid;
		$log->message = "{$type},{$repo},{$rev}";
		$this->write ($log);
	}

	function write ($log)
	{
		$this->db->trans_begin ();

		$this->db->set ('type', $log->type);
		$this->db->set ('action', $log->action);
		$this->db->set ('projectid', $log->projectid);
		$this->db->set ('message', $log->message);
		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}
		else
		{
			$this->db->trans_commit ();
			return TRUE;
		}
	}

	function delete ($log)
	{
		$this->db->trans_begin ();

		$this->db->where ('id', $log->id);
		$this->db->delete ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}
		else
		{
			$this->db->trans_commit ();
			return TRUE;
		}
	}
}

?>
