<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body { background-color: #f7fafc; color: #718096; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif; }
        .container { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .content { text-align: center; }
        .title { font-size: 20px; font-weight: 600; padding: 20px; border-right: 2px solid; display: inline-block; margin-right: 20px; }
        .message { font-size: 18px; display: inline-block; }
        .debug { margin-top: 30px; padding: 20px; background-color: #edf2f7; border-radius: 6px; text-align: left; max-width: 800px; overflow-x: auto; }
        .debug h2 { margin-top: 0; font-size: 18px; }
        .debug pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <div class="title">
            <?= htmlspecialchars($code) ?>
        </div>
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>

        <?php if (isset($exception)): ?>
            <div class="debug">
                <h2><?= htmlspecialchars(get_class($exception)) ?></h2>
                <p><?= htmlspecialchars($exception->getMessage()) ?></p>
                <pre><?= htmlspecialchars($exception->getTraceAsString()) ?></pre>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
