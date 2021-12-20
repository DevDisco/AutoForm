<div class="row mb-3">
    <label class="col-sm-3 col-form-label" for="{{id}}">{{label}}</label>
    <div class="col-sm-9">
        <select id="{{id}}" class='form-select' name="{{name}}" title="{{instructions}}" {{required}} {{readonly}}>
            <option value=''>&nbsp;</option>
            [[repeat]]
            <option value="{{value}}" {{selected}}>{{label}}</option>
            [[repeat]]
        </select>
    </div>
</div>