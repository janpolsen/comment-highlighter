<?php
/*
Plugin Name: Comment Highlighter
Plugin URI: http://code.google.com/p/comment-highlighter/
Description: Add a style class to specific comments (or all comments) based on the authors email, url or name or based on post ID, comment ID or pingbacks. If upgrading from v0.7 or earlier, then you MUST visit the settings page to be sure everything is installed correct.
Version: 0.8
Author: Jan Olsen
Author URI: http://kamajole.dk
*/
add_action ( 'admin_menu', 'ch_menu' ) ;
function ch_menu () {
    global $ch_options ;
    add_options_page ( 'CH', 'Comment Highlighter', 9, __FILE__, 'ch_manage_options' ) ;
}

function CommentHighlight ( $link = 'class' ) {
    global $comment, $wpdb ;
    $ret = array ( ) ;

    if ($link == 'link') {
        if (current_user_can ( 'manage_options' )) {
            $ch_options = get_option( 'jpo_comment_highlighter_options' );
            $_url = get_bloginfo ( 'wpurl' ) . "/wp-admin/options-general.php?page={$ch_options['install_path']}&amp;c={$comment->comment_ID}" ;
            echo " <a href='{$_url}'>(comment highlight)</a>" ;
        }
    } elseif ($link == 'class') {
        $evenodd = '$evenodd' . $comment->comment_post_ID ;
        global $$evenodd ;
        $$evenodd ++ ;
        $_meta = $wpdb->get_results ( "SELECT *
                                 FROM {$wpdb->postmeta}
                                 WHERE (meta_key = '_jpo_comment_highlighter' AND post_id = {$comment->comment_post_ID})
                                 OR meta_key = '_jpo_comment_highlighter_global';", ARRAY_A ) ;
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
        echo ' ' . implode ( ' ', $ret ) ;
    }
}

function ch_manage_options () {
    global $wpdb, $VERSION ;

    if ($_POST) {
        if ($_POST [ 'submit' ]) {
            $_meta_key = '_jpo_comment_highlighter' . ($_POST [ 'global' ] ? '_global' : '') ;
            $_post_id = ($_POST [ 'txt_pid' ] ? $_POST [ 'txt_pid' ] : 0) ;
            $_arr = array ( ) ;
            foreach ( $_POST as $key => $val ) {
                list ( $_pre, $_post ) = explode ( '_', $key ) ;
                if ($_pre == 'ch' && $val) {
                    $_arr [ $_post ] = addslashes ( $_POST [ "txt_{$_post}" ] ) ;
                }
            }
            $wpdb->query ( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
                    VALUES ({$_post_id}, '{$_meta_key}', '" . serialize ( $_arr ) . "');" ) ;
        }
        echo "<script type='text/javascript'>" ;
        echo "location.href='{$_POST['referer']}'" ;
        echo "</script>" ;
        exit () ;
    }

    if ($_GET [ 'delete' ]) {
        $wpdb->query ( "DELETE FROM {$wpdb->postmeta} WHERE meta_id = {$_GET['delete']};" ) ;
        echo "<script type='text/javascript'>" ;
        echo "location.href='{$_SERVER['SCRIPT_NAME']}?page={$_GET['page']}'" ;
        echo "</script>" ;
        exit () ;
    }

    if (! function_exists ( 'curl_init' )) {
        echo "<div class='wrap'>" ;
        echo "<div style='color: red;'>This plugin only works with <a href='http://php.net/curl' target='_blank'>curl</a> activated in PHP</div>" ;
        echo "</div>" ;
        include (ABSPATH . 'wp-admin/admin-footer.php') ;
        die () ;
    }

    echo "<div class='wrap'>" ;

    echo "<h2>Comment Highlighter Options</h2>" ;

    if (isset ( $_GET [ 'c' ] )) {
        if ($_GET [ 'c' ] == 'new') {
            $_pid = '0' ;
            $_cid = '0' ;
            $_email = '' ;
            $_name = '' ;
            $_url = '' ;
        } else {
            $_meta = $wpdb->get_row ( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = {$_GET['c']};" ) ;
            $_pid = $_meta->comment_post_ID ;
            $_cid = $_GET [ 'c' ] ;
            $_email = $_meta->comment_author_email ;
            $_name = $_meta->comment_author ;
            $_url = $_meta->comment_author_url ;
        }
        echo "<form method='post' action='{$PHP_SELF}'>" ;
        echo "<input type='hidden' name='referer' value='{$_SERVER['HTTP_REFERER']}' />" ;
        echo "<table border='0' cellpadding='5' cellspacing='0'>" ;
        echo "<caption>Create a new comment highlight based on <i>(only selected criterias will be saved and you can select multiple criterias)</i>:</caption>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_pid' name='ch_pid' value='true' /></td>" ;
        echo "<td style='whitespace: nowrap;'><label for='ch_pid'>".fff('tag_red')." Post ID:</label></td>" ;
        echo "<td><input type='text' name='txt_pid'   style='width: 250px;' value='{$_pid}' /></td>" ;
        echo "<td>&nbsp;</td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_cid' name='ch_cid' value='true' /></td>" ;
        echo "<td style='whitespace: nowrap;'><label for='ch_cid'>".fff('tag_blue')." Comment ID:</label></td>" ;
        echo "<td><input type='text' name='txt_cid'   style='width: 250px;' value='{$_cid}' /></td>" ;
        echo "<td>&nbsp;</td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_email' name='ch_email' value='1' /></td>" ;
        echo "<td><label for='ch_email'>".fff('email')." Email:</label></td>" ;
        echo "<td><input type='text' name='txt_email' style='width: 250px;' value='{$_email}' /></td>" ;
        echo "<td><i>the email of the person</i></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_name' name='ch_name' value='true' /></td>" ;
        echo "<td><label for='ch_name'>".fff('vcard')." Name:</label></td>" ;
        echo "<td><input type='text' name='txt_name'  style='width: 250px;' value='{$_name}' /></td>" ;
        echo "<td><i>the name of the person</i></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_url' name='ch_url' value='true' /></td>" ;
        echo "<td><label for='ch_url'>".fff('world')." URL:</label></td>" ;
        echo "<td><input type='text' name='txt_url'   style='width: 250px;' value='{$_url}' /></td>" ;
        echo "<td><i>the URL the person has entered</i></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_pingback' name='ch_pingback' value='true' /></td>" ;
        echo "<td colspan='2'><label for='ch_pingback'>".fff('arrow_refresh')." Pingback</label></td>" ;
        echo "<td><i>if a comment isn't really a comment but a pingback/trackback</i><input type='hidden' value='true' name='txt_pingback' id='txt_pingback' /></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_even' name='ch_even' value='true' /></td>" ;
        echo "<td colspan='2'><label for='ch_even'>".fff('shape_move_backwards')." Even numbered comments</label></td>" ;
        echo "<td><i>every even numbered comment</i><input type='hidden' value='true' name='txt_even' id='txt_even' /></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td align='center'><input type='checkbox' id='ch_odd' name='ch_odd' value='true' /></td>" ;
        echo "<td colspan='2'><label for='ch_odd'>".fff('shape_move_forwards')." Odd numbered comments</label></td>" ;
        echo "<td><i>every odd numbered comment</i><input type='hidden' value='true' name='txt_odd' id='txt_odd' /></td>" ;
        echo "</tr>" ;
        echo "<tr style='font-weight: bold;'>" ;
        echo "<td align='center'><input type='checkbox' id='global' name='global' value='true' " . ($_GET [ 'c' ] == 'new' ? "checked='checked'" : '') . " /></td>" ;
        echo "<td><label for='global'>".fff('asterisk_yellow')." Global</label></td>" ;
        echo "<td>&nbsp;</td>" ;
        echo "<td><i>this means that the post and comment ID will not be part of the criteria</i></td>" ;
        echo "</tr>" ;
        echo "<tr>" ;
        echo "<td><input type='hidden' name='ch_class' value='true' /></td>" ;
        echo "<td>".fff('tag')." Class(es):</td>" ;
        echo "<td><input type='text' name='txt_class' style='width: 250px;' /></td>" ;
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
        $_meta = $wpdb->get_results ( "SELECT * FROM {$wpdb->postmeta} WHERE LEFT(meta_key, 24) = '_jpo_comment_highlighter';", ARRAY_A ) ;
        if (count ( $_meta )) {
            echo "<table cellpadding='3' cellspacing='0'>" ;
            echo "<tr>" ;
            echo "<th align='left'>Post ID</th>" ;
            echo "<th align='left'>Criteria(s)</th>" ;
            echo "<th align='left'>Class to use</th>" ;
            echo "<td>&nbsp;</td>" ;
            echo "</tr>" ;

            $tofff = array('email'    => 'email.png',
                       'pingback' => 'arrow_refresh.png',
                       'global'   => 'asterisk_yellow.png',
                       'class'    => 'tag.png',
                       'name'     => 'vcard.png',
                       'even'     => 'shape_move_backwards.png',
                       'odd'      => 'shape_move_forwards.png',
                       'pid'      => 'tag_red.png',
                       'cid'      => 'tag_blue.png',
                       'url'      => 'world.png',
                       );

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
                echo "<td valign='top'><a href='{$_SERVER['REQUEST_URI']}&amp;delete={$row['meta_id']}' title='Delete this comment highlight'>Delete</a></td>" ;
                echo "</tr>" ;
            }
            echo "</table>" ;
        } else {
            echo "<p>You haven't got any comment highlights yet...</p>" ;
        }
        $_url = get_bloginfo ( 'wpurl' ) . "/wp-admin/options-general.php?page={$_GET['page']}&amp;c=new" ;
        echo "<input type='button' style='background-color: #ccffcc;' value=' Add a new comment highlight ' onclick=\"location.href = '{$_url}';\" />" ;

        update_option( 'jpo_comment_highlighter_options' , array('install_path' => $_GET['page']) );
    }
    echo "</div>" ;
}

function comment_highlighter () {
    global $wpdb, $comment_highlighter_cache ;

    if (! isset ( $comment_highlighter_cache )) {

        $comment_highlight = $wpdb->get_results ( "SELECT   post_id, meta_value
          FROM {$wpdb->postmeta} AS pm
          INNER JOIN {$wpdb->posts} AS p ON (pm.post_id = p.ID)
          WHERE meta_key = '_jpo_comment_highlighter'
          AND (post_status = 'static' OR post_status = 'publish');" ) ;
    } else {
        return $comment_highlighter_cache ;
    }

    if (! $comment_highlight) {
        $comment_highlighter_cache = false ;
        return false ;
    }

    foreach ( $comment_highlight as $link ) {
        $comment_highlighter_cache [ $link->post_id ] = $link->meta_value ;
    }

    return $comment_highlighter_cache ;
}

if (! function_exists('fff')) {
  function fff($name) {
    $name = basename($name,'.png');
    return "<img src='http://famfamfam.googlecode.com/svn/wiki/images/{$name}.png' alt='{$name}' style='width: 16px; vertical-align: middle;' />";
  }
}
?>