<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php if ( function_exists( 'language_attributes' ) && function_exists( 'is_rtl' ) ) language_attributes(); else echo "dir='$text_direction'"; ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width">
    <meta name='robots' content='noindex,follow' />
    <title><?php echo esc_html__('Protected Area', 'rsfirewall'); ?></title>
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
            color: #666;
            font-size: 24px;
            margin: 15px 0 0 0;
            padding: 0;
            padding-bottom: 7px;
            text-align: center;
        }
        .error {
            max-width: 400px;
            margin: 0em auto;
            padding: 5px 2em;
            border-left: 4px solid #dc3232;
            background-color: #fff;
            -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
            box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
        }

        .block {
            background: #fff;
            max-width: 400px;
            margin: 2em auto;
            -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            padding: 1em 2em;
        }
        #login-page {
            margin-top: 50px;
            text-align:center;
        }
        #login-page p {
            font-size: 14px;
            line-height: 1.5;
            margin: 25px 0 20px;
        }

        .error > p {
            margin: 10px 0 10px !important;
        }

        #login-page code {
            font-family: Consolas, Monaco, monospace;
        }

        .button {
            color: #fff;
            text-shadow: 0 -1px 0 rgba(0,0,0,0.25);
            background-color: #006dcc;
            *background-color: #04c;
            background-image: -moz-linear-gradient(top,#08c,#04c);
            background-image: -webkit-gradient(linear,0 0,0 100%,from(#08c),to(#04c));
            background-image: -webkit-linear-gradient(top,#08c,#04c);
            background-image: -o-linear-gradient(top,#08c,#04c);
            background-image: linear-gradient(to bottom,#08c,#04c);
            background-repeat: repeat-x;
            border-color: #04c #04c #002a80;
            border-color: rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc',endColorstr='#ff0044cc',GradientType=0);
            filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);

            padding: 11px 19px;
            font-size: 17.5px;
            -webkit-border-radius: 6px;
            -moz-border-radius: 6px;
            border-radius: 6px;
            display: inline-block;
            cursor:pointer;
        }

        .button:hover,
        .button:focus {
            color: #fff;
            background-color: #04c;
            *background-color: #003bb3;
            text-decoration: none;
            background-position: 0 -15px;
            -webkit-transition: background-position .1s linear;
            -moz-transition: background-position .1s linear;
            -o-transition: background-position .1s linear;
            transition: background-position .1s linear;
        }

        .button:active {
            color: #fff;
            background-color: #04c;
            *background-color: #003bb3;

            background-image: none;
            outline: 0;
            -webkit-box-shadow: inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);
            -moz-box-shadow: inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);
        }

        #login-page form {
            margin:15px 0px;
            text-align: center;

        }
        #login-page form > input[type="password"] {
            background-color: #fff;
            border: 1px solid #ccc;
            -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,0.075);
            -moz-box-shadow: inset 0 1px 1px rgba(0,0,0,0.075);
            box-shadow: inset 0 1px 1px rgba(0,0,0,0.075);
            -webkit-transition: border linear .2s,box-shadow linear .2s;
            -moz-transition: border linear .2s,box-shadow linear .2s;
            -o-transition: border linear .2s,box-shadow linear .2s;
            transition: border linear .2s,box-shadow linear .2s;

            display: inline-block;
            width:70%;
            height: 20px;
            padding: 4px 6px;
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 20px;
            color: #555;
            vertical-align: middle;
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
        }
        #login-page form > input[type="password"]:focus {
            border-color: rgba(82,168,236,0.8);
            outline: 0;
            outline: thin dotted \9;
            -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(82,168,236,0.6);
            -moz-box-shadow: inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(82,168,236,0.6);
            box-shadow: inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(82,168,236,0.6);
        }


        <?php
        if ( 'rtl' == $text_direction ) {
            echo 'body { font-family: Tahoma, Arial; }';
        }
        ?>
    </style>
</head>
<body id="login-page">
<?php echo $error_msg; ?>
    <div class="block">
        <img src="<?php echo RSFIREWALL_URL.'assets/images/rsfirewall-icon-48.png'; ?>" alt="<?php echo esc_html__('RSFirewall', 'rsfirewall'); ?>"/>
        <h1><?php echo esc_html__('Please login to continue', 'rsfirewall'); ?></h1>
        <form method="post">
            <input type="password" name="rsf_backend_password" class="input-block"/>
            <br/>
            <button type="submit" class="button"><?php echo esc_html__('Access', 'rsfirewall'); ?></button>
        </form>
    </div>
</body>
</html>