<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <title>ovkadcheck</title>
</head>
<body>

<script>
    window.addEventListener('load', (e) => {
        window.ovkadcheckLoad = window.performance.now();
    });
    document.addEventListener('readystatechange', (e) => {
        if (document.readyState === 'complete') {
            window.ovkadcheckComplete = window.performance.now();

            <?php if (isset($iabMode) && $iabMode > 0): ?>
                (function sendIabHostLoaded() {
                    const frames = document.getElementsByTagName('iframe');
                    for (let i = 0; i < frames.length; i++) {
                        frames[i].contentWindow.postMessage('IAB_HOST_LOADED', '*');
                        const innerFrames = frames[i].contentDocument.getElementsByTagName('iframe');
                        for (let i = 0; i < innerFrames.length; i++) {
                            innerFrames[i].contentWindow.postMessage('IAB_HOST_LOADED', '*');
                        }
                    }
                })();
            <?php endif; ?>
        }
    });
</script>

    <?php /* if (isset($source) && strlen($source) > 0): ?>

        <iframe style="width:100%;height:100%" id="ovkadcheckMyFrame" src="about:blank"></iframe>

        <script type="text/javascript">
            let doc = document.getElementById('ovkadcheckMyFrame').contentWindow.document;
            doc.open();
            doc.write(<?php echo json_encode($source); ?>);
            doc.close();
        </script>

    <?php endif; */ ?>

    <?php if (isset($iframe) && strlen($iframe) > 0): ?>

        <iframe style="width:100%;height:100%" src="<?php echo $iframe; ?>"></iframe>

    <?php endif; ?>

    <?php if (isset($assets) && strlen($assets) > 0): ?>
        <?php echo $assets; ?>
    <?php endif; ?>

    <?php if (isset($iabMode) && $iabMode === 500): ?>
        <script src="/delayReady500ms.php"></script>
    <?php endif; ?>
    <?php if (isset($iabMode) && $iabMode === 100): ?>
        <script src="/delayReady100ms.php"></script>
    <?php endif; ?>


</body>
</html>
