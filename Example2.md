# Highlight all comments based on the email on a specific post #
## Changes to the Comment Highlighter plugin ##
  1. Go to **Admin** -> **Options** -> **Comment Highlighter**
  1. Click the button |Add a new comment highlight|
|:--------------------------|
  1. Check the checkbox at "Post ID" and write the post ID (can be found on Admin -> Manage -> Posts in the left column) - i.e. `42`
  1. Check the checkbox at "Email" and write the email address in the text field - i.e. `vip@example.org`
  1. Make sure the "Global" checkbox is **NOT** checked
  1. Make sure to write a class name at the "Class(es)" text field - i.e. "`vipcomment`"
  1. Click the button |Add this comment highlight|

## Changes to the style sheet ##
  1. Now go to **Admin** -> **Presentation** -> **Theme Editor** and choose Stylesheet in the right menu
  1. Add the following (or the style of your choice) to the style sheet:
```
.commentlist .vipcomment {
  background-color: #ffeeee;
  border: #eedddd 2px dashed;
}
```

## Result ##
All comments on post ID 42, made by any person who has written `vip@example.org` as their email address will now be "highlighted" according to the style above.