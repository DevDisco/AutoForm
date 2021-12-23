<form action='process.php' method='post' class="needs-validation" {{enctype}} novalidate>
    <!-- Start: don't change -->
    <input type="hidden" name="t" id="t" value="{{table}}">
    <input type="hidden" name="id" id="id" value="{{id}}">
    {{inputs}}
    <!-- End: don't change -->
    <div class="col-12">
        <button class="btn btn-primary" id="submit" type="submit" onClick="checkForm(event)">Wijzigen</button>
    </div>
</form>