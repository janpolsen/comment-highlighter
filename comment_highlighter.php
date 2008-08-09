<?php
/*
Plugin Name: Comment Highlighter
Plugin URI: http://code.google.com/p/comment-highlighter/
Description: Add a style class to specific comments (or all comments) based on the authors email, url or name or based on post ID, comment ID or pingbacks. If upgrading from v0.7 or earlier, then you MUST visit the settings page to be sure everything is installed correct.
Version: 0.13
Author: Jan Olsen
Author URI: http://kamajole.dk
*/
add_action ( 'admin_menu', 'ch_menu' ) ;
$ch_options = get_option( 'jpo_comment_highlighter_options' );
if ( ! function_exists( 'file_put_contents' ) ) {
    define( 'FILE_APPEND' , 1 );

    /**
     * Support for PHP4
     * Write a string to a file
     *
     * @param unknown_type $n Full file name to write to
     * @param unknown_type $d String to write
     * @param unknown_type $flag Use FILE_APPEND for appending
     * @return unknown
     */
    function file_put_contents( $n, $d, $flag = false ) {
        $mode = ( $flag == FILE_APPEND || strtoupper( $flag ) == 'FILE_APPEND' ) ? 'a' : 'w';
        $f = @fopen( $n , $mode );
        if ( $f === false ) {
            return 0;
        } else {
            if ( is_array( $d ) )
                $d = implode( $d );
            $bytes_written = fwrite( $f , $d );
            fclose( $f );
            return $bytes_written;
        }
    }
}
if ( $_GET [ 'debug' ] == $ch_options [ 'debug_key' ] ) {
    $logfile = dirname(__FILE__).'/'.basename(__FILE__, '.php').'.log';
    $loglevel = 0;

    /**
     * helper function to do the actual logging to the default log file IF we are in debug mode
     *
     * @param string $str string to log
     * @param int offset compared to previous offset
     */
    function logger( $str, $loglevel_offset = 0 ) {
        global $logfile, $loglevel;
        if ( $str ) {
            list( $usec , $sec ) = explode( " " , microtime() );
            if ( $loglevel_offset < 0 )
                $loglevel += $loglevel_offset;
            file_put_contents( $logfile , sprintf( "%s%03s | %s %s\n" , date( "Y-m-d H:i:s." ) , floor( $usec * 1000 ) , str_repeat( '  ' , $loglevel ) , $str ) , FILE_APPEND );
            if ( $loglevel_offset > 0 )
                $loglevel += $loglevel_offset;
        }
    }
    file_put_contents( $logfile , '' );
} else {

    /**
     * dummy function for logger() when debug mode is deactivated
     *
     * @param string $dummy1 void
     * @param int $dummy2 void
     * @return boolean false
     */
    function logger( $dummy1 = '', $dummy2 = 0 ) {
        return false;
    }
}


logger("plugin start", +1);
logger("\$ch_options = " . var_export( $ch_options , true ));

function ch_menu () {
    global $ch_options;
    add_options_page ( 'CH', 'Comment Highlighter', 9, __FILE__, 'ch_manage_options' ) ;
}

