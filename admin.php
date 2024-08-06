<?php
 
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_userhistory extends DokuWiki_Admin_Plugin {

	function admin_plugin_userhistory ( ) {
        $this->setupLocale ( );
	}
 
    /**
     * return sort order for position in admin menu
     */
    function getMenuSort ( ) {
      return 999;
    }

    /**
     * handle user request
     */
    function handle ( ) {
    }
 
    /**
     * output appropriate html
     */
	function _userList ( ) {
		global $auth;
		global $ID;
		
        $user_list = $auth->retrieveUsers ( );
		
        echo ( '<h2 id="'.str_replace ( array ( " ", "'" ), "_", strtolower ( $this->getLang ( 'list' ) ) ).'">'.$this->getLang ( 'list' ).'</h2>' );
	
		echo ( '<div class = "editor_list"><p class = "editor_counter">'.$this->getLang ( 'total' ).': '.count ( $user_list ).'</p><ol>' );
		foreach ( $user_list as $key => $value ) {
			$nick = $key;
			$name = $value['name'];
			$href = wl ( $ID ). ( strpos ( wl ( $ID ), '?' )?'&amp;':'?' ).'do=admin&amp;page='.$this->getPluginName ( ).'&amp;user='.hsc ( $nick );
			echo ( '<li><a href = "'.$href.'">'.$nick.' - '.$name.'</li>' );
		}
		echo ( '</ol></div>' );
	}	
	
	function _getChanges ( $user ) {
		global $conf;
		
		function globr ( $dir, $pattern ) {
			$files = glob ( $dir.'/'.$pattern );
			$subdirs = glob ( $dir.'/*', GLOB_ONLYDIR ); /* Rework by bugmenot2 */
			if ( !empty ( $subdirs ) ) {
				foreach ( $subdirs as $subdir ) {
					$subfiles = globr ( $subdir, $pattern );
					if ( !empty ( $subfiles ) && !empty ( $files ) ) {
						$files = array_merge ( $files, $subfiles );
					}
				}
			}
			return $files;
		}

		$changes = array ( );
		$alllist = globr ( $conf['metadir'], '*.changes' );
		$skip = array ( '_comments.changes', '_dokuwiki.changes' );
		
		for ( $i = 0; $i < count ( $alllist ); $i++ ) { /* for all files */
			$fullname = $alllist[$i];
			$filepart = basename ( $fullname );
			if ( in_array ( $filepart, $skip ) ) continue;
			
			$f = file ( $fullname );
			for ( $j = 0; $j < count ( $f ); $j++ ) { /* for all lines */
				$line = $f[$j];
				$change = dokuwiki\ChangeLog\ChangeLog::parseLogLine ( $line );
				if ( strtolower ( $change['user'] ) == strtolower ( $user ) ) $changes[] = $change;
			}
		}
	
		function cmp ( $a, $b ) {
			$time1 = $a['date'];
			$time2 = $b['date'];
			if ( $time1 == $time2 ) { return 0; }
			return ( $time1 < $time2 ? 1 : -1 );
		}
		
		uasort ( $changes, 'cmp' );
	
		return $changes;
	}
	
	function _userHistory ( $user ) {
		global $conf;
		global $ID;
		global $lang;
		
		$href = wl ( $ID ). ( strpos ( wl ( $ID ), '?' )?'&amp;':'?' ).'do=admin&amp;page='.$this->getPluginName ( );
		echo ( '<p><a href = "'.$href.'">['.$this->getLang('back').']</a></p>' );
		echo ( '<h2>'.$user.'</h2>' );
		$changes = array_values ( $this->_getChanges ( $user ) );
		echo ( '<div class = "edit_list"><p class = "edit_counter">'.$this->getLang ( 'total' ).': '.count ( $changes ).'</p><ol>' );

		foreach ( $changes as $key => $change ) {
			if ( $key == 1000 ) { /* long list limiter */
				break;
			};
			$date = strftime ( $conf['dformat'], $change['date'] );
			echo ( $change['type'] === 'e' ? '<li class = "minor">' : '<li>' );
			echo ( '<div class = "li"><span class="date">'.$date.'</span>' );
			$p = array ( );
			$p['src']    = DOKU_BASE.'lib/images/diff.png';
			$p['title']  = $lang['diff'];
			$p['alt']    = $lang['diff'];
			$att = buildAttributes ( $p );
			echo ( '<a class = "diff_link" href = "'.wl ( $change['id'], "do=diff&amp;rev=".$change['date'] ).'"><img '.$att.' /></a>' );
			$p['src']    = DOKU_BASE.'lib/images/history.png';
			$p['title']  = $lang['btn_revs'];
			$p['alt']    = $lang['btn_revs'];
			$att = buildAttributes ( $p );
			echo ( '<a class = "revisions_link" href = "'.wl ( $change['id'], "do=revisions" ).'"><img '.$att.' /></a> ' );
			echo ( $change['id'].' &ndash; '.html_wikilink ( ':'.$change['id'], $conf['useheading'] ? NULL : $change['id'] ) );
			if ( $change['sum'] != "" ) {
				echo ( ' &ndash; '.hsc ( $change['sum'] ) );
			};
			echo ( '</div></li>' );
		}
		echo ( '</ol></div>' );
	}

    function html ( ) {
		echo ( '<h1 id="'.str_replace ( array ( " ", "'" ), "_", strtolower ( $this->getLang ( 'menu' ) ) ).'">'.$this->getLang ( 'menu' ).'</h1>' );
		
		if ( isset ( $_REQUEST['user'] ) ) {
			$this->_userHistory ( $_REQUEST['user'] );	
		} else {
			$this->_userList ( );	
		}
	}
 
}
