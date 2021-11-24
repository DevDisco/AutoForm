A component is a simple template of an input or input group. It used an even simpler template markup based on Twig et all. You can only use two things:
- a variable placeholder, {{value}} will be swapped with the contents of $value,
- a set of [[repeat]] anchors around a block that must be repeated in a select or checkbox group

I use Bootstrap5 css here, but you could use any styling you like.

-------------------------------------------------
Variables and their use to set attribute values
-------------------------------------------------
name="{{name}}" : name of the input
id="{{id}}" : id of the input
value="{{value}}" : preset value
label="{{label}}" : more readable variant of {{name}}
title="{{instructions}}" : instructions on how to fill out the form
maxlength="{{maxlength}}" : max number of characters that the input will accept
max="{{max}}" : max value of the integer the input will accept
{{required}} : is added when the sql field doesn't accept NULL
{{checked}} : is added when a select or checkbox field 

The system is quite forgiving and will clean up any placeholders that haven't been used.

-------------------------------------------------
How to use [[repeat]]
-------------------------------------------------
<fieldset class="row mb-3">
    <legend class="col-form-label col-sm-3 pt-0">{{label}}</legend>
    <div class="col-sm-9">
        [[repeat]]
        <div class="form-check">
            <input class="form-check-input" type="{{type}}" name="{{name}}[]" value="{{value}}" id="{{id}}" title="{{instructions}}" {{required}} {{checked}}>
            <label class=" form-check-label" for="{{id}}"">
                {{label}}
            </label>
        </div>
        [[repeat]]
    </div>
</fieldset>
