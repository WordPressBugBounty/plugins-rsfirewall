<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php if ( function_exists( 'language_attributes' ) && function_exists( 'is_rtl' ) ) language_attributes(); else echo "dir='$text_direction'"; ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width">
    <meta name='robots' content='noindex,follow' />
    <title><?php echo esc_html__('404 Page Not Found', 'rsfirewall'); ?></title>
    <style type="text/css">
        html {
            background: #f1f1f1;
        }
        body {
            color: #444;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        h1 {
            border-bottom: 1px solid #dadada;
            clear: both;
            color: #e65050;
            font-size: 24px;
            margin: 15px 0 0 0;
            padding: 0;
            padding-bottom: 7px;
            text-align: center;
        }

        .block {
            background: #fff;
            max-width: 60%;
            margin: 2em auto;
            -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            padding: 1em 2em;
        }
        #not-found-page {
            margin-top: 50px;
            text-align:center;
        }
        #not-found-page p {
            font-size: 16px;
            line-height: 1.5;
            margin: 25px 0 20px;
            text-align:center;
        }

        .error > p {
            margin: 10px 0 10px !important;
        }

        #not-found-page code {
            font-family: Consolas, Monaco, monospace;
        }


        <?php
        if ( 'rtl' == $text_direction ) {
            echo 'body { font-family: Tahoma, Arial; }';
        }
        ?>
    </style>
</head>
<body id="not-found-page">
<div class="block">
    <h1><?php echo esc_html__('404 Page Not Found', 'rsfirewall'); ?></h1>
    <p><?php echo $error_msg; ?></p>
</div>
</body>
</html>