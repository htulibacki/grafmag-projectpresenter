<?php

define('PP_ROOT_DIR', '');          // podaj ścieżkę do projektów, relatywnie do głównego folderu Dropboxa, zaczynając od znaku "/""
define('PP_ACCESS_TOKEN', '');      // podaj wygenerowany access token
define('PP_CACHE_DIR', '/cache');   // podaj folder z plikamy cache, relatywnie do index.php

$projectName = isset($_GET['projekt']) ? $_GET['projekt'] : null;
$useCache = isset($_GET['cache']) ? (bool) $_GET['cache'] : true;
$clearCache = isset($_GET['clear-cache']) ? (bool) $_GET['clear-cache'] : false;

$projectExists = true;
$projectEmpty = false;
$projectNoImages = false;
$cacheFileName = realpath(dirname(__FILE__) . PP_CACHE_DIR) . DIRECTORY_SEPARATOR . $projectName . '.cache';

if (!is_null($projectName) && preg_match('/^[a-z0-9\-]+$/i', $projectName)) {
    if ($clearCache && file_exists($cacheFileName)) {
        unlink($cacheFileName);
    }

    if (file_exists($cacheFileName) && ((time() - filemtime($cacheFileName)) < 3600) && $useCache) {
        $images = unserialize(file_get_contents($cacheFileName));
    } else {
        require_once 'Dropbox/autoload.php';

        $client = new Dropbox\Client(PP_ACCESS_TOKEN, 'Projects-Previewer/1.0');

        $projectFolder = $client->getMetadataWithChildren(PP_ROOT_DIR . '/' . $projectName);

        if (is_null($projectFolder)) {
            $projectExists = false;
        } else {
            $images = array();

            foreach ($projectFolder['contents'] as $image) {
                if (!in_array($image['mime_type'], array('image/jpeg', 'image/png'))) {
                    continue;
                }

                $tempLink = $client->createTemporaryDirectLink($image['path']);
                $tempLinkPathInfo = pathinfo($tempLink[0]);
                $images[] = array('link' => $tempLink[0], 'name' => urldecode($tempLinkPathInfo['filename']));
            }

            if (!empty($images)) {
                if ($useCache) {
                    file_put_contents($cacheFileName, serialize($images));
                }
            } else {
                $projectNoImages = true;;
            }
        }
    }
} elseif (!is_null($projectName) && !preg_match('/^[a-z0-9\-]+$/i', $projectName)) {
    $projectExists = false;
} else {
    $projectEmpty = true;
    $projectExists = false;
}

