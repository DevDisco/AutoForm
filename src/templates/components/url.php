    <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="{{id}}">{{label}}</label>
        <div class="col-sm-9">
            <input class="form-control" type="url" id="{{id}}" name="{{name}}" value="{{value}}" maxlength="{{maxlength}}" {{required}} pattern="https?://.+" title="{{instructions}}">
            <div class="invalid-feedback">
                Dit is geen geldige URL.
            </div>
        </div>
    </div>