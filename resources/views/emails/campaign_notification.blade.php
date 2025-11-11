&lt;!DOCTYPE html&gt;
&lt;html lang="en"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt;
    &lt;title&gt;New Campaign Notification&lt;/title&gt;
    &lt;style&gt;
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .campaign-details { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    &lt;/style&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;div class="container"&gt;
        &lt;div class="header"&gt;
            &lt;h1&gt;New Campaign Assigned!&lt;/h1&gt;
            &lt;p&gt;A new campaign has been submitted through your QR code.&lt;/p&gt;
        &lt;/div&gt;

        &lt;div class="content"&gt;
            &lt;h2&gt;Campaign Details&lt;/h2&gt;
            &lt;div class="campaign-details"&gt;
                &lt;p&gt;&lt;strong&gt;Title:&lt;/strong&gt; {{ $campaign-&gt;title }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Description:&lt;/strong&gt; {{ $campaign-&gt;description }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Budget:&lt;/strong&gt; ${{ number_format($campaign-&gt;budget, 2) }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Client:&lt;/strong&gt; {{ $client-&gt;name }} ({{ $client-&gt;email }})&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Status:&lt;/strong&gt; {{ ucfirst($campaign-&gt;status) }}&lt;/p&gt;
            &lt;/div&gt;

            &lt;h3&gt;Next Steps&lt;/h3&gt;
            &lt;ul&gt;
                &lt;li&gt;Review the campaign requirements&lt;/li&gt;
                &lt;li&gt;Contact the client if you need clarification&lt;/li&gt;
                &lt;li&gt;Begin working on the campaign&lt;/li&gt;
                &lt;li&gt;Update campaign status as you progress&lt;/li&gt;
                &lt;li&gt;Submit final results when complete&lt;/li&gt;
            &lt;/ul&gt;

            &lt;p&gt;&lt;strong&gt;Potential Earnings:&lt;/strong&gt; You can earn up to 20% commission on this campaign budget.&lt;/p&gt;

            &lt;p&gt;Good luck with your campaign!&lt;/p&gt;

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