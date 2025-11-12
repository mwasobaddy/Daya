import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CheckCircle, Loader2, User, Briefcase, Target, CheckSquare, ArrowRight, ArrowLeft, Sparkles, Rocket, Shield, XCircle } from 'lucide-react';
import { toast, ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { useState, useEffect, useRef } from 'react';

declare global {
    interface Window {
        turnstile: {
            render: (element: string | HTMLElement, config: { sitekey: string; callback: (token: string) => void }) => void;
            remove: (element: string | HTMLElement) => void;
        };
    }
}

interface Props {
    flash?: {
        success?: string;
        error?: string;
    };
}

interface Country {
    id: number;
    name: string;
    code: string;
    county_label: string;
    subcounty_label: string;
}

interface County {
    id: number;
    name: string;
    country_id: number;
}

interface Subcounty {
    id: number;
    name: string;
    county_id: number;
}

type Step = 'account' | 'campaign' | 'targeting' | 'review';

export default function CampaignSubmit({ flash }: Props) {
    const [currentStep, setCurrentStep] = useState<Step>('account');
    const [countries, setCountries] = useState<Country[]>([]);
    const [countriesLoading, setCountriesLoading] = useState(true);
    const [countyLabel, setCountyLabel] = useState('County');
    const [subcountyLabel, setSubcountyLabel] = useState('Sub-county');
    const [counties, setCountys] = useState<County[]>([]);
    const [countiesLoading, setCountysLoading] = useState(false);
    const [subcounties, setSubcounties] = useState<Subcounty[]>([]);
    const [subcountiesLoading, setSubcountiesLoading] = useState(false);
    const [turnstileToken, setTurnstileToken] = useState<string>('');
    const turnstileRef = useRef<HTMLDivElement>(null);
    const [referralValidating, setReferralValidating] = useState(false);
    const [referralValid, setReferralValid] = useState<boolean | null>(null);
    const [referralMessage, setReferralMessage] = useState('');

    useEffect(() => {
        const fetchCountries = async () => {
            try {
                const response = await fetch('/api/countries');
                const data = await response.json();
                setCountries(data);
            } catch (error) {
                console.error('Failed to fetch countries:', error);
            } finally {
                setCountriesLoading(false);
            }
        };

        fetchCountries();

        // Check if Turnstile script is already loaded
        if (!document.querySelector('script[src="https://challenges.cloudflare.com/turnstile/v0/api.js"]')) {
            const script = document.createElement('script');
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        // Capture the element reference for cleanup
        const turnstileElement = turnstileRef.current;

        // Wait for Turnstile to be available and render the widget
        const renderTurnstile = () => {
            if (window.turnstile && turnstileElement) {
                window.turnstile.render(turnstileElement, {
                    sitekey: '1x00000000000000000000AA',
                    callback: (token: string) => {
                        setTurnstileToken(token);
                    },
                });
            } else {
                // Retry after a short delay if not ready
                setTimeout(renderTurnstile, 100);
            }
        };

        renderTurnstile();

        return () => {
            if (window.turnstile && turnstileElement) {
                window.turnstile.remove(turnstileElement);
            }
        };
    }, []);

    // Extract referral code from URL parameters
    useEffect(() => {
        const extractReferralCode = () => {
            if (typeof window !== 'undefined') {
                const urlParams = new URLSearchParams(window.location.search);
                // Extract referral code from URL parameters (ref, referral, or code)
                const referralCode = urlParams.get('ref') || urlParams.get('referral') || urlParams.get('code');
                if (referralCode && referralCode.length === 6 && /^[A-Za-z0-9]{6}$/.test(referralCode)) {
                    setData('referral_code', referralCode.toUpperCase());
                }
            }
        };

        extractReferralCode();
    }, []);

    const fetchCountys = async (countryId: number) => {
        setCountysLoading(true);
        try {
            const response = await fetch(`/api/counties?country_id=${countryId}`);
            const data = await response.json();
            setCountys(data);
        } catch (error) {
            console.error('Failed to fetch counties:', error);
            setCountys([]);
        } finally {
            setCountysLoading(false);
        }
    };

    const fetchSubcounties = async (countyId: number) => {
        setSubcountiesLoading(true);
        try {
            const response = await fetch(`/api/subcounties?county_id=${countyId}`);
            const data = await response.json();
            setSubcounties(data);
        } catch (error) {
            console.error('Failed to fetch subcounties:', error);
            setSubcounties([]);
        } finally {
            setSubcountiesLoading(false);
        }
    };

    // Update labels based on selected country
    const updateLabels = (countryValue: string) => {
        const selectedCountry = countries.find(country => country.code.toLowerCase() === countryValue);
        if (selectedCountry) {
            setCountyLabel(selectedCountry.county_label);
            setSubcountyLabel(selectedCountry.subcounty_label);
        } else {
            setCountyLabel('County');
            setSubcountyLabel('Sub-county');
        }
    };

    const validateReferralCode = async (code: string) => {
        if (!code || code.length !== 6) {
            setReferralValid(null);
            setReferralMessage('');
            return;
        }

        setReferralValidating(true);
        try {
            const response = await fetch('/api/validate-referral', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({ referral_code: code.toUpperCase() }),
            });

            const result = await response.json();

            if (response.ok) {
                setReferralValid(true);
                setReferralMessage(`Valid referral code from ${result.referrer.name}`);
            } else {
                setReferralValid(false);
                setReferralMessage(result.message || 'Invalid referral code');
            }
        } catch (error) {
            setReferralValid(false);
            setReferralMessage('Failed to validate referral code');
        } finally {
            setReferralValidating(false);
        }
    };

    const { data, setData, processing, errors, reset } = useForm({
        account_type: '',
        business_name: '',
        name: '',
        email: '',
        phone: '',
        country: '',
        referral_code: '',
        campaign_title: '',
        digital_product_link: '',
        explainer_video_url: '',
        campaign_objective: '',
        budget: '',
        content_safety: '',
        target_country: '',
        target_county: '',
        target_subcounty: '',
        business_types: [] as string[],
        start_date: '',
        end_date: '',
        description: '',
        target_audience: '',
        objectives: '',
    });

    const steps = [
        { id: 'account', title: 'Account Setup', icon: User, description: 'Create your client profile', color: 'from-blue-500 to-cyan-500' },
        { id: 'campaign', title: 'Campaign Details', icon: Briefcase, description: 'Basic campaign information', color: 'from-purple-500 to-pink-500' },
        { id: 'targeting', title: 'Targeting & Budget', icon: Target, description: 'Define your audience and budget', color: 'from-orange-500 to-red-500' },
        { id: 'review', title: 'Review & Submit', icon: CheckSquare, description: 'Review and launch campaign', color: 'from-green-500 to-emerald-500' },
    ];

    const currentStepIndex = steps.findIndex(step => step.id === currentStep);

    // Validate referral code when it changes
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            validateReferralCode(data.referral_code);
        }, 500); // Debounce validation

        return () => clearTimeout(timeoutId);
    }, [data.referral_code]);

    // Validation functions
    const validateEmail = (email: string): boolean => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    const validatePhone = (phone: string): boolean => {
        const phoneRegex = /^\+?[\d\s\-()]{10,}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    };

    const validateUrl = (url: string): boolean => {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    };

    const validateStep = (step: Step): boolean => {
        const newErrors: Record<string, string> = {};
        let isValid = true;

        if (step === 'account') {
            if (!data.account_type) {
                newErrors.account_type = 'Account type is required';
                isValid = false;
            }
            if (!data.business_name.trim()) {
                newErrors.business_name = 'Business/organization name is required';
                isValid = false;
            }
            if (!data.name.trim()) {
                newErrors.name = 'Contact person name is required';
                isValid = false;
            }
            if (!data.email.trim()) {
                newErrors.email = 'Email address is required';
                isValid = false;
            } else if (!validateEmail(data.email)) {
                newErrors.email = 'Please enter a valid email address';
                isValid = false;
            }
            if (!data.phone.trim()) {
                newErrors.phone = 'Phone number is required';
                isValid = false;
            } else if (!validatePhone(data.phone)) {
                newErrors.phone = 'Please enter a valid phone number';
                isValid = false;
            }
            if (!data.country) {
                newErrors.country = 'Country selection is required';
                isValid = false;
            }
        } else if (step === 'campaign') {
            if (!data.campaign_title.trim()) {
                newErrors.campaign_title = 'Campaign title is required';
                isValid = false;
            }
            if (!data.digital_product_link.trim()) {
                newErrors.digital_product_link = 'Digital product link is required';
                isValid = false;
            } else if (!validateUrl(data.digital_product_link)) {
                newErrors.digital_product_link = 'Please enter a valid URL';
                isValid = false;
            }
            if (data.explainer_video_url && !validateUrl(data.explainer_video_url)) {
                newErrors.explainer_video_url = 'Please enter a valid URL';
                isValid = false;
            }
            if (!data.campaign_objective) {
                newErrors.campaign_objective = 'Campaign objective is required';
                isValid = false;
            }
            if (!data.budget || parseFloat(data.budget) <= 0) {
                newErrors.budget = 'Budget must be greater than $0';
                isValid = false;
            }
            if (!data.description.trim()) {
                newErrors.description = 'Campaign description is required';
                isValid = false;
            }
        } else if (step === 'targeting') {
            if (!data.budget || parseFloat(data.budget) < 50) {
                newErrors.budget = 'Minimum budget is $50';
                isValid = false;
            }
            if (!data.content_safety) {
                newErrors.content_safety = 'Content safety preference is required';
                isValid = false;
            }
            if (!data.target_country) {
                newErrors.target_country = 'Target country is required';
                isValid = false;
            }
            if (!data.target_county) {
                newErrors.target_county = 'Target county is required';
                isValid = false;
            }
            if (!data.target_subcounty) {
                newErrors.target_subcounty = 'Target sub-county is required';
                isValid = false;
            }
            if (data.business_types.length === 0) {
                newErrors.business_types = 'Please select at least one business type';
                isValid = false;
            }
            if (!data.start_date) {
                newErrors.start_date = 'Start date is required';
                isValid = false;
            } else {
                const startDate = new Date(data.start_date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (startDate < today) {
                    newErrors.start_date = 'Start date cannot be in the past';
                    isValid = false;
                }
            }
            if (!data.end_date) {
                newErrors.end_date = 'End date is required';
                isValid = false;
            } else if (data.start_date && new Date(data.end_date) <= new Date(data.start_date)) {
                newErrors.end_date = 'End date must be after start date';
                isValid = false;
            }
        }

        return isValid;
    };

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (currentStep === 'review') {
            if (!turnstileToken) {
                toast.error('Please complete the security verification first.');
                return;
            }

            try {
                const response = await fetch('/api/client/campaign/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        ...data,
                        turnstile_token: turnstileToken,
                    }),
                });

                const result = await response.json();

                if (response.ok) {
                    console.log('Campaign submission successful:', result);

                    // Clear form and show success message
                    reset();
                    setCurrentStep('account');
                    setTurnstileToken('');

                    // Show success toast
                    toast.success(result.message || 'Campaign submitted successfully!');
                } else if (response.status === 409) {
                    // User already has an active campaign
                    console.log('User has existing active campaign:', result);
                    toast.error(result.message || 'You already have an active campaign. Check your email for details.');
                } else {
                    console.error('Campaign submission error:', result);

                    // Handle validation errors
                    if (result.errors && typeof result.errors === 'object') {
                        const formattedErrors: Record<string, string> = {};
                        Object.entries(result.errors).forEach(([key, messages]) => {
                            formattedErrors[key] = Array.isArray(messages) ? messages[0] : messages;
                        });
                        toast.error('Please check the form for validation errors and try again.');
                    } else {
                        // Handle general errors
                        const errorMessage = result.message || 'Failed to submit campaign. Please try again.';
                        toast.error(errorMessage);
                    }
                }
            } catch (error) {
                console.error('Network error:', error);
                toast.error('Network error. Please check your connection and try again.');
            }
        } else {
            // Validate current step before proceeding
            if (validateStep(currentStep)) {
                nextStep();
            }
        }
    };

    const nextStep = () => {
        const nextIndex = currentStepIndex + 1;
        if (nextIndex < steps.length) {
            setCurrentStep(steps[nextIndex].id as Step);
        }
    };

    const prevStep = () => {
        const prevIndex = currentStepIndex - 1;
        if (prevIndex >= 0) {
            setCurrentStep(steps[prevIndex].id as Step);
        }
    };

    const renderStepContent = () => {
        switch (currentStep) {
            case 'account':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-blue-50 to-cyan-50 p-6 rounded-xl border-2 border-blue-100">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2 bg-blue-500 rounded-lg">
                                    <User className="w-5 h-5 text-white" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900">Let's Get Started</h3>
                            </div>
                            <p className="text-sm text-gray-600">Set up your account to begin your campaign journey with us</p>
                        </div>

                        <div className="space-y-5">
                            <div className="group">
                                <Label htmlFor="account_type" className="text-sm font-medium text-gray-700 mb-2 block">Account Type *</Label>
                                <Select value={data.account_type} onValueChange={(value) => setData('account_type', value)}>
                                    <SelectTrigger className="transition-all duration-200 hover:border-blue-400 focus:ring-2 focus:ring-blue-500">
                                        <SelectValue placeholder="Select account type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="startup">🚀 Startup</SelectItem>
                                        <SelectItem value="artist">🎨 Artist</SelectItem>
                                        <SelectItem value="label">🎵 Label</SelectItem>
                                        <SelectItem value="ngo">🤝 NGO</SelectItem>
                                        <SelectItem value="agency">💼 Agency</SelectItem>
                                        <SelectItem value="business">🏢 Business</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.account_type && (
                                    <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.account_type}</p>
                                )}
                            </div>

                            <div className="group">
                                <Label htmlFor="business_name" className="text-sm font-medium text-gray-700 mb-2 block">Business/Organization Name *</Label>
                                <Input
                                    id="business_name"
                                    type="text"
                                    value={data.business_name}
                                    onChange={(e) => setData('business_name', e.target.value)}
                                    required
                                    placeholder="Enter your business or organization name"
                                    className="transition-all duration-200 hover:border-blue-400 focus:ring-2 focus:ring-blue-500"
                                />
                                {errors.business_name && (
                                    <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.business_name}</p>
                                )}
                            </div>

                            <div className="group">
                                <Label htmlFor="name" className="text-sm font-medium text-gray-700 mb-2 block">Contact Person Name *</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    placeholder="Enter contact person full name"
                                    className="transition-all duration-200 hover:border-blue-400 focus:ring-2 focus:ring-blue-500"
                                />
                                {errors.name && (
                                    <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.name}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div className="group">
                                    <Label htmlFor="email" className="text-sm font-medium text-gray-700 mb-2 block">Email Address *</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                        placeholder="your.email@example.com"
                                        className="transition-all duration-200 hover:border-blue-400 focus:ring-2 focus:ring-blue-500"
                                    />
                                    {errors.email && (
                                        <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.email}</p>
                                    )}
                                </div>

                                <div className="group">
                                    <Label htmlFor="phone" className="text-sm font-medium text-gray-700 mb-2 block">Phone Number *</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        required
                                        placeholder="+234 xxx xxx xxxx"
                                        className="transition-all duration-200 hover:border-blue-400 focus:ring-2 focus:ring-blue-500"
                                    />
                                    {errors.phone && (
                                        <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.phone}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div className="group">
                                    <Label htmlFor="country" className="text-sm font-medium text-gray-700 mb-2 block">Country *</Label>
                                    <Select value={data.country} onValueChange={(value) => {
                                        setData('country', value);
                                        updateLabels(value);
                                    }} disabled={countriesLoading}>
                                        <SelectTrigger className="transition-all duration-200 hover:border-blue-400 focus:ring-2 focus:ring-blue-500">
                                            <SelectValue placeholder={countriesLoading ? "Loading countries..." : "Select your country"} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {countries.map((country) => (
                                                <SelectItem key={country.id} value={country.code.toLowerCase()}>
                                                    {country.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.country && (
                                        <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.country}</p>
                                    )}
                                </div>

                                <div className="group">
                                    <Label htmlFor="referral_code" className="text-sm font-medium text-gray-700 mb-2 block">Referral Code (Optional)</Label>
                                    <Input
                                        id="referral_code"
                                        type="text"
                                        value={data.referral_code}
                                        onChange={(e) => setData('referral_code', e.target.value)}
                                        placeholder="Enter referral code if applicable"
                                        className="transition-all duration-200 hover:border-blue-400 focus:ring-2 focus:ring-blue-500"
                                    />
                                    <p className="mt-1.5 text-xs text-gray-500">
                                        If you were referred by a Digital Ambassador
                                    </p>
                                    {data.referral_code && (
                                        <div className="mt-2 flex items-center space-x-2">
                                            {referralValidating ? (
                                                <div className="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 dark:border-blue-400"></div>
                                                    <span className="text-sm">Validating...</span>
                                                </div>
                                            ) : referralValid === true ? (
                                                <div className="flex items-center space-x-2 text-green-600 dark:text-green-400">
                                                    <CheckCircle className="h-4 w-4" />
                                                    <span className="text-sm">{referralMessage}</span>
                                                </div>
                                            ) : referralValid === false ? (
                                                <div className="flex items-center space-x-2 text-red-600 dark:text-red-400">
                                                    <XCircle className="h-4 w-4" />
                                                    <span className="text-sm">{referralMessage}</span>
                                                </div>
                                            ) : null}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                );

            case 'campaign':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-purple-50 to-pink-50 p-6 rounded-xl border-2 border-purple-100">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg">
                                    <Briefcase className="w-5 h-5 text-white" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900">Campaign Information</h3>
                            </div>
                            <p className="text-sm text-gray-600">Tell us about your campaign goals and objectives</p>
                        </div>

                        <div className="space-y-5">
                            <div className="group">
                                <Label htmlFor="campaign_title" className="text-sm font-medium text-gray-700 mb-2 block">Campaign Title *</Label>
                                <Input
                                    id="campaign_title"
                                    type="text"
                                    value={data.campaign_title}
                                    onChange={(e) => setData('campaign_title', e.target.value)}
                                    required
                                    placeholder="Enter a compelling campaign title"
                                    className="transition-all duration-200 hover:border-purple-400 focus:ring-2 focus:ring-purple-500"
                                />
                                {errors.campaign_title && (
                                    <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.campaign_title}</p>
                                )}
                            </div>

                            <div className="group">
                                <Label htmlFor="digital_product_link" className="text-sm font-medium text-gray-700 mb-2 block">Digital Product Link *</Label>
                                <Input
                                    id="digital_product_link"
                                    type="url"
                                    value={data.digital_product_link}
                                    onChange={(e) => setData('digital_product_link', e.target.value)}
                                    required
                                    placeholder="https://your-product-link.com"
                                    className="transition-all duration-200 hover:border-purple-400 focus:ring-2 focus:ring-purple-500"
                                />
                                {errors.digital_product_link && (
                                    <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.digital_product_link}</p>
                                )}
                                <p className="mt-1.5 text-xs text-gray-500">
                                    Link to your digital product, app, or content
                                </p>
                            </div>

                            <div className="group">
                                <Label htmlFor="explainer_video_url" className="text-sm font-medium text-gray-700 mb-2 block">Explainer Video URL (Optional)</Label>
                                <Input
                                    id="explainer_video_url"
                                    type="url"
                                    value={data.explainer_video_url}
                                    onChange={(e) => setData('explainer_video_url', e.target.value)}
                                    placeholder="https://youtube.com/watch?v=..."
                                    className="transition-all duration-200 hover:border-purple-400 focus:ring-2 focus:ring-purple-500"
                                />
                                {errors.explainer_video_url && (
                                    <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.explainer_video_url}</p>
                                )}
                                <p className="mt-1.5 text-xs text-gray-500">
                                    YouTube, Vimeo, or other video platform link
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div className="group">
                                    <Label htmlFor="campaign_objective" className="text-sm font-medium text-gray-700 mb-2 block">Campaign Objective *</Label>
                                    <Select value={data.campaign_objective} onValueChange={(value) => setData('campaign_objective', value)}>
                                        <SelectTrigger className="transition-all duration-200 hover:border-purple-400 focus:ring-2 focus:ring-purple-500">
                                            <SelectValue placeholder="Select campaign objective" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="music_promotion">🎵 Music Promotion</SelectItem>
                                            <SelectItem value="app_downloads">📱 App Downloads</SelectItem>
                                            <SelectItem value="brand_awareness">📢 Brand Awareness</SelectItem>
                                            <SelectItem value="product_launch">🚀 Product Launch</SelectItem>
                                            <SelectItem value="event_promotion">🎉 Event Promotion</SelectItem>
                                            <SelectItem value="social_cause">❤️ Social Cause</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.campaign_objective && (
                                        <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.campaign_objective}</p>
                                    )}
                                </div>

                                <div className="group">
                                    <Label htmlFor="budget" className="text-sm font-medium text-gray-700 mb-2 block">Budget ($) *</Label>
                                    <Input
                                        id="budget"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.budget}
                                        onChange={(e) => setData('budget', e.target.value)}
                                        required
                                        placeholder="Enter campaign budget"
                                        className="transition-all duration-200 hover:border-purple-400 focus:ring-2 focus:ring-purple-500"
                                    />
                                    {errors.budget && (
                                        <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.budget}</p>
                                    )}
                                </div>
                            </div>

                            <div className="group">
                                <Label htmlFor="description" className="text-sm font-medium text-gray-700 mb-2 block">Campaign Description *</Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('description', e.target.value)}
                                    required
                                    placeholder="Describe your campaign objectives, target audience, and specific requirements..."
                                    rows={4}
                                    className="flex min-h-[80px] w-full rounded-lg border border-input bg-background px-4 py-3 text-sm ring-offset-background placeholder:text-muted-foreground transition-all duration-200 hover:border-purple-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                {errors.description && (
                                    <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.description}</p>
                                )}
                            </div>
                        </div>

                        <div className="bg-gradient-to-br from-blue-50 to-cyan-50 p-5 rounded-xl border-2 border-blue-100">
                            <div className="flex items-start gap-3">
                                <Sparkles className="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                                <div>
                                    <h4 className="font-semibold text-blue-900 mb-2">How it works:</h4>
                                    <ul className="text-sm text-blue-800 space-y-2">
                                        <li className="flex items-start gap-2">
                                            <span className="text-blue-600 font-bold mt-0.5">•</span>
                                            <span>Find a DCD and get their QR code</span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <span className="text-blue-600 font-bold mt-0.5">•</span>
                                            <span>Submit your campaign details</span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <span className="text-blue-600 font-bold mt-0.5">•</span>
                                            <span>DCD will handle campaign execution</span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <span className="text-blue-600 font-bold mt-0.5">•</span>
                                            <span>Track progress via email updates</span>
                                        </li>
                                        <li className="flex items-start gap-2">
                                            <span className="text-blue-600 font-bold mt-0.5">•</span>
                                            <span>Earn from successful campaign outcomes</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                );

            case 'targeting':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-orange-50 to-red-50 p-6 rounded-xl border-2 border-orange-100">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg">
                                    <Target className="w-5 h-5 text-white" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900">Target Your Audience</h3>
                            </div>
                            <p className="text-sm text-gray-600">Define who you want to reach and set your budget</p>
                        </div>

                        <div className="space-y-6">
                            <div className="group">
                                <Label htmlFor="budget" className="text-sm font-medium text-gray-700 mb-2 block">Budget ($) *</Label>
                                <Input
                                    id="budget"
                                    type="number"
                                    min="50"
                                    step="10"
                                    value={data.budget}
                                    onChange={(e) => setData('budget', e.target.value)}
                                    required
                                    placeholder="Enter campaign budget"
                                    className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500"
                                />
                                <p className="mt-1.5 text-xs text-gray-500">
                                    1 Credit = $1 = 10 verified clicks/scans
                                </p>
                                {errors.budget && (
                                    <p className="mt-1.5 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.budget}</p>
                                )}
                            </div>

                            <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                                <Label className="text-sm font-medium text-gray-700 mb-3 block">Content Safety Preference *</Label>
                                <div className="space-y-3">
                                    <div className="relative">
                                        <input
                                            type="radio"
                                            id="safety_family"
                                            name="content_safety"
                                            value="family_friendly"
                                            checked={data.content_safety === 'family_friendly'}
                                            onChange={(e) => setData('content_safety', e.target.value)}
                                            className="peer sr-only"
                                        />
                                        <Label 
                                            htmlFor="safety_family" 
                                            className="flex items-center p-4 rounded-lg border-2 border-gray-200 cursor-pointer transition-all peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300"
                                        >
                                            <div className="flex-1">
                                                <div className="font-medium text-gray-900">👨‍👩‍👧‍👦 Family Friendly</div>
                                                <div className="text-sm text-gray-500">Content suitable for all ages</div>
                                            </div>
                                            <div className="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-green-500 peer-checked:bg-green-500 flex items-center justify-center">
                                                {data.content_safety === 'family_friendly' && <div className="w-2 h-2 rounded-full bg-white"></div>}
                                            </div>
                                        </Label>
                                    </div>
                                    <div className="relative">
                                        <input
                                            type="radio"
                                            id="safety_mature"
                                            name="content_safety"
                                            value="mature_audience"
                                            checked={data.content_safety === 'mature_audience'}
                                            onChange={(e) => setData('content_safety', e.target.value)}
                                            className="peer sr-only"
                                        />
                                        <Label 
                                            htmlFor="safety_mature" 
                                            className="flex items-center p-4 rounded-lg border-2 border-gray-200 cursor-pointer transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50 hover:border-purple-300"
                                        >
                                            <div className="flex-1">
                                                <div className="font-medium text-gray-900">🔞 Mature Audience</div>
                                                <div className="text-sm text-gray-500">Content for adults only</div>
                                            </div>
                                            <div className="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-purple-500 peer-checked:bg-purple-500 flex items-center justify-center">
                                                {data.content_safety === 'mature_audience' && <div className="w-2 h-2 rounded-full bg-white"></div>}
                                            </div>
                                        </Label>
                                    </div>
                                </div>
                                <p className="mt-3 text-xs text-gray-500">
                                    Help us match your content with appropriate Digital Content Distributors
                                </p>
                            </div>

                            <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                                <Label className="text-sm font-medium text-gray-700 mb-3 block">Target Counties *</Label>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <Label htmlFor="target_country" className="text-xs text-gray-600 mb-1.5 block">Country</Label>
                                        <Select
                                            value={data.target_country}
                                            onValueChange={(value) => {
                                                setData('target_country', value);
                                                updateLabels(value);
                                                setData('target_county', '');
                                                setData('target_subcounty', '');
                                                setCountys([]);
                                                setSubcounties([]);
                                                const selectedCountry = countries.find(c => c.code.toLowerCase() === value);
                                                if (selectedCountry) {
                                                    fetchCountys(selectedCountry.id);
                                                }
                                            }}
                                            disabled={countriesLoading}
                                        >
                                            <SelectTrigger className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500">
                                                <SelectValue placeholder={countriesLoading ? "Loading..." : "Select country"} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {countries.map((country) => (
                                                    <SelectItem key={country.id} value={country.code.toLowerCase()}>
                                                        {country.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label htmlFor="target_county" className="text-xs text-gray-600 mb-1.5 block">{countyLabel}</Label>
                                        <Select
                                            value={data.target_county}
                                            onValueChange={(value) => {
                                                setData('target_county', value);
                                                setData('target_subcounty', '');
                                                setSubcounties([]);
                                                const selectedCounty = counties.find(r => r.id.toString() === value);
                                                if (selectedCounty) {
                                                    fetchSubcounties(selectedCounty.id);
                                                }
                                            }}
                                            disabled={countiesLoading || !data.target_country}
                                        >
                                            <SelectTrigger className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500">
                                                <SelectValue placeholder={countiesLoading ? "Loading..." : `Select ${countyLabel.toLowerCase()}`} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {counties.map((county) => (
                                                    <SelectItem key={county.id} value={county.id.toString()}>
                                                        {county.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label htmlFor="target_subcounty" className="text-xs text-gray-600 mb-1.5 block">{subcountyLabel}</Label>
                                        <Select
                                            value={data.target_subcounty}
                                            onValueChange={(value) => setData('target_subcounty', value)}
                                            disabled={subcountiesLoading || !data.target_county}
                                        >
                                            <SelectTrigger className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500">
                                                <SelectValue placeholder={subcountiesLoading ? "Loading..." : `Select ${subcountyLabel.toLowerCase()}`} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {subcounties.map((subcounty) => (
                                                    <SelectItem key={subcounty.id} value={subcounty.id.toString()}>
                                                        {subcounty.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                                <Label className="text-sm font-medium text-gray-700 mb-3 block">Business Type Targeting *</Label>
                                <div className="max-h-96 overflow-y-auto">
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                        <div className="bg-gradient-to-br from-blue-50 to-blue-100/50 p-4 rounded-lg">
                                            <h4 className="font-semibold text-sm mb-3 text-blue-900 flex items-center gap-2">
                                                <span className="text-lg">🏪</span> Retail & Merchant
                                            </h4>
                                            <div className="space-y-2.5">
                                                {['kiosk_duka', 'mini_supermarket', 'wholesale_shop', 'hardware_store', 'agrovet', 'butchery', 'boutique', 'electronics', 'stationery', 'general_store'].map((type) => (
                                                    <div key={type} className="flex items-center space-x-2">
                                                        <input
                                                            type="checkbox"
                                                            id={`business_${type}`}
                                                            checked={data.business_types.includes(type)}
                                                            onChange={(e) => {
                                                                const current = data.business_types;
                                                                if (e.target.checked) {
                                                                    setData('business_types', [...current, type]);
                                                                } else {
                                                                    setData('business_types', current.filter(t => t !== type));
                                                                }
                                                            }}
                                                            className="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-2 focus:ring-blue-500"
                                                        />
                                                        <Label htmlFor={`business_${type}`} className="text-sm text-gray-700 cursor-pointer">
                                                            {type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                        </Label>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="bg-gradient-to-br from-purple-50 to-purple-100/50 p-4 rounded-lg">
                                            <h4 className="font-semibold text-sm mb-3 text-purple-900 flex items-center gap-2">
                                                <span className="text-lg">💈</span> Services & Care
                                            </h4>
                                            <div className="space-y-2.5">
                                                {['salon', 'barber_shop', 'beauty_parlour', 'tailor', 'shoe_repair', 'photography_studio', 'printing_cyber', 'laundry'].map((type) => (
                                                    <div key={type} className="flex items-center space-x-2">
                                                        <input
                                                            type="checkbox"
                                                            id={`business_${type}`}
                                                            checked={data.business_types.includes(type)}
                                                            onChange={(e) => {
                                                                const current = data.business_types;
                                                                if (e.target.checked) {
                                                                    setData('business_types', [...current, type]);
                                                                } else {
                                                                    setData('business_types', current.filter(t => t !== type));
                                                                }
                                                            }}
                                                            className="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-2 focus:ring-purple-500"
                                                        />
                                                        <Label htmlFor={`business_${type}`} className="text-sm text-gray-700 cursor-pointer">
                                                            {type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                        </Label>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="bg-gradient-to-br from-orange-50 to-orange-100/50 p-4 rounded-lg">
                                            <h4 className="font-semibold text-sm mb-3 text-orange-900 flex items-center gap-2">
                                                <span className="text-lg">🍴</span> Food & Beverage
                                            </h4>
                                            <div className="space-y-2.5">
                                                {['cafe', 'restaurant', 'fast_food', 'mama_mboga', 'milk_atm', 'bakery'].map((type) => (
                                                    <div key={type} className="flex items-center space-x-2">
                                                        <input
                                                            type="checkbox"
                                                            id={`business_${type}`}
                                                            checked={data.business_types.includes(type)}
                                                            onChange={(e) => {
                                                                const current = data.business_types;
                                                                if (e.target.checked) {
                                                                    setData('business_types', [...current, type]);
                                                                } else {
                                                                    setData('business_types', current.filter(t => t !== type));
                                                                }
                                                            }}
                                                            className="w-4 h-4 text-orange-600 rounded border-gray-300 focus:ring-2 focus:ring-orange-500"
                                                        />
                                                        <Label htmlFor={`business_${type}`} className="text-sm text-gray-700 cursor-pointer">
                                                            {type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                        </Label>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="bg-gradient-to-br from-green-50 to-green-100/50 p-4 rounded-lg">
                                            <h4 className="font-semibold text-sm mb-3 text-green-900 flex items-center gap-2">
                                                <span className="text-lg">💰</span> Financial Services
                                            </h4>
                                            <div className="space-y-2.5">
                                                {['mobile_money', 'bank_agent', 'bill_payment', 'betting_shop'].map((type) => (
                                                    <div key={type} className="flex items-center space-x-2">
                                                        <input
                                                            type="checkbox"
                                                            id={`business_${type}`}
                                                            checked={data.business_types.includes(type)}
                                                            onChange={(e) => {
                                                                const current = data.business_types;
                                                                if (e.target.checked) {
                                                                    setData('business_types', [...current, type]);
                                                                } else {
                                                                    setData('business_types', current.filter(t => t !== type));
                                                                }
                                                            }}
                                                            className="w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-2 focus:ring-green-500"
                                                        />
                                                        <Label htmlFor={`business_${type}`} className="text-sm text-gray-700 cursor-pointer">
                                                            {type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                        </Label>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="bg-gradient-to-br from-yellow-50 to-yellow-100/50 p-4 rounded-lg">
                                            <h4 className="font-semibold text-sm mb-3 text-yellow-900 flex items-center gap-2">
                                                <span className="text-lg">🚖</span> Transport
                                            </h4>
                                            <div className="space-y-2.5">
                                                {['boda_boda', 'matatu_sacco', 'fuel_station', 'car_wash'].map((type) => (
                                                    <div key={type} className="flex items-center space-x-2">
                                                        <input
                                                            type="checkbox"
                                                            id={`business_${type}`}
                                                            checked={data.business_types.includes(type)}
                                                            onChange={(e) => {
                                                                const current = data.business_types;
                                                                if (e.target.checked) {
                                                                    setData('business_types', [...current, type]);
                                                                } else {
                                                                    setData('business_types', current.filter(t => t !== type));
                                                                }
                                                            }}
                                                            className="w-4 h-4 text-yellow-600 rounded border-gray-300 focus:ring-2 focus:ring-yellow-500"
                                                        />
                                                        <Label htmlFor={`business_${type}`} className="text-sm text-gray-700 cursor-pointer">
                                                            {type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                        </Label>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="bg-gradient-to-br from-red-50 to-red-100/50 p-4 rounded-lg">
                                            <h4 className="font-semibold text-sm mb-3 text-red-900 flex items-center gap-2">
                                                <span className="text-lg">🏠</span> Community
                                            </h4>
                                            <div className="space-y-2.5">
                                                {['church', 'school_canteen', 'bar_lounge', 'pharmacy', 'clinic', 'other'].map((type) => (
                                                    <div key={type} className="flex items-center space-x-2">
                                                        <input
                                                            type="checkbox"
                                                            id={`business_${type}`}
                                                            checked={data.business_types.includes(type)}
                                                            onChange={(e) => {
                                                                const current = data.business_types;
                                                                if (e.target.checked) {
                                                                    setData('business_types', [...current, type]);
                                                                } else {
                                                                    setData('business_types', current.filter(t => t !== type));
                                                                }
                                                            }}
                                                            className="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-2 focus:ring-red-500"
                                                        />
                                                        <Label htmlFor={`business_${type}`} className="text-sm text-gray-700 cursor-pointer">
                                                            {type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                        </Label>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {errors.business_types && (
                                    <p className="mt-2 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.business_types}</p>
                                )}
                            </div>

                            <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                                <Label className="text-sm font-medium text-gray-700 mb-3 block">Campaign Duration *</Label>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="start_date" className="text-xs text-gray-600 mb-1.5 block">Start Date</Label>
                                        <Input
                                            id="start_date"
                                            type="date"
                                            value={data.start_date}
                                            onChange={(e) => setData('start_date', e.target.value)}
                                            required
                                            min={new Date().toISOString().split('T')[0]}
                                            className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="end_date" className="text-xs text-gray-600 mb-1.5 block">End Date</Label>
                                        <Input
                                            id="end_date"
                                            type="date"
                                            value={data.end_date}
                                            onChange={(e) => setData('end_date', e.target.value)}
                                            required
                                            min={data.start_date || new Date().toISOString().split('T')[0]}
                                            className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div className="group">
                                    <Label htmlFor="target_audience" className="text-sm font-medium text-gray-700 mb-2 block">Target Audience</Label>
                                    <textarea
                                        id="target_audience"
                                        value={data.target_audience}
                                        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('target_audience', e.target.value)}
                                        placeholder="Describe your target audience (age, gender, interests, etc.)"
                                        rows={3}
                                        className="flex min-h-[60px] w-full rounded-lg border border-input bg-background px-4 py-3 text-sm ring-offset-background placeholder:text-muted-foreground transition-all duration-200 hover:border-orange-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                </div>

                                <div className="group">
                                    <Label htmlFor="objectives" className="text-sm font-medium text-gray-700 mb-2 block">Key Objectives</Label>
                                    <Input
                                        id="objectives"
                                        type="text"
                                        value={data.objectives}
                                        onChange={(e) => setData('objectives', e.target.value)}
                                        placeholder="e.g., Increase sales by 30%, Brand awareness"
                                        className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500"
                                    />
                                </div>
                            </div>

                            <div className="bg-gradient-to-br from-blue-50 to-cyan-50 p-5 rounded-xl border-2 border-blue-100">
                                <div className="flex items-start gap-3">
                                    <Sparkles className="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <h4 className="font-semibold text-blue-900 mb-2">💡 Campaign Tips</h4>
                                        <ul className="text-sm text-blue-800 space-y-2">
                                            <li className="flex items-start gap-2">
                                                <span className="text-blue-600 font-bold mt-0.5">•</span>
                                                <span>Be specific about your target audience demographics</span>
                                            </li>
                                            <li className="flex items-start gap-2">
                                                <span className="text-blue-600 font-bold mt-0.5">•</span>
                                                <span>Define clear, measurable objectives</span>
                                            </li>
                                            <li className="flex items-start gap-2">
                                                <span className="text-blue-600 font-bold mt-0.5">•</span>
                                                <span>Consider your budget allocation across different channels</span>
                                            </li>
                                            <li className="flex items-start gap-2">
                                                <span className="text-blue-600 font-bold mt-0.5">•</span>
                                                <span>Geographic targeting helps reach the right locations</span>
                                            </li>
                                            <li className="flex items-start gap-2">
                                                <span className="text-blue-600 font-bold mt-0.5">•</span>
                                                <span>Business type selection ensures your content reaches relevant outlets</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                );

            case 'review':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-green-50 to-emerald-50 p-6 rounded-xl border-2 border-green-100">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg">
                                    <CheckSquare className="w-5 h-5 text-white" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900">Review Your Campaign</h3>
                            </div>
                            <p className="text-sm text-gray-600">Double-check everything before launching</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                                <h3 className="font-semibold text-base mb-4 flex items-center gap-2 text-gray-900">
                                    <User className="w-4 h-4 text-blue-600" />
                                    Account Information
                                </h3>
                                <div className="space-y-3 text-sm">
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Type:</span>
                                        <span className="font-medium text-gray-900">{data.account_type}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Business:</span>
                                        <span className="font-medium text-gray-900">{data.business_name}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Contact:</span>
                                        <span className="font-medium text-gray-900">{data.name}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Email:</span>
                                        <span className="font-medium text-gray-900 break-all">{data.email}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Phone:</span>
                                        <span className="font-medium text-gray-900">{data.phone}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Country:</span>
                                        <span className="font-medium text-gray-900">{data.country}</span>
                                    </div>
                                    {data.referral_code && (
                                        <div className="flex justify-between py-2">
                                            <span className="text-gray-600">Referral:</span>
                                            <span className="font-medium text-gray-900">{data.referral_code}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                                <h3 className="font-semibold text-base mb-4 flex items-center gap-2 text-gray-900">
                                    <Briefcase className="w-4 h-4 text-purple-600" />
                                    Campaign Details
                                </h3>
                                <div className="space-y-3 text-sm">
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Title:</span>
                                        <span className="font-medium text-gray-900">{data.campaign_title}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Objective:</span>
                                        <span className="font-medium text-gray-900">{data.campaign_objective}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Budget:</span>
                                        <span className="font-medium text-green-600">${data.budget}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Duration:</span>
                                        <span className="font-medium text-gray-900">{data.start_date} to {data.end_date}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Safety:</span>
                                        <span className="font-medium text-gray-900">{data.content_safety}</span>
                                    </div>
                                    {data.target_country && (
                                        <div className="flex justify-between py-2 border-b border-gray-100">
                                            <span className="text-gray-600">Country:</span>
                                            <span className="font-medium text-gray-900">{countries.find(c => c.code.toLowerCase() === data.target_country)?.name}</span>
                                        </div>
                                    )}
                                    {data.target_county && (
                                        <div className="flex justify-between py-2 border-b border-gray-100">
                                            <span className="text-gray-600">County:</span>
                                            <span className="font-medium text-gray-900">{counties.find(r => r.id.toString() === data.target_county)?.name}</span>
                                        </div>
                                    )}
                                    {data.business_types.length > 0 && (
                                        <div className="flex justify-between py-2">
                                            <span className="text-gray-600">Business Types:</span>
                                            <span className="font-medium text-gray-900">{data.business_types.length} selected</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                            <h3 className="font-semibold text-base mb-3 text-gray-900">Campaign Description</h3>
                            <p className="text-sm text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-lg">{data.description}</p>
                        </div>

                        {data.target_audience && (
                            <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                                <h3 className="font-semibold text-base mb-3 text-gray-900">Target Audience</h3>
                                <p className="text-sm text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-lg">{data.target_audience}</p>
                            </div>
                        )}

                        <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                            <div className="flex items-center gap-2 mb-3">
                                <Shield className="w-4 h-4 text-blue-600" />
                                <Label className="text-sm font-medium text-gray-700">Security Verification</Label>
                            </div>
                            <div ref={turnstileRef} id="turnstile-widget" className="flex justify-center"></div>
                            {!turnstileToken && (
                                <p className="mt-3 text-xs text-gray-500 text-center">
                                    Please complete the security verification to continue
                                </p>
                            )}
                        </div>

                        <div className="bg-gradient-to-br from-green-50 to-emerald-50 p-5 rounded-xl border-2 border-green-100">
                            <div className="flex items-start gap-3">
                                <Rocket className="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" />
                                <div>
                                    <h4 className="font-semibold text-green-900 mb-2">✅ Ready to Launch!</h4>
                                    <p className="text-sm text-green-800 leading-relaxed">
                                        Your campaign will be reviewed by our team and assigned to the selected Digital Content Distributor.
                                        You'll receive email updates throughout the campaign lifecycle.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                );

            default:
                return null;
        }
    };

    return (
        <>
            <Head title="Submit Campaign" />

            <div className="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-5xl mx-auto">
                    <div className="text-center mb-12 animate-in fade-in-50 duration-700">
                        <div className="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mb-4 shadow-lg">
                            <Rocket className="w-8 h-8 text-white" />
                        </div>
                        <h1 className="text-4xl font-bold text-gray-900 mb-3 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Launch Your Campaign
                        </h1>
                        <p className="text-base text-gray-600 max-w-2xl mx-auto">
                            Create your account and launch your first campaign in one simple process
                        </p>
                    </div>

                    <div className="mb-10 animate-in slide-in-from-top-5 duration-700">
                        <div className="relative">
                            <div className="absolute top-5 left-0 right-0 h-1 bg-gray-200 rounded-full" style={{ zIndex: 0 }}></div>
                            <div 
                                className="absolute top-5 left-0 h-1 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full transition-all duration-500 ease-in-out" 
                                style={{ width: `${(currentStepIndex / (steps.length - 1)) * 100}%`, zIndex: 1 }}
                            ></div>
                            
                            <div className="relative flex items-start justify-between" style={{ zIndex: 2 }}>
                                {steps.map((step, index) => {
                                    const StepIcon = step.icon;
                                    const isActive = index === currentStepIndex;
                                    const isCompleted = index < currentStepIndex;

                                    return (
                                        <div key={step.id} className="flex flex-col items-center" style={{ width: `${100 / steps.length}%` }}>
                                            <div className={`flex items-center justify-center w-10 h-10 rounded-full border-4 border-white shadow-lg transition-all duration-300 ${
                                                isCompleted ? 'bg-gradient-to-br from-green-500 to-emerald-500 text-white scale-110' :
                                                isActive ? `bg-gradient-to-br ${step.color} text-white scale-110 shadow-xl` : 
                                                'bg-white text-gray-400 border-gray-300'
                                            }`}>
                                                {isCompleted ? <CheckCircle className="w-5 h-5" /> : <StepIcon className="w-5 h-5" />}
                                            </div>
                                            <div className="mt-3 text-center hidden sm:block">
                                                <p className={`text-xs font-semibold transition-colors duration-300 ${
                                                    isActive ? 'text-gray-900' : isCompleted ? 'text-green-600' : 'text-gray-500'
                                                }`}>
                                                    {step.title}
                                                </p>
                                                <p className="text-xs text-gray-500 mt-0.5 max-w-[120px]">{step.description}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    <Card className="shadow-xl border-2 animate-in zoom-in-95 duration-500">
                        <CardHeader className={`bg-gradient-to-br ${steps[currentStepIndex].color} text-white rounded-t-lg`}>
                            <CardTitle className="flex items-center text-xl">
                                {React.createElement(steps[currentStepIndex].icon, { className: "w-6 h-6 mr-3" })}
                                {steps[currentStepIndex].title}
                            </CardTitle>
                            <CardDescription className="text-white/90 text-sm">
                                {steps[currentStepIndex].description}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-8">
                            {flash?.success && (
                                <Alert className="mb-6 border-2 border-green-200 bg-green-50 animate-in slide-in-from-top-2">
                                    <CheckCircle className="h-5 w-5 text-green-600" />
                                    <AlertDescription className="text-green-800 font-medium">{flash.success}</AlertDescription>
                                </Alert>
                            )}

                            {flash?.error && (
                                <Alert className="mb-6 border-2 border-red-200 bg-red-50 animate-in slide-in-from-top-2">
                                    <AlertDescription className="text-red-800 font-medium">{flash.error}</AlertDescription>
                                </Alert>
                            )}

                            <form onSubmit={submit} className="space-y-8">
                                {renderStepContent()}

                                <div className="flex justify-between pt-8 border-t-2 border-gray-100">
                                    <Button 
                                        type="button" 
                                        variant="outline" 
                                        onClick={prevStep} 
                                        disabled={currentStepIndex === 0}
                                        className="px-6 py-2.5 border-2 hover:bg-gray-50 transition-all duration-200 disabled:opacity-40"
                                    >
                                        <ArrowLeft className="w-4 h-4 mr-2" />
                                        Previous
                                    </Button>

                                    <Button 
                                        type="submit" 
                                        disabled={processing}
                                        className={`px-6 py-2.5 bg-gradient-to-br ${steps[currentStepIndex].color} text-white hover:shadow-lg transition-all duration-200 disabled:opacity-60`}
                                    >
                                        {processing ? (
                                            <>
                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                {currentStep === 'review' ? 'Launching...' : 'Processing...'}
                                            </>
                                        ) : currentStep === 'review' ? (
                                            <>
                                                <Rocket className="w-4 h-4 mr-2" />
                                                Launch Campaign
                                            </>
                                        ) : (
                                            <>
                                                Next Step
                                                <ArrowRight className="w-4 h-4 ml-2" />
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </form>

                            {currentStep === 'account' && (
                                <div className="mt-8 text-center p-4 bg-gray-50 rounded-xl">
                                    <p className="text-sm text-gray-600">
                                        <strong className="text-gray-900">Need help?</strong> Contact our support team at{' '}
                                        <a href="mailto:support@daya.africa" className="text-blue-600 hover:text-blue-700 font-medium hover:underline transition-colors">
                                            support@daya.africa
                                        </a>
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="mt-8 grid grid-cols-3 gap-4 animate-in fade-in-50 duration-700 delay-300">
                        <div className="text-center p-4 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                            <div className="text-2xl font-bold text-blue-600">24/7</div>
                            <div className="text-xs text-gray-600 mt-1">Support Available</div>
                        </div>
                        <div className="text-center p-4 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                            <div className="text-2xl font-bold text-purple-600">5min</div>
                            <div className="text-xs text-gray-600 mt-1">Setup Time</div>
                        </div>
                        <div className="text-center p-4 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                            <div className="text-2xl font-bold text-green-600">100%</div>
                            <div className="text-xs text-gray-600 mt-1">Success Rate</div>
                        </div>
                    </div>
                </div>
            </div>
            <ToastContainer position="top-right" autoClose={5000} />
        </>
    );
}