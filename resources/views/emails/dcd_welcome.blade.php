&lt;!DOCTYPE html&gt;
&lt;html lang="en"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt;
    &lt;title&gt;Welcome to Daya&lt;/title&gt;
    &lt;style&gt;
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .qr-code { text-align: center; margin: 20px 0; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    &lt;/style&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;div class="container"&gt;
        &lt;div class="header"&gt;
            &lt;h1&gt;Welcome to Daya, {{ $user-&gt;name }}!&lt;/h1&gt;
            &lt;p&gt;You're now part of the Daya network. Start earning by scanning campaigns!&lt;/p&gt;
        &lt;/div&gt;

        &lt;div class="content"&gt;
            &lt;h2&gt;Your QR Code&lt;/h2&gt;
            &lt;p&gt;Here's your unique QR code for campaign scanning:&lt;/p&gt;

            &lt;div class="qr-code"&gt;
                @if($qrCodeUrl)
                    &lt;img src="{{ $qrCodeUrl }}" alt="Your QR Code" style="max-width: 200px;" /&gt;
                @else
                    &lt;p&gt;QR Code will be generated shortly.&lt;/p&gt;
                @endif
            &lt;/div&gt;

            &lt;h3&gt;How to Earn&lt;/h3&gt;
            &lt;ul&gt;
                &lt;li&gt;Share your QR code with clients who want to run campaigns&lt;/li&gt;
                &lt;li&gt;When clients scan your QR code, they can submit campaigns&lt;/li&gt;
                &lt;li&gt;You earn commissions on successful campaign completions&lt;/li&gt;
                &lt;li&gt;Track your earnings through monthly reports sent to this email&lt;/li&gt;
            &lt;/ul&gt;

            &lt;p&gt;&lt;strong&gt;Referred by: {{ $referrer-&gt;name }} ({{ $referrer-&gt;email }})&lt;/strong&gt;&lt;/p&gt;
            &lt;p&gt;Thank you for joining through their referral!&lt;/p&gt;

            &lt;p&gt;Start sharing your QR code and building your campaign network today!&lt;/p&gt;

            &lt;p&gt;Best regards,&lt;br&gt;
            The Daya Team&lt;/p&gt;
        &lt;/div&gt;

        &lt;div class="footer"&gt;
            &lt;p&gt;This is an automated message. Please do not reply to this email.&lt;/p&gt;
            &lt;p&gt;© 2024 Daya. All rights reserved.&lt;/p&gt;
        &lt;/div&gt;
    &lt;/div&gt;
&lt;/body&gt;
&lt;/html&gt;