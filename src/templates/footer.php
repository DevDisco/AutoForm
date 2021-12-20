<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<script>
    function getNextSibling(elem, selector) {

        // Get the next sibling element
        var sibling = elem.nextElementSibling;

        // If the sibling matches our selector, use it
        // If not, jump to the next sibling and continue the loop
        while (sibling) {
            if (sibling.matches(selector)) return sibling;
            sibling = sibling.nextElementSibling
        }
    };

    function toggleFileInput(id) {

        const fileFieldSet = document.getElementById("upload-" + id);
        const prefillInput = document.getElementById("prefill-" + id);

        fileFieldSet.classList.remove("d-none");
        fileFieldSet.disabled = false;
        prefillInput.classList.add("d-none");
        prefillInput.disabled = true;
    }


    //todo: check image dimensions?
    //https://stackoverflow.com/questions/8903854/check-image-width-and-height-before-upload-with-javascript
    function checkUpload(event) {

        const fileInput = event.target;
        const fileSizeInputId = "max-" + fileInput.id;
        const maxFileSize = document.getElementById(fileSizeInputId).value;
        const instructionsDiv = getNextSibling(fileInput, ".custom-feedback");

        console.log("upload_check", instructionsDiv);

        if (fileInput.files[0].size > maxFileSize) {

            instructionsDiv.style.display = "inline";
            fileInput.value = "";
        } else {

            instructionsDiv.style.display = "none";
        }

    };

    function checkForm(event) {

        const form = event.target.parentElement.parentElement;

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.classList.add("was-validated");

        console.log(form);
    }

    // Example starter JavaScript for disabling form submissions if there are invalid fields
    // (function() {
    //     "use strict";

    //     // Fetch all the forms we want to apply custom Bootstrap validation styles to
    //     var forms = document.querySelectorAll(".needs-validation");

    //     // Loop over them and prevent submission
    //     Array.prototype.slice.call(forms).forEach(function(form) {
    //         form.addEventListener(
    //             "submit",
    //             function(event) {
    //                 if (!form.checkValidity()) {
    //                     event.preventDefault();
    //                     event.stopPropagation();
    //                 }

    //                 form.classList.add("was-validated");
    //             },
    //             false
    //         );
    //     });
    // })();
</script>
</body>

</html>