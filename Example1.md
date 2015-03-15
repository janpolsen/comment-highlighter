# Highlight all comments based on the email #
## Changes to the Comment Highlighter plugin ##
  1. Go to **Admin** -> **Options** -> **Comment Highlighter**
  1. Click the button |Add a new comment highlight|
|:--------------------------|
  1. Check the checkbox at "email" and write the email address in the text field - i.e. `johndoe@example.org`
  1. Make sure the "Global" checkbox is checked
  1. Make sure to write a class name at the "Class(es)" text field - i.e. "`admincomment`"
  1. Click the button |Add this comment highlight|

## Changes to the style sheet ##
  1. Now go to **Admin** -> **Presentation** -> **Theme Editor** and choose Stylesheet in the right menu
  1. Add the following (or the style of your choice) to the style sheet:
```
.commentlist .admincomment {
  background-color: #eeeeff;
  border: #ddddee 1px solid;
}
```

## Result ##
All comments made by any person who has written `johndoe@example.org` as their email address will now be "highlighted" according to the style above.