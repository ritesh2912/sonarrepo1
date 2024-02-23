<!-- partial:index.partial.html -->
<!DOCTYPE HTML>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="date=no">
    <meta name="format-detection" content="telephone=no">
    <style type="text/CSS"></style>
    <style @import url('https://dopplerhealth.com/fonts/BasierCircle/basiercircle-regular-webfont.woff2');></style>
    <title></title>
    <style>
        table,
        td,
        div,
        h1,
        p {
            font-family: 'Basier Circle', 'Roboto', 'Helvetica', 'Arial', sans-serif;
        }

        @media screen and (max-width: 530px) {
            .unsub {
                display: block;
                padding: 8px;
                margin-top: 14px;
                border-radius: 6px;

                text-decoration: none !important;
                font-weight: bold;
            }

            .button {
                min-height: 42px;
                line-height: 42px;
            }

            .col-lge {
                max-width: 100% !important;
            }
        }

        @media screen and (min-width: 531px) {
            .col-sml {
                max-width: 27% !important;
            }

            .col-lge {
                max-width: 73% !important;
            }
        }
    </style>
</head>

<body style="margin:0;padding:0;word-spacing:normal;">
    <div role="article" aria-roledescription="email" lang="en"
        style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
        <table role="presentation" style="width:100%;border:none;border-spacing:0;">
            <tr>
                <td align="center" style="padding:0;">

                    <table role="presentation"
                        style="width:94%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:'Basier Circle', 'Roboto', 'Helvetica', 'Arial', sans-serif;font-size:1em;line-height:1.37em;color:#384049;">
                        <!--      Logo headder -->
                        <tr style="background-color: #1670F8;">
                            <td style="padding:18px 30px 18px 24px;font-size:1.5em;font-weight:bold;">
                                <a href="http://dopplerhealth.com/" style="text-decoration:none;">
                                    <img src="{{ url('public/images/logo.svg') }}" alt="">
                                </a>
                            </td>
                        </tr>
                        <!--      Intro Section -->
                        <tr>
                           
                            <td style="padding:30px;background-color:#f5f5f5;">
                                <h1
                                    style="text-align: center; margin-top:0;font-size:1.953em;line-height:1.3;font-weight:bold;letter-spacing:-0.02em;margin-bottom:10;color:#7D36CC;">
                                    Greetings!</h1>
                                <p style="text-align: center; margin:0;">Dear {{$receiver_name}},
                                </p>
                                <p style="text-align: center;">Welcome To Teacher Cool,</p>
                                <p style="text-align: center;">
                                    Please verify your email from the link below:
                                </p>
                                <p style="text-align: center;margin: 2.5em auto;">
                                    <a class="button" href={{ $url }} style="background: #1670F8; 
                       text-decoration: none; 
                       padding: 1em 1.5em;
                       color: #ffffff; 
                       border-radius: 48px;
                       mso-padding-alt:0;
                       text-underline-color:#1670F8">
                                        <i
                                            style="letter-spacing: 25px;mso-font-width:-100%;mso-text-raise:20pt">&nbsp;</i>
                                        <span style="mso-text-raise:10pt;font-weight:bold;">Verify Your Email</span>
                                        <i style="letter-spacing: 25px;mso-font-width:-100%">&nbsp;</i>
                                    </a>
                                </p>
                                <p></p>
                                <p style="margin:0">For any query please contact us on
                                    <b>{{ env('MAIL_FROM_ADDRESS', '') }}</b>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="padding:30px;text-align:center;font-size: 0.75em;background-color:#1c3669;color:#384049;border-top: 1px solid #eee;">
                                <p style="margin:0 0 0.75em 0;line-height: 0;">
                                    <!--      LinkedIn logo            -->
                                    <a href="#" style="display:inline-block;text-decoration:none;margin: 0 5px;">
                                        <img src="{{ url('public/images/be.svg') }}" alt=""
                                            style=" width: 28px; height: 28px;">
                                    </a>
                                    <!--      Facebook logo            -->
                                    <a href="#" style="display:inline-block;text-decoration:none;margin: 0 5px;">
                                        <img src="{{ url('public/images/fb.svg') }}" alt=""
                                            style=" width: 28px; height: 28px;">
                                    </a>
                                    <!--     Instagram logo               -->
                                    <a href="#" style="display:inline-block;text-decoration:none;margin: 0 5px;">
                                        <img src="{{ url('public/images/link.svg') }}" alt=""
                                            style=" width: 28px; height: 28px;">
                                    </a>
                                </p>
                                <p style="margin:0;font-size:.75rem;line-height:1.5em;text-align: center; color:#FFF;">
                                    {{ env('APP_NAME', 'Teacher Cool') }} - All copyrights reserved 2023
                                    <br>
                                    <!-- <a class="unsub" href="#" style="color:#FFF;text-decoration:underline;">Unsubscribe</a> -->
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>


</html>