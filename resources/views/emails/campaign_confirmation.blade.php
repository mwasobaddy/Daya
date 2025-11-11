&lt;!DOCTYPE html&gt;
&lt;html lang="en"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt;
    &lt;title&gt;Campaign Confirmation&lt;/title&gt;
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
            &lt;h1&gt;Campaign Submitted Successfully!&lt;/h1&gt;
            &lt;p&gt;Thank you for choosing Daya for your campaign.&lt;/p&gt;
        &lt;/div&gt;

        &lt;div class="content"&gt;
            &lt;h2&gt;Campaign Details&lt;/h2&gt;
            &lt;div class="campaign-details"&gt;
                &lt;p&gt;&lt;strong&gt;Title:&lt;/strong&gt; {{ $campaign-&gt;title }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Description:&lt;/strong&gt; {{ $campaign-&gt;description }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Budget:&lt;/strong&gt; ${{ number_format($campaign-&gt;budget, 2) }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Status:&lt;/strong&gt; {{ ucfirst($campaign-&gt;status) }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;DCD Assigned:&lt;/strong&gt; {{ $dcd-&gt;name }} ({{ $dcd-&gt;email }})&lt;/p&gt;
            &lt;/div&gt;

            &lt;h3&gt;What Happens Next?&lt;/h3&gt;
            &lt;ul&gt;
                &lt;li&gt;Your campaign will be reviewed by our team&lt;/li&gt;
                &lt;li&gt;Once approved, the DCD will begin working on your campaign&lt;/li&gt;
                &lt;li&gt;You'll receive progress updates via email&lt;/li&gt;
                &lt;li&gt;Campaign completion and final results will be shared with you&lt;/li&gt;
            &lt;/ul&gt;

            &lt;p&gt;If you have any questions, please contact our support team.&lt;/p&gt;

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