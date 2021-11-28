The .json files override the default field settings based on the configuration of your mysql table. The filename must be equal to the corresponding sql field name.
You will generally use this n combination with a component, which is a custom input.

{
  "component": "the filename of the component you want to use, without .php",
  "type": "the html5 type of the standard input, ignored if you use a component",
  "options": "an array with the options you want to use for a field. This must be combined with a select or a group of checkboxes/radio-buttons",
  "maxlength": "you can override the maxlength value based on the sql field type, as long as you make sure it's not larger than what the database will accept.",
  "max": "see above and check your table very well.",
  "label": "overrides the label generated from the sql table.",
  "value": "overrides the default value generated from the sql table.",
  "accept": "specifies which file type(s) to accept in a file input, ie: .png or image/jpeg",
  "maxfilesize": "used with the file component, max size in bytes, default 4mb"
}
