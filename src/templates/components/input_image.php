    <div class="row mb-3">
        <label class="col-sm-3 col-form-label" for="{{id}}">{{label}}</label>
        <div class="col-sm-9">
            <fieldset id="upload-{{id}}" class="{{class_input}}" {{disabled}}>
                <input id="max-{{id}}" type="hidden" name="MAX_FILE_SIZE" value="{{maxfilesize}}" />
                <input type="hidden" name="prefill_{{id}}" value="{{value}}" />
                <input onchange="checkUpload(event)" class="form-control" type="file" name="{{name}}" accept="{{accept}}" title="{{instructions}}" data-width="{{width}}" data-heigth="{{heigth}}" {{required}}>
                <div class="custom-feedback">
                    Sorry, this file is too large. {{instructions}}
                </div>
            </fieldset>
            <fieldset id="prefill-{{id}}" class="input-group mb-3 {{class_prefill}}">
                <span onClick="toggleFileInput('{{id}}')" class="input-group-text" id="delete-image"><i class="bi bi-trash"></i></span>
                <input type="hidden" name="prefill_{{id}}" value="{{value}}" />
                <input type="text" name="{{name}}" class="form-control" aria-label="image" aria-describedby="delete-image" value="{{value}}" readonly>
                <span class="text-muted">This field already has an image attached to it. Delete the image if you want to replace it with a new one.</span>
            </fieldset>
        </div>
    </div>