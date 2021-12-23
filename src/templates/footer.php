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
        console.log(document.getElementById(fileSizeInputId));
        const maxFileSize = document.getElementById(fileSizeInputId).value;
        const instructionsDiv = getNextSibling(fileInput, ".custom-feedback");

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
    }

    function confirmDelete(t, id, encoded) {

        if (confirm("Are you sure you want to delete this record?") === true) {

            window.location = "process.php?t=" + t + "&id=" + id + "&d=" + encoded;
        }
    }

    // GET THE IMAGE WIDTH AND HEIGHT USING fileReader() API.
    function readImageFile(file) {
        var reader = new FileReader(); // CREATE AN NEW INSTANCE.

        reader.onload = function(e) {
            var img = new Image();
            img.src = e.target.result;

            img.onload = function() {
                var w = this.width;
                var h = this.height;

                console.log(w, h);
            }
        };
        reader.readAsDataURL(file);
    }
</script>
</body>

</html>