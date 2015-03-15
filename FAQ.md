### Could you please explain exactly where the PHP line should be added? ###

Most themes (if not all of them) uses shows each comment as a `<li>...</li>`-tag, so the whole purpose is to add a class to this tag.

Looking at the default Wrodpress theme, then the essential lines in `comment.php` looks like this:
```
/* This variable is for alternating comment background */
$oddcomment = 'class="alt" ';

<ol class="commentlist">

<?php foreach ($comments as $comment) : ?>

  <li <?php echo $oddcomment; ?>id="comment-<?php comment_ID() ?>">
    <cite><?php comment_author_link() ?></cite> Says:
    <?php if ($comment->comment_approved == '0') : ?>
    <em>Your comment is awaiting moderation.</em>
    <?php endif; ?>
    <br />

    <small class="commentmetadata"><a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date('F jS, Y') ?> at <?php comment_time() ?></a> <?php edit_comment_link('edit','&nbsp;&nbsp;',''); ?></small>

    <?php comment_text() ?>

  </li>

<?php
  /* Changes every other comment to a different class */
  $oddcomment = ( empty( $oddcomment ) ) ? 'class="alt" ' : '';
?>

<?php endforeach; /* end for each comment */ ?>

</ol>
```
In line 1 a variable called `$oddcomment` is set to `class="alt"` and that value is changed after displaying each comment. The result of this is, that every other comment will have a class name called `alt`, which again can have it's own style.
Anyways, those lines of codes are not necessary when using this plugin, since the plugin will handle those classes itself.

The above default theme for comments can be rewritten to utilize the Comment Highlighter plugin as follows:
```
<ol class="commentlist">

<?php foreach ($comments as $comment) : ?>

  <li class="<?php if(function_exists('CommentHighlight')) CommentHighlight(); ?>" id="comment-<?php comment_ID() ?>">
    <cite><?php comment_author_link() ?></cite> Says:
    <?php if ($comment->comment_approved == '0') : ?>
    <em>Your comment is awaiting moderation.</em>
    <?php endif; ?>
    <br />

    <small class="commentmetadata"><a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date('F jS, Y') ?> at <?php comment_time() ?></a> <?php edit_comment_link('edit','&nbsp;&nbsp;',''); ?></small>

    <?php comment_text() ?>

  </li>

<?php endforeach; /* end for each comment */ ?>

</ol>
```

So to get back to your question, then you can see the PHP line code has been placed inside the comment loop (which starts at line 3 above). To be more exact, then it is placed as an attribute to the `<li>...</li>`-tag (in line 5 above).

You need to look at your `comments.php` and find a structure like...
```
... foreach $comments ...
...   <li id="xxx"> ...
...   </li> ...
... endforeach ...
```
and replace the `<li>`-tag to look like...
```
...   <li class="<?php if(function_exists('CommentHighlight')) CommentHighlight(); ?>" id="xxx">
```
instead.

I hope that helps finding the right place to add the line of code. If not then feel free to post or send me the `comments.php` file, then I can pinpoint the exact spot ![http://famfamfam.googlecode.com/svn/wiki/images/emoticon_smile.png](http://famfamfam.googlecode.com/svn/wiki/images/emoticon_smile.png).