    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="theme-color" content="#0D47A1">
    <title><?= e($title ?? setting('site_name', '9JACASH')) ?> | <?= e(setting('site_name', '9JACASH')) ?></title>
    <meta name="description" content="<?= e(setting('site_tagline', 'Earn. Grow. Cash Out.')) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- App theme -->
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">

    <script>
      (function(){
        var t = localStorage.getItem('9jc_theme');
        if (t) document.documentElement.setAttribute('data-theme', t);
      })();
    </script>
