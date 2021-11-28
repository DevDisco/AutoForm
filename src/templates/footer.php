<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<script async="" src="js/validate-forms.js"></script>
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


    function checkUpload(event) {

        const fileInput = event.target;
        const fileSizeInputId = "max_" + fileInput.id;
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
</script>
</body>

</html>