?>
<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="robots" content="noindex, nofollow">

        <title>Przeglądarka projektów</title>

        <style>
            body {
                margin: 0;
                background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjE4OERBRTQ2MThCQjExRTRCOEMwRTkzNzcyREU3ODU3IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjE4OERBRTQ3MThCQjExRTRCOEMwRTkzNzcyREU3ODU3Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MTg4REFFNDQxOEJCMTFFNEI4QzBFOTM3NzJERTc4NTciIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6MTg4REFFNDUxOEJCMTFFNEI4QzBFOTM3NzJERTc4NTciLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz75KiBQAAAAKklEQVR42mL5//8/AzZw+PBhrOJMDCSCUQ3EABZc4W1razsaSvTTABBgAKdQCGsTFUuXAAAAAElFTkSuQmCC) 0 0 repeat;
                font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;
            }

            header {
                position: fixed;
                top: 0;
                right: 0;
                left: 0;
                height: 25%;
                min-height: 51px
            }

            #bar {
                font-size: 14px;
                line-height: 1em;
                background-color: rgba(0, 0, 0, 0.87);
                padding: 16px 25px;
                opacity: 0;
                transition: opacity 300ms;
            }

            header:hover #bar {
                opacity: 1;
                transition: opacity 300ms;
            }

            #bar .sizes {
                float: right;
                line-height: 19px;
                font-weight: bold;
            }

            #bar h1 {
                margin: 0;
            }

            #bar a {
                color: #fff;
                text-decoration: none;
                margin-left: 20px;
            }

            #bar a:hover {
                color: #44c8ff;
            }

            #project img {
                display: none;
                margin: 0 auto;
            }

            form {
                position: fixed;
                left: 50%;
                top: 50%;
                -ms-transform: translate(-50%, -50%);
                -webkit-transform: translate(-50%, -50%);
                transform: translate(-50%, -50%);
                box-sizing: border-box;
                text-align: center;
                background-color: #ffffff;
            }

            form fieldset {
                padding: 30px;
                margin: 0;
                border: 0;
            }

            form h1 {
                margin: 0 0 20px;
            }

            form input {
                color: #b1b7ba;
                font-family: inherit;
                font-size: 14px;
                border: 1px solid #d8dee5;
                padding: 10px 15px;
                box-sizing: border-box;
                width: 360px;
            }

            .error {
                position: fixed;
                left: 50%;
                top: 50%;
                -ms-transform: translate(-50%, -50%);
                -webkit-transform: translate(-50%, -50%);
                transform: translate(-50%, -50%);
                box-sizing: border-box;
                background-color: #d7182a;
                font-weight: bold;
                color: #fff;
                line-height: 1.29em;
                font-size: 14px;
                padding: 40px;
                max-width: 80%;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <?php if ($projectExists && !$projectNoImages): ?>
        <header>
            <div id="bar">
                <div class="sizes">
                    <?php foreach ($images as $image): ?>
                    <a href="#s<?php echo $image['name']; ?>" data-name="<?php echo $image['name']; ?>"><?php echo htmlspecialchars($image['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endforeach ?>
                </div>

                <h1><img src="images/logo.png" alt="Grafmag"></h1>
            </div>
        </header>

        <div id="project">
            <?php foreach ($images as $image): ?>
            <img src="<?php echo $image['link']; ?>" alt="" data-name="<?php echo $image['name']; ?>">
            <?php endforeach ?>
        </div>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
        <script>
            /* v1.5 */
            /* https://github.com/farinspace/jquery.imgpreload */
            if("undefined"!=typeof jQuery){(function(a){a.imgpreload=function(b,c){c=a.extend({},a.fn.imgpreload.defaults,c instanceof Function?{all:c}:c);if("string"==typeof b){b=new Array(b)}var d=new Array;a.each(b,function(e,f){var g=new Image;var h=f;var i=g;if("string"!=typeof f){h=a(f).attr("src")||a(f).css('background-image').replace(/^url\((?:"|')?(.*)(?:'|")?\)$/mg, "$1");i=f}a(g).bind("load error",function(e){d.push(i);a.data(i,"loaded","error"==e.type?false:true);if(c.each instanceof Function){c.each.call(i)}if(d.length>=b.length&&c.all instanceof Function){c.all.call(d)}a(this).unbind("load error")});g.src=h})};a.fn.imgpreload=function(b){a.imgpreload(this,b);return this};a.fn.imgpreload.defaults={each:null,all:null}})(jQuery)}

            $(function() {
                var imageSizes = [];

                function findPerfectImage(sizes) {
                    var windowWidth = $(window).width(), difference = 10000, perfectImageSize = 0;

                    sizes.forEach(function (size) {
                        var sizeDiff = windowWidth - size;

                        if (sizeDiff > 0 && sizeDiff < difference) {
                            difference = sizeDiff;
                            perfectImageSize = size;
                        }
                    });

                    if (perfectImageSize === 0) {
                        sizes.sort(function (a, b) {
                            return a - b;
                        });
                        perfectImageSize = sizes[0];
                    }

                    return perfectImageSize;
                };

                $('#bar a').on('click', function(e) {
                    e.preventDefault();

                    $('#project img:visible').hide();
                    $('#project img[data-name="' + $(this).data('name') + '"]').css('display', 'block');
                });

                $('#project img').imgpreload(function() {
                    this.forEach(function(image) {
                        image = $(image);
                        image.attr('data-size', image.width());
                        imageSizes.push(image.width());
                    });

                    $('#project img[data-size="' + findPerfectImage(imageSizes) + '"]').css('display', 'block');
                });

                $(window).on('resize', function() {
                    var perfectImage = $('#project img[data-size="' + findPerfectImage(imageSizes) + '"]');

                    if (!perfectImage.is(':visible')) {
                        $('#project img:visible').hide();
                        perfectImage.css('display', 'block');
                    }
                });
            });
        </script>
        <?php elseif ($projectExists && $projectNoImages): ?>
        <div class="error">Wybrany projekt nie posiada żadnego wgranego podglądu.</div>
        <?php elseif (!$projectExists && !$projectEmpty): ?>
        <div class="error">Wybrany projekt nie istnieje.</div>
        <?php else: ?>
            <form action="" method="get">
                <fieldset>
                    <h1><img src="images/logo-dark.png" alt="Grafmag"></h1>
                    <input type="text" name="projekt" id="projekt" placeholder="Podaj nazwę projektu i wciśnij enter">
                </fieldset>
            </form>
        <?php endif; ?>
    </body>
</html>