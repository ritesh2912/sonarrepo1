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
    <title>{{ env('APP_NAME', 'Teacher Cool') }}</title>

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
    <div role="article" aria-roledescription="email" lang="en" style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
        <table role="presentation" style="width:100%;border:none;border-spacing:0;">
            <tr>
                <td align="center" style="padding:0;">

                    <table role="presentation" style="width:94%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:'Basier Circle', 'Roboto', 'Helvetica', 'Arial', sans-serif;font-size:1em;line-height:1.37em;color:#384049;">
                        <!--      Logo headder -->
                        <tr style="background-color: #1670F8;">
                            <td style="padding:18px 30px 18px 24px;font-size:1.5em;font-weight:bold;">
                                <a href="http://dopplerhealth.com/" style="text-decoration:none;">
                                    <img src="{{ url('public/images/logo.svg') }}" alt="">
                                </a>
                            </td>
                        </tr>
                        <tr>
                                <td
                                    style="padding:26px 0 16px;color:#7D36CC;font-size:24px;font-weight:500;line-height: 26px; border-top: 8px solid #3F1272;">
                                    Greetings!</td>
                            </tr>
                            <tr>
                                <td style="padding:26px 0 16px; font-size:22px;font-weight:400;line-height: 26px;">
                                    Copyrights Takedown Request
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    URL of the content:- <b>{{ $reqData['url'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    Another URL of the content:- <b>{{ $reqData['another_url'] ?? 'N/A' }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    Work infringed:- <b>{{ $reqData['work_infringed'] ?? 'N/A' }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:26px 0 16px; font-size:22px;font-weight:400;line-height: 26px;">
                                Requester information
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:26px 0 16px; font-size:22px;font-weight:400;line-height: 26px;">
                                Name of IP Owner
                                </td>
                            </tr>            
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    First Name:- <b>{{ $reqData['first_name'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    Last Name:- <b>{{ $reqData['last_name'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    title:- <b>{{ $reqData['title'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    Company or University Email:- <b>{{ $reqData['university_email'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    Company or University Name:- <b>{{ $reqData['university_name'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    Fax:- <b>{{ $reqData['fax'] ?? 'N/A' }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    Phone:- <b>{{ $reqData['phone'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:26px 0 16px; font-size:22px;font-weight:400;line-height: 26px;">
                                    Address
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    Address Line 1:- <b>{{ $reqData['address_line_1'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                    Address Line 2:- <b>{{ $reqData['address_line_2'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                        City:- <b>{{ $reqData['city'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                        State/Province- <b>{{ $reqData['state'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                        ZIP / Postal - <b>{{ $reqData['zip'] }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    style="color: #333333; padding: 0 40px 0 ;word-break: break-all; font-size:16px; line-height: 26px;">
                                        Country - <b>{{ $reqData['country'] }}</b>
                                </td>
                            </tr>
                        <tr>
                            <td style="padding:30px;text-align:center;font-size: 0.75em;background-color:#1c3669;color:#384049;border-top: 1px solid #eee;">
                                <p style="margin:0 0 0.75em 0;line-height: 0;">
                                    <!--      LinkedIn logo            -->
                                    <a href="#" style="display:inline-block;text-decoration:none;margin: 0 5px;">
                                        <img src="{{ url('public/images/be.svg') }}" alt="" style=" width: 28px; height: 28px;">
                                    </a>
                                    <!--      Facebook logo            -->
                                    <a href="#" style="display:inline-block;text-decoration:none;margin: 0 5px;">
                                        <img src="{{ url('public/images/fb.svg') }}" alt="" style=" width: 28px; height: 28px;">
                                    </a>
                                    <!--     Instagram logo               -->
                                    <a href="#" style="display:inline-block;text-decoration:none;margin: 0 5px;">
                                        <img src="{{ url('public/images/link.svg') }}" alt="" style=" width: 28px; height: 28px;">
                                    </a>
                                </p>
                                <p style="margin:0;font-size:.75rem;line-height:1.5em;text-align: center; color:#FFF;">
                                    {{ env('APP_NAME', 'Teacher Cool') }} - All copyrights reserved 2023
                                    <br>
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
<!-- partial -->