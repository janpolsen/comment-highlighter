### Debug key ###
A secret key used to run the plugin in debug mode. You can add `?debug=xxx` or `&debug=xxx` where `xxx` is the debug key to the URL of any page. This will result in the plugin writing a log file called `comment_highlighter.log` to the avatar cache directory.

Following is a few examples of how to add the debug parameter to an URL:
If your URL follows the pattern:

  * `http://example.com/blog/plugins/comment_highlighter/`
    * add `?debug=xxx`
      * result: `http://example.com/blog/plugins/comment_highlighter/?debug=xxx`

  * `http://example.com/blog/plugins/comment_highlighter/comment-page-1/#comments`
    * add `?debug=xxx` BEFORE the `#comments`
      * result: `http://example.com/blog/plugins/comment_highlighter/comment-page-1/?debug=xxx#comments`

  * `http://example.com/blog/wp-admin/options-general.php?page=comment_highlighter/comment_highlighter.php`
    * add `&debug=xxx` instead of `?debug=xxx`
      * result: `http://example.com/blog/wp-admin/options-general.php?page=comment_highlighter/comment_highlighter.php&debug=xxx`

The log file is useful to find out what happens during the rendering of a page and also to locate a possible bottleneck.

**NOTE: The log file will contain email addresses as well as full path names of your system, so have that in mind before you send or publish that log file to anyone.**

Sample setting: `myleetpassword42`

### Post ID ###
This is the internal [Wordpress](http://wordpress.org) ID of the post

### Comment ID ###
This is the internal [Wordpress](http://wordpress.org) ID of the comment

### Email ###
This is the email address of the commenter

### Name ###
This is the name of the commenter

### URL ###
This is the URL of the commenter

### Pingback ###
This is a boolean variable indication whether or not a comment is actually a pingback

### Even numbered comments ###
This is every even numbered comments. The actual comment number on the post isn't needed at all. Everything is handled internally in the plugin.

### Odd numbered comments ###
This is every odd numbered comments. The actual comment number on the post isn't needed at all. Everything is handled internally in the plugin.

### Global ###
This indicates if the rule should be global or not. If this checkbox is checked, then the post ID and comment ID will be ignored, but all other rules are still used.

This is very usefull, if you i.e. want to target all comments made by a specific person based on that persons email address. Just write the email address and check the boxes at "Email" and "Global" and finally write the class to use.

### Class(es) ###
The name of the class that should be added to the comment based on the above selected rules. Separate multiple classes with a space.