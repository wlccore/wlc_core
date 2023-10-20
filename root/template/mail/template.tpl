<!DOCTYPE html>
<html lang="{{language}}">
<head>
    <meta charset="utf-8">
    <title>{% block title %}{% endblock %}</title>
    
</head>
<body style="margin: 0; padding: 0; color: #333f5e; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
<table class="mail-container" style="border: 0; max-width: 800px; width: 100%; margin: 0 auto;">
    <tbody>
    <tr>
        <td><div class="mail-container-header" style="background: #556275; text-align: center; padding: 20px 0px;">
            <img src="{{img}}/mail/logo-icon.svg" alt="Logo">
        </div></td>
    </tr>
    <tr>
        <td style="padding:40px 0;">
            <table style="width: 100%; border: 0;">
                <tbody>
                <tr>
                    <td>
                        <p style="color: #333f5e; margin: 0 0 36px; text-align: center; font-size: 12px; line-height: 20px;">
                            {% trans %}Add our email to your address book to not miss out on important information from{% endtrans %} {{site_name}}.
                            {% trans %}If this email does not display correctly, open it in your browser.{% endtrans %}
                        </p>
                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%; border: 0;">
                            <tbody>
                            <tr>
                                <td style="background:  url('{{img}}/mail/msg-content-bg.png')">
                                    <p style="font-size: 18px; color: #333f5e; margin: 0; border-top: 1px solid #556275; line-height: 0;">&nbsp;</p>
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100%; border: 0; margin-top: 20px;">
                                        <tbody>
                                        <tr>
                                            <td width="30"><p style="color: #112233; margin: 0; font-size: 1px; line-height: 1px;">&nbsp;</p></td>
                                            <td>{% block content %}{% endblock %}</td>
                                            <td width="30"><p style="color: #112233; margin: 0; font-size: 1px; line-height: 1px;">&nbsp;</p></td>
                                        </tr>
                                        <tr>
                                            <td width="30"><p style="color: #112233; margin: 0; font-size: 1px; line-height: 1px;">&nbsp;</p></td>
                                            <td>
                                                <p style="color: #112233; font-size: 14px; line-height: 22px;">{% trans %}If you have any questions, please contact{% endtrans %} <a target="_blank" href="{{site_support_link}}" style="color: #5aa3e8;">{% trans %}online support{% endtrans %}.</a></p>
                                                <p style="color: #112233; font-size: 14px; font-weight: bold; line-height: 22px;">{% trans %}With respect{% endtrans %}</p>
                                                <p style="color: #112233; font-size: 14px; font-weight: bold; line-height: 22px;">{{site_name}}</p>
                                            </td>
                                            <td width="30"><p style="color: #112233; margin: 0; font-size: 1px; line-height: 1px;">&nbsp;</p></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td><table class="mail-container-footer" style="width: 100%; border: 0; color: #7f8b9a; font-size: 12px; min-height: 80px; background: #556275; border-radius: 8px; margin-bottom: 8px; padding: 12px;">
        <tbody>
        <tr>
            <td width="30%" align="center"><img src="{{img}}/mail/site-logo-full.svg" alt="Logo" width="120"></td>
            <td>
            <p style="color: #f7f7f7; font-size: 12px; line-height: 12px;">{% trans %}casino_mail_info{% endtrans %}</p>
            <p style="color: #f7f7f7; font-size: 12px; line-height: 12px;">{% trans %}Please do not reply to this e-mail. This email is generated automatically and you will not receive a response.{% endtrans %}</p>
            </td>
        </tr>
        </tbody>
        </table>
        <table class="mail-copyright" style="width: 100%; border: 0; color: #7f8b9a; font-size: 12px;">
        <tbody>
        <tr>
            <td>{% trans %}casino_footer_copyright{% endtrans %}</td>
        </tr>
        </tbody>
        </table>
        
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>
