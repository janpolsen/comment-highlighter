There isn't any difference between installing this plugin for the first time and upgrading the plugin.

  * Download the latest release [here](http://code.google.com/p/comment-highlighter/downloads/list)
  * Save that file as `comment_highlighter.php` in your plugin-directory
  * Activate the plugin called **Comment Highlighter** in your admin section under Plugins
  * Start adding some "rules"
  * Add the following line to wherever you define the class of your comment:
```
<?php if(function_exists('CommentHighlight')) CommentHighlight(); ?>
```
> If you use the "Connection" theme, then this line is located in the comments.php file and can look something like this:
```
<li class="<?php if(function_exists('CommentHighlight')) CommentHighlight(); ?>" id="comment-<?php comment_ID() ?>">
```
  * You might also want to add the following line to your comment header:
```
<?php if(function_exists('CommentHighlight')) CommentHighlight('link'); ?>
```

> This will make a link in you comment header which links directly to the admin interface of the plugin. The email, name, URL, comment and post ID will be pre-filled this way and you can just tag which options you want to use.

> The link will of course only be visible for the admins of the blog and not to normal readers.

> ![http://kamajole.dk/images/20070709_comment_highlight.png](http://kamajole.dk/images/20070709_comment_highlight.png)

  * From v0.10 it is possible to add a prefix and/or postfix which will be added to the class(es) for matching rules. You can assign that prefix/postfix like:
```
<?php if(function_exists('CommentHighlight')) CommentHighlight('class', array('prefix' => 'pre_', 'postfix' => '_post')); ?>
```