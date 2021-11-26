<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><?= $config->FORM_TITLE ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav">
                <?php foreach ($navbar->getListOfTables() as $key => $value) : ?>
                    <?php if ($navbar->getCurrentTable() == $key) : ?>
                        <a class="nav-link disabled" href="index.php?t=<?= $key ?>" tabindex="-1" aria-disabled="true"><?= $value ?></a>
                    <?php else : ?>
                        <a class="nav-link" href="index.php?t=<?= $key ?>"><?= $value ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</nav>