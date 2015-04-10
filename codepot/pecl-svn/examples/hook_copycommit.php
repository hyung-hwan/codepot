<?php

/* 
  example copy on commit hook script
  usage:
  php ..path..to..this..file.. hook_copycommit.php "$REPOS" "$REV" "/target/location" >> /tmp/svnlog 

*/
dl('svn.so');

class Subversion_CopyCommit {
    var $repos;
    var $rev;
    var $target; // where the copy goes
    
    
    function start($args) {
        print_r($args);
        list( $cmd , $this->repos, $this->rev , $this->target ) = $args; 
        if (empty($this->target)) {
            echo "NO TARGET !";exit;
        }
        if ($this->repos{0} == '/') {
            $this->repos = 'file://'. $this->repos;
        }
        $this->rev = (int) $this->rev;
        $ar = svn_log($this->repos, $this->rev,  $this->rev-1, 0, SVN_DISCOVER_CHANGED_PATHS);
        
        
        //print_R($ar);
        foreach($ar[0]['paths'] as $action) {
            $this->processAction($action);
        }
    }
    
    function processAction($action) 
    {
        $this->debug("Action: {$action['action']} on {$action['path']}");
        switch($action['action']) {
            case 'M': // modified
            case 'A': // added.
               
               /* how to handle moves?? */
               
                // is it a file or directory?
                if ($this->isDir($action['path'])) {
                    if (!file_exists($this->target . $action['path'])) {
                        require_once 'System.php';
                        System::mkdir(array('-p',$this->target . $action['path']));
                    }
                    return;
                }
                
                $this->writeFile($this->target.$action['path'], 
                    svn_cat($this->repos . $action['path'],$this->rev))    ;
                return;
                
            case 'D': // deleted.
                if (file_exists($this->target . $action['path'])) {
                    require_once 'System.php';
                    System::rm($this->target . $action['path']);
                }
                return;
                
            case 'R': // replaced????
                return;
        }
    }
    var $dircache = array();
     
    function isDir($path) 
    {
        if (!isset($this->dircache[dirname($path)])) {
		echo "SVN:LS ".$this->repos.dirname($path) ."\n";
		$p = strlen(dirname($path)) > 1  ? dirname($path) : '';
            $this->dircache[dirname($path)]= svn_ls($this->repos.$p,$this->rev);
        }
        $ar= $this->dircache[dirname($path)];
        //print_r($ar);
        $match = basename($path);
        foreach($ar as $info) {
            if ($info['name'] != $match) {
                continue;
            }
            return $info['type'] == 'dir';
        }
        return false;
    }
    function writeFile($target,$data) 
    {
        if (!file_exists(dirname($target))) {
            require_once 'System.php';
            System::mkdir(array('-p', dirname($target)));
        }
        $fh = fopen($target,'w');
        fwrite($fh, $data);
        fclose($fh);
    }
    
    function debug($str) 
    {
        echo $str."\n";
    }
    
}
ini_set('memory_limit','64M');
$x = new Subversion_CopyCommit;
$x->start($_SERVER['argv']);
