      <fieldset class="row mb-3">
          <legend class="col-form-label col-sm-3 pt-0">{{label}}</legend>
          <div class="col-sm-9">
              [[repeat]]
              <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="{{name}}[]" value="{{value}}" id="{{id}}" title="{{instructions}}" {{required}} {{checked}}>
                  <label class=" form-check-label" for="{{id}}" title="{{instructions}}">
                      {{label}}
                  </label>
              </div>
              [[repeat]]
          </div>
      </fieldset>