function CommentHighlight ( $link = 'class', $additional_options = array() ) {
    global $comment, $wpdb, $ch_options ;
    logger("CommentHighlight ( \$link = '{$link}', \$additional_options ) {", +1);
    logger("\$additional_options = " . var_export($additional_options, true));
    logger("\$comment = " . var_export($comment, true));
    $ret = array ( ) ;

    if ($link == 'link') {
        if (current_user_can ( 'manage_options' )) {
            logger("current_user_can manage_options");

            $_url = get_bloginfo ( 'wpurl' ) . "/wp-admin/options-general.php?page={$ch_options['install_path']}&amp;c={$comment->comment_ID}" ;

            logger("\$_url = {$_url}");
            echo " <a href='{$_url}'>(comment highlight)</a>" ;
        }
    } elseif ($link == 'class') {
        $evenodd = '$evenodd' . $comment->comment_post_ID ;
        global $$evenodd ;
        $$evenodd ++ ;

        $_sql = "SELECT *
                 FROM {$wpdb->postmeta}
                 WHERE (meta_key = '_jpo_comment_highlighter' AND post_id = {$comment->comment_post_ID})
                 OR meta_key = '_jpo_comment_highlighter_global';";
        logger("\$_sql = {$_sql}");
        $_meta = $wpdb->get_results ( $_sql, ARRAY_A ) ;
        logger("\$_meta = " . var_export($_meta, true));
        if (count ( $_meta )) {
            foreach ( $_meta as $row ) {
                $val = unserialize ( $row [ 'meta_value' ] ) ;
                foreach ( $val as $critkey => $critval ) {
                    switch ( $critkey) {
                        case 'email' :
                            if ($comment->comment_author_email == $critval) {
                                $ret [] = $val [ 'class' ] ;
                            }
                        break ;
                        case 'name' :
                            if ($comment->comment_author == $critval) {
                                $ret [] = $val [ 'class' ] ;
                            }
                        break ;
                        case 'url' :
                            if ($comment->comment_author_url == $critval) {
                                $ret [] = $val [ 'class' ] ;
                            }
                        break ;
                        case 'uid' :
                            if ($comment->comment_author_name == $critval) {
                                $ret [] = $val [ 'class' ] ;
                            }
                        break ;
                        case 'cid' :
                            if ($comment->comment_ID == $critval) {
                                $ret [] = $val [ 'class' ] ;
                            }
                        break ;
                        case 'even' :
                            $ret [] = ($$evenodd % 2 ? '' : $val [ 'class' ]) ;
                        break ;
                        case 'odd' :
                            $ret [] = ($$evenodd % 2 ? $val [ 'class' ] : '') ;
                        break ;
                        case 'pingback' :
                            if ($comment->comment_type == 'pingback') {
                                $ret [] = $val [ 'class' ] ;
                            }
                        break ;
                        default :
                    }
                }
            }
        }

        foreach ($ret AS $key => $val) {
            if (isset($additional_options['prefix'])) {
                $ret[$key] = $additional_options['prefix'].$val;
            }
            if (isset($additional_options['postfix'])) {
                $ret[$key] = $val.$additional_options['postfix'];
            }
        }
        echo implode ( ' ', $ret ) ;
    }
    logger("\$ret = " . var_export($ret, true));
    logger("}", -1);
}

