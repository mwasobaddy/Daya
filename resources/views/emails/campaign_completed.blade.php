&lt;!DOCTYPE html&gt;
&lt;html lang="en"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt;
    &lt;title&gt;Campaign Completed&lt;/title&gt;
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
            &lt;h1&gt;Campaign Completed Successfully!&lt;/h1&gt;
            &lt;p&gt;Congratulations on completing this campaign.&lt;/p&gt;
        &lt;/div&gt;

        &lt;div class="content"&gt;
            &lt;h2&gt;Campaign Summary&lt;/h2&gt;
            &lt;div class="campaign-details"&gt;
                &lt;p&gt;&lt;strong&gt;Title:&lt;/strong&gt; {{ $campaign-&gt;title }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Description:&lt;/strong&gt; {{ $campaign-&gt;description }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Budget:&lt;/strong&gt; ${{ number_format($campaign-&gt;budget, 2) }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;{{ $otherUser-&gt;role === 'client' ? 'DCD' : 'Client' }}:&lt;/strong&gt; {{ $otherUser-&gt;name }} ({{ $otherUser-&gt;email }})&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Status:&lt;/strong&gt; {{ ucfirst($campaign-&gt;status) }}&lt;/p&gt;
                &lt;p&gt;&lt;strong&gt;Completed At:&lt;/strong&gt; {{ $campaign-&gt;completed_at?-&gt;format('M j, Y g:i A') }}&lt;/p&gt;
            &lt;/div&gt;

            @if($otherUser-&gt;role === 'client')
                &lt;!-- Email to DCD --&gt;
                &lt;h3&gt;Your Earnings&lt;/h3&gt;
                &lt;p&gt;Congratulations! You have earned ${{ number_format($campaign-&gt;budget * 0.20, 2) }} KeDDS tokens from this campaign completion.&lt;/p&gt;
                &lt;p&gt;Your venture shares have been allocated and will be reflected in your monthly report.&lt;/p&gt;
            @else
                &lt;!-- Email to Client --&gt;
                &lt;h3&gt;Campaign Complete&lt;/h3&gt;
                &lt;p&gt;Your campaign has been successfully completed by our Digital Content Distributor.&lt;/p&gt;
                &lt;p&gt;Thank you for choosing Daya for your campaign needs.&lt;/p&gt;
            @endif

            &lt;p&gt;If you have any questions or need further assistance, please contact our support team.&lt;/p&gt;

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