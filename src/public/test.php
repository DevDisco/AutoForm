<!DOCTYPE HTML>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autoform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

</head>

<body>
    <h1 class="text-center">Autoform</h1>

    <div class="container w-75">
        <form action='process.php' method='post' class="needs-validation" novalidate>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label" for="url">Url</label>
                <div class="col-sm-9">
                    <input class="form-control" type="url" id="url" name="url" value="" maxlength="64" required title="Een URL, inclusief http(s)://">
                    <div class="invalid-feedback">
                        Dit is geen geldige URL.
                    </div>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" id="submit" type="submit">Invoeren</button>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>

</html>