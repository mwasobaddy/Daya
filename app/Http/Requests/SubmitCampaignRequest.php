<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Account Information
            'account_type' => 'required|in:startup,artist,label,ngo,agency,business',
            'business_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:10|regex:/^0[\d\s\-()]{9}$/',
            'country' => 'required|string|max:10',
            'referral_code' => 'nullable|string|max:50',
            'referred_by_code' => 'nullable|string|max:50', // DA referral code
            'dcd_id' => 'nullable|integer|exists:users,id', // For QR code scans

            // Campaign Information
            'campaign_title' => 'required|string|max:255',
            'digital_product_link' => 'required|url|max:500',
            'explainer_video_url' => 'nullable|url|max:500',
            'campaign_objective' => 'required|in:music_promotion,app_downloads,brand_awareness,product_launch,event_promotion,social_cause',
            'budget' => 'required|numeric|min:50',
            'description' => 'nullable|string|max:2000',

            // Targeting & Budget
            'content_safety_preferences' => 'required|array|min:1',
            'content_safety_preferences.*' => 'string|in:kids,teen,adult,no_restrictions',
            'target_country' => 'required|string|max:10',
            'target_county' => 'nullable|string|max:50',
            'target_subcounty' => 'nullable|string|max:50',
            'target_ward' => 'nullable|string|max:50',
            'business_types' => 'required|array|min:1',
            'business_types.*' => 'string|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',

            // Music genres for labels/artists
            'music_genres' => 'required_if:account_type,artist,label|array',
            'music_genres.*' => 'string|max:50',

            // Additional
            'target_audience' => 'nullable|string|max:1000',
            'objectives' => 'nullable|string|max:500',
            'turnstile_token' => ['required', new \App\Rules\TurnstileToken],
        ];
    }
}
