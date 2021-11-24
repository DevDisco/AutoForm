    <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="{{id}}">{{label}}</label>
        <div class="col-sm-9">
            <input class="form-control" id="{{id}}" type="{{type}}" name="{{name}}" value="{{value}}" maxlength="{{maxlength}}" max="{{max}}" title="{{instructions}}" {{required}}>
            <div class=" invalid-feedback">
                Dit veld moet (correct) ingevuld worden.
            </div>
        </div>
    </div>