function ch_manage_options () {
    global $wpdb, $VERSION, $ch_options ;
    logger("ch_manage_options () {", +1);

    if ($_POST) {
        if ($_POST [ 'submit' ]) {
            $_meta_key = '_jpo_comment_highlighter' . ($_POST [ 'global' ] ? '_global' : '') ;
            $_meta_id  = $_POST['meta_id'];
            $_post_id = ($_POST [ 'txt_pid' ] ? $_POST [ 'txt_pid' ] : 0) ;
            $_arr = array ( ) ;
            foreach ( $_POST as $key => $val ) {
                list ( $_pre, $_post ) = explode ( '_', $key ) ;
                if ($_pre == 'ch' && $val) {
                    $_arr [ $_post ] = addslashes ( $_POST [ "txt_{$_post}" ] ) ;
                }
            }
            if ($_meta_id) {
                $wpdb->query ( "UPDATE {$wpdb->postmeta} SET post_id = {$_post_id}, meta_key = '{$_meta_key}', meta_value = '" . serialize ( $_arr ) . "' WHERE meta_key LIKE '_jpo_comment_highlighter%' AND meta_id = {$_meta_id};" ) ;
            } else {
                $wpdb->query ( "INSERT {$wpdb->postmeta} SET post_id = {$_post_id}, meta_key = '{$_meta_key}', meta_value = '" . serialize ( $_arr ) . "' ;" );
//                $wpdb->query ( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES ({$_post_id}, '{$_meta_key}', '" . serialize ( $_arr ) . "');" ) ;
            }
        } elseif ($_POST['update_values']) {
            $ch_options['debug_key'] = $_POST['debug_key'];
            update_option( 'jpo_comment_highlighter_options' , $ch_options );
        }
        echo "<script type='text/javascript'>" ;
        echo "location.href='{$_POST['referer']}'" ;
        echo "</script>" ;
        exit;
    }

    if ($_GET [ 'delete' ]) {
        $wpdb->query ( "DELETE FROM {$wpdb->postmeta} WHERE meta_id = {$_GET['delete']};" ) ;
        echo "<script type='text/javascript'>" ;
        echo "location.href='{$_SERVER['SCRIPT_NAME']}?page={$_GET['page']}'" ;
        echo "</script>" ;
        exit;
    }

    $tofff = array(
        'email'    => 'email.png',
        'pingback' => 'arrow_refresh.png',
        'global'   => 'asterisk_yellow.png',
        'class'    => 'tag.png',
        'name'     => 'vcard.png',
        'even'     => 'shape_move_backwards.png',
        'odd'      => 'shape_move_forwards.png',
        'pid'      => 'tag_red.png',
        'cid'      => 'tag_blue.png',
        'uid'      => 'tag_green.png',
        'url'      => 'world.png',
        'delete'   => 'delete.png',
        'edit'     => 'pencil.png',
    );

    echo "<div class='wrap'>" ;

    echo "<h2>Comment Highlighter Options</h2>" ;

    echo "<form method='post' action='{$PHP_SELF}'>" ;
    if (isset($_GET['c']) || isset($_GET['edit'])) {
        if ($_GET [ 'c' ] == 'new') {
            $_pid   =
            $_cid   = '0' ;
            $_uid   =
            $_email =
            $_name  =
            $_url   = '' ;
        } elseif ($_GET['edit']) {
            $_sql = "SELECT * FROM {$wpdb->postmeta} WHERE LEFT(meta_key, 24) = '_jpo_comment_highlighter' AND meta_id = {$_GET['edit']};";
            logger("\$_sql = {$_sql}");
            $_meta = $wpdb->get_results ( $_sql , ARRAY_A ) ;
            $_global = (substr($_meta[0]['meta_key'], -6) == 'global' ? true : false);
            foreach(unserialize($_meta[0]['meta_value']) AS $var => $val) {
                $_tmp = "_{$var}";
                $$_tmp = $val;
            }
            echo "<input type='hidden' name='meta_id' value='{$_GET['edit']}' />";
        } else {
            $_meta  = $wpdb->get_row ( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = {$_GET['c']};" ) ;
            $_pid   = $_meta->comment_post_ID ;
            $_cid   = $_GET [ 'c' ] ;
            $_email = $_meta->comment_author_email ;
            $_email = $_meta->comment_author_name ;
            $_name  = $_meta->comment_author ;
            $_url   = $_meta->comment_author_url ;
        }
        echo "<input type='hidden' name='referer' value='{$_SERVER['HTTP_REFERER']}' />" ;
        echo "<table border='0' cellpadding='5' cellspacing='0'>" ;
        echo "<caption>Create a new comment highlight based on <i>(only selected criterias will be saved and you can select multiple criterias)</i>:</caption>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_pid' name='ch_pid' value='true' ".toChecked($_pid)."/></td>" ;
        echo "<td style='whitespace: nowrap;'><label for='ch_pid'>".fff($tofff['pid'])." Post ID:</label></td>" ;
        echo "<td><input type='text' name='txt_pid'   style='width: 250px;' value='{$_pid}' /></td>" ;
        echo "<td>&nbsp;</td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_cid' name='ch_cid' value='true' ".toChecked($_cid)."/></td>" ;
        echo "<td style='whitespace: nowrap;'><label for='ch_cid'>".fff($tofff['cid'])." Comment ID:</label></td>" ;
        echo "<td><input type='text' name='txt_cid'   style='width: 250px;' value='{$_cid}' /></td>" ;
        echo "<td>&nbsp;</td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo   "<td align='center'><input type='checkbox' id='ch_uid' name='ch_uid' value='true' ".toChecked($_uid)."/></td>" ;
        echo   "<td style='whitespace: nowrap;'><label for='ch_uid'>".fff($tofff['uid'])." User ID:</label></td>" ;
        echo   "<td><input type='text' name='txt_uid'   style='width: 250px;' value='{$_uid}' /></td>" ;
        echo   "<td>the user ID of a person (usually the WP login name)</td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_email' name='ch_email' value='1' ".toChecked($_email)."/></td>" ;
        echo "<td><label for='ch_email'>".fff($tofff['email'])." Email:</label></td>" ;
        echo "<td><input type='text' name='txt_email' style='width: 250px;' value='{$_email}' /></td>" ;
        echo "<td><i>the email of the person</i></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_name' name='ch_name' value='true' ".toChecked($_name)."/></td>" ;
        echo "<td><label for='ch_name'>".fff($tofff['name'])." Name:</label></td>" ;
        echo "<td><input type='text' name='txt_name'  style='width: 250px;' value='{$_name}' /></td>" ;
        echo "<td><i>the name of the person</i></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_url' name='ch_url' value='true' ".toChecked($_url)."/></td>" ;
        echo "<td><label for='ch_url'>".fff($tofff['url'])." URL:</label></td>" ;
        echo "<td><input type='text' name='txt_url'   style='width: 250px;' value='{$_url}' /></td>" ;
        echo "<td><i>the URL the person has entered</i></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_pingback' name='ch_pingback' value='true' ".toChecked($_pingback)."/></td>" ;
        echo "<td colspan='2'><label for='ch_pingback'>".fff($tofff['pingback'])." Pingback</label></td>" ;
        echo "<td><i>if a comment isn't really a comment but a pingback/trackback</i><input type='hidden' value='true' name='txt_pingback' id='txt_pingback' /></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_even' name='ch_even' value='true' ".toChecked($_even)."/></td>" ;
        echo "<td colspan='2'><label for='ch_even'>".fff($tofff['even'])." Even numbered comments</label></td>" ;
        echo "<td><i>every even numbered comment</i><input type='hidden' value='true' name='txt_even' id='txt_even' /></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_odd' name='ch_odd' value='true' ".toChecked($_odd)."/></td>" ;
        echo "<td colspan='2'><label for='ch_odd'>".fff($tofff['odd'])." Odd numbered comments</label></td>" ;
        echo "<td><i>every odd numbered comment</i><input type='hidden' value='true' name='txt_odd' id='txt_odd' /></td>" ;
        echo "</tr>" ;
        echo "<tr style='font-weight: bold;'>" ;
        echo "<td align='center'><input type='checkbox' id='global' name='global' value='true' " . ($_GET [ 'c' ] == 'new' || $_global ? "checked='checked'" : '') . " /></td>" ;
        echo "<td><label for='global'>".fff($tofff['global'])." Global</label></td>";
        echo "<td>&nbsp;</td>" ;
        echo "<td><i>this means that the post and comment ID will not be part of the criteria</i></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td><input type='hidden' name='ch_class' value='true' /></td>" ;
        echo "<td>".fff($tofff['class'])." Class(es):</td>" ;
        echo "<td><input type='text' name='txt_class' style='width: 250px;' value='{$_class}'/></td>" ;
        echo "<td><i>separate multiple classes with spaces</i></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td colspan='3' align='left'><input type='submit' name='submit' style='float: left; background-color: #ccffcc;'  value=' Add this comment highlight ' /></td>" ;
        echo "<td colspan='2' align='right'><input type='submit' name='cancel' style='float: right; background-color: #ffcccc;' value=' Cancel and return ' /></td>" ;
        echo "</tr>" ;
        echo "</table>" ;
        echo "<br/>" ;
        echo "<br/>" ;
        echo "</form>" ;
    } else {
        $_sql = "SELECT * FROM {$wpdb->postmeta} WHERE LEFT(meta_key, 24) = '_jpo_comment_highlighter';";
        logger("\$_sql = {$_sql}");
        $_meta = $wpdb->get_results ( $_sql , ARRAY_A ) ;
        logger("\$_meta = " . var_export($_meta, true));

        $debug_key = ($ch_options['debug_key'] ? $ch_options['debug_key'] : substr(md5(uniqid()),0, 8));
        echo "<form method='post' action='{$PHP_SELF}'>" ;

        echo "<table cellpadding='5' cellspacing='0'>" ;
        echo "<tr>";
        echo   "<td colspan='2' valign='top' style='width: 300px'>";
        echo     "<b>Secret debug key</b> (<a href='http://code.google.com/p/comment-highlighter/wiki/Help' title='Comment Highlighter Help' target='_blank'>?</a>)<br/>";
        echo     "<i style='color: #aaa;'>Used to enable debug mode by adding <tt>?debug=xxx</tt> or <tt>&amp;debug=xxx</tt> to the URL where <tt>xxx</tt> is the secret debug key</i>";
        echo   "</td>";
        echo   "<td colspan='2' valign='top'>";
        echo     "<input type='text' name='debug_key' value='{$debug_key}' style='width: 250px; font-family: monospace; font-size: 10px;' />";
        echo     "<input type='submit' style='background-color: #ccffcc;' value=' Update value ' name='update_values' id='update_values' />" ;

        echo   "</td>";
        echo "</tr>";
        if (count ( $_meta )) {
            echo "<tr>" ;
            echo   "<th align='left'>Post ID</th>" ;
            echo   "<th align='left'>Criteria(s)</th>" ;
            echo   "<th align='left'>Class(es) to use</th>" ;
            echo   "<td>&nbsp;</td>" ;
            echo "</tr>" ;

            foreach ( $_meta as $row ) {
                $pid = (substr ( $row [ 'meta_key' ], - 6 ) == 'global' ? fff($tofff['global']) .' Global' : fff($tofff['pid']).' '.$row [ 'post_id' ]) ;
                $val = unserialize ( $row [ 'meta_value' ] ) ;
                echo "<tr>" ;
                echo "<td valign='top'>{$pid}</td>" ;
                echo "<td valign='top'>" ;
                foreach ( $val as $critkey => $critval ) {
                    if ($critkey != 'class') {
                        echo fff($tofff[$critkey])." ".ucfirst($critkey)." = {$critval}<br/>";
                    }
                }
                echo "</td>" ;
                echo "<td valign='top'>".fff($tofff['class'])."{$val['class']}</td>" ;
                echo "<td valign='top'>";
                echo   fff($tofff['delete'])." <a href='{$_SERVER['REQUEST_URI']}&amp;delete={$row['meta_id']}' title='Delete this comment highlight'>Delete</a>";
                echo   str_repeat('&nbsp;', 5);
                echo   fff($tofff['edit'])." <a href='{$_SERVER['REQUEST_URI']}&amp;edit={$row['meta_id']}' title='Edit this comment highlight'>Edit</a>" ;
                echo "</td>" ;
                echo "</tr>" ;
            }
            echo "</table>" ;
            echo "</form>";
        } else {
            echo "</table>" ;
            echo "<p>You haven't got any comment highlights yet...</p>" ;
        }
        $_url = get_bloginfo ( 'wpurl' ) . "/wp-admin/options-general.php?page={$_GET['page']}&amp;c=new" ;
        echo "<input type='button' style='background-color: #ccffcc;' value=' Add a new comment highlight ' onclick=\"location.href = '{$_url}';\" />" ;

      $ch_options['install_path'] = $_GET['page'];
      update_option( 'jpo_comment_highlighter_options' , $ch_options );
    }
    echo "</div>" ;
    logger("}", -1);
}

function comment_highlighter () {
    global $wpdb, $comment_highlighter_cache ;
    logger("comment_highlighter () {", +1);

    if (! isset ( $comment_highlighter_cache )) {
        $comment_highlight = $wpdb->get_results ( "SELECT   post_id, meta_value
          FROM {$wpdb->postmeta} AS pm
          INNER JOIN {$wpdb->posts} AS p ON (pm.post_id = p.ID)
          WHERE meta_key = '_jpo_comment_highlighter'
          AND (post_status = 'static' OR post_status = 'publish');" ) ;
    } else {
//        return $comment_highlighter_cache ;
        $ret = $comment_highlighter_cache;
    }

    if (! $comment_highlight) {
//        $comment_highlighter_cache = false ;
//        return false ;
        $ret = false;
    } else {
        foreach ( $comment_highlight as $link ) {
            $comment_highlighter_cache [ $link->post_id ] = $link->meta_value ;
        }
        $ret = $comment_highlighter_cache;
    }

    logger("\$comment_highlighter_cache = " . var_export($comment_highlighter_cache, true));
    logger("}", -1);
    return $ret;
}

if (! function_exists('fff')) {
  function fff($name) {
    $name = basename($name,'.png');
    return "<img src='http://famfamfam.googlecode.com/svn/wiki/images/{$name}.png' alt='{$name}' style='width: 16px; vertical-align: middle;' />";
  }
}

function toChecked($in) {
    return ($in ? "checked='checked'" : '');
}
logger("plugin end", -1);
?>