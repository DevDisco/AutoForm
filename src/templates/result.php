<div class="container w-75">
    <h1 class="text-center"><?=$title?></h1>

    <?= $message ?>
    
    <p>You can close this page or return to the <a href="index.php?t=<?= Request::getTable(); ?>">editor</a></p>
</div>