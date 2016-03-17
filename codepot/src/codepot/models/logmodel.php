<?php

class LogModel extends Model
{
	function LogModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function getNumEntries ($projectid = '', $userid = '')
	{
		$this->db->trans_start ();

		//$this->db->where ('type', 'code');
		//$this->db->where ('action', 'commit');

		if (is_array($projectid))
			$this->db->where_in ('projectid', $projectid);
		else if ($projectid != '') 
			$this->db->where ('projectid', $projectid);
		//$num = $this->db->count_all ('log');

		if ($userid != '') $this->db->where ('userid', $userid);

		$this->db->select ('count(*) as count');
		$query = $this->db->get ('log');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result();
		
		$num = empty($result)? 0:
		       isset($result[0]->COUNT)? $result[0]->COUNT: $result[0]->count;

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $num;
	}

	function getEntries ($offset, $limit, $projectid = '', $userid = '')
	{
		$this->db->trans_start ();

		//$this->db->where ('type', 'code');
		//$this->db->where ('action', 'commit');
		if (is_array($projectid))
			$this->db->where_in ('projectid', $projectid);
		else if ($projectid != '') 
			$this->db->where ('projectid', $projectid);

		if ($userid != '') $this->db->where ('userid', $userid);
		$this->db->order_by ('createdon', 'desc');
		$query = $this->db->get ('log', $limit, $offset);

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		$result = $query->result ();

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

				if ($row->action == 'commit')
				{
					//list($type,$repo,$rev) = split('[,]', $row->message);
					list($type,$repo,$rev) = explode(',', $row->message);

					$tmp['type'] = $type;
					$tmp['repo'] = $repo;
					$tmp['rev'] = $rev;

					$log = @svn_log (
						'file:///'.CODEPOT_SVNREPO_DIR."/{$repo}",
						$rev, $rev, 1, SVN_DISCOVER_CHANGED_PATHS);
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
				}
				else
				{
					//list($type,$repo,$rev,$propname,$action) = split('[,]', $row->message);
					list($type,$repo,$rev,$propname,$action) = explode(',', $row->message);

					$tmp['type'] = $type;
					$tmp['repo'] = $repo;
					$tmp['rev'] = $rev;

					$tmp['propname'] = $propname;
					$tmp['action'] = $action;

					$tmp['time'] = $row->createdon;
					$tmp['author'] = $row->userid;
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

	function writeCodeRevpropChange ($type, $repo, $rev, $userid, $propname, $action)
	{
		$log->type = 'code';
		$log->action = 'revpropchange';
		$log->projectid = $repo;
		$log->userid = $userid;
		$log->message = "{$type},{$repo},{$rev},{$propname},{$action}";
		$this->write ($log);
	}

	function write ($log)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->set ('type', $log->type);
		$this->db->set ('action', $log->action);
		$this->db->set ('projectid', $log->projectid);
		$this->db->set ('message', $log->message);
		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('userid', $log->userid);
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
		$this->db->trans_begin (); // manual transaction. not using trans_start().

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

	function purge ()
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$now = time();
		$one_month_ago = $now - (24 * 60 * 60 * 30);
		$this->db->where ('createdon <=', codepot_unixtimetodbdate($one_month_ago));
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

	function countCodeCommitsPerProject ($userid, $hour_limit = 0, $count_limit = 0)
	{
		$this->db->trans_begin ();
		if (strlen($userid) > 0) $this->db->where ('userid', $userid);

		if ($hour_limit > 0)
		{
			//$this->db->where ("updatedon >= SYSDATE() - INTERVAL {$hour_limit} HOUR");
			$this->db->where ("createdon >= CURRENT_TIMESTAMP - INTERVAL '{$hour_limit}' HOUR");
		}
		
		$this->db->where ('type', 'code');
		$this->db->where ('action', 'commit');

		if ($count_limit > 0) $this->db->limit ($count_limit);

		$this->db->select ('projectid, COUNT(id) AS commit_count');
		$this->db->group_by ('projectid');
		if ($count_limit > 0)  $this->db->order_by ('commit_count', 'desc');
		$query = $this->db->get ('log');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit();

		return $query->result ();
	}

	function countCodeCommitsPerUser ($projectid, $hour_limit = 0, $count_limit = 0)
	{
		$this->db->trans_begin ();
		if (strlen($projectid) > 0) $this->db->where ('projectid', $projectid);

		if ($hour_limit > 0)
		{
			//$this->db->where ("updatedon >= SYSDATE() - INTERVAL {$hour_limit} HOUR");
			$this->db->where ("createdon >= CURRENT_TIMESTAMP - INTERVAL '{$hour_limit}' HOUR");
		}

		$this->db->where ('type', 'code');
		$this->db->where ('action', 'commit');

		if ($count_limit > 0) $this->db->limit ($count_limit);

		$this->db->select ('userid, COUNT(id) AS commit_count');
		$this->db->group_by ('userid');
		if ($count_limit > 0) $this->db->order_by ('commit_count', 'desc');
		$query = $this->db->get ('log');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit();

		return $query->result ();
	}
}

?>
