<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css?v=1">
    <title><?= $title ?></title>
</head>
<body>
    <div id="wrapper">
        <header>
            <?php if (isset($_SESSION['auth']) AND $_SESSION['auth'] == TRUE) include 'elems/header.php'; ?>
        </header>
        <main>
            <?php include 'elems/info.php' ?>
            <?= $content ?>
        </main>
    </div>
</body>
</html>