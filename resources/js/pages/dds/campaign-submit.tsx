import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { CheckCircle, Loader2, User, Briefcase, Target, CheckSquare, ArrowRight, ArrowLeft, Sparkles, Rocket, XCircle, Palette, Music, Handshake, Building, Phone, Megaphone, PartyPopper, Heart, Building2, HandPlatter, Utensils, HandCoins, CarTaxiFront, Church, Shield, AlertCircle, Home } from 'lucide-react';
import * as ReactToastify from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const { toast, ToastContainer } = ReactToastify;



declare global {
    interface Window {
        turnstile: {
            render: (element: string | HTMLElement, config: {
                sitekey: string;
                callback?: (token: string) => void;
                'error-callback'?: (error: string) => void;
                'expired-callback'?: () => void;
                'timeout-callback'?: () => void;
                theme?: 'light' | 'dark' | 'auto';
                size?: 'normal' | 'compact' | 'flexible';
                execution?: 'render' | 'execute';
                appearance?: 'always' | 'execute' | 'interaction-only';
            }) => string; // Returns widget ID
            remove: (widgetId: string) => void;
            reset: (widgetId: string) => void;
            getResponse: (widgetId: string) => string | null;
            isExpired: (widgetId: string) => boolean;
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

interface Ward {
    id: number;
    name: string;
    subcounty_id: number;
}

type Step = 'account' | 'campaign' | 'targeting' | 'review';

const currencyMap: Record<string, string> = {
    'KE': 'KSh',
    'NG': '₦',
    // Add more countries as needed
};

const getCurrencySymbol = (countryCode: string): string => {
    return currencyMap[countryCode.toUpperCase()] || '$';
};

// Cost per click mapping based on campaign objective (in Kenyan Shillings)
const cpcMap: Record<string, number> = {
    'music_promotion': 1,      // Light-Touch
    'brand_awareness': 1,      // Light-Touch (simple)
    'event_promotion': 1,      // Light-Touch (simple)
    'social_cause': 1,         // Light-Touch (basic)
    'app_downloads': 5,        // Moderate-Touch
    'product_launch': 5,       // Moderate-Touch
    'apartment_listing': 5,    // Moderate-Touch
};

const getCostPerClick = (objective: string, countryCode: string): number => {
    const baseCpc = cpcMap[objective] || 1; // Default to 1 KSh if not found
    // Convert to Naira for Nigeria (1 KSh = 10 Naira)
    return countryCode.toUpperCase() === 'NG' ? baseCpc * 10 : baseCpc;
};

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
    const [wards, setWards] = useState<Ward[]>([]);
    const [wardsLoading, setWardsLoading] = useState(false);
    const [referralValidating, setReferralValidating] = useState(false);
    const [referralValid, setReferralValid] = useState<boolean | null>(null);
    const [referralMessage, setReferralMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const turnstileRef = useRef(null);
    const [turnstileWidgetId, setTurnstileWidgetId] = useState<string | null>(null);
    const [turnstileLoaded, setTurnstileLoaded] = useState(false);
    const [turnstileError, setTurnstileError] = useState<string | null>(null);



    const { data, setData, processing, errors, reset, clearErrors, setError } = useForm({
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
        target_country: '',
        target_county: '',
        target_subcounty: '',
        target_ward: '',
        business_types: [] as string[],
        content_safety_preferences: [] as string[],
        start_date: '',
        end_date: '',
        target_audience: '',
        objectives: '',
        // allow custom 'other' business type from targeting UI
        other_business_type: '',
        music_genres: [] as string[],
        turnstile_token: import.meta.env.DEV ? 'dev-bypass-token' : '',
    });

    const clearFieldError = useCallback((field: string) => {
        if (errors[field as keyof typeof errors]) {
            clearErrors(field as keyof typeof errors);
        }
    }, [errors, clearErrors]);

    const updateData = (field: string, value: string | string[]) => {
        setData(field as keyof typeof data, value);
        clearFieldError(field);
    };

    // Initialize Turnstile when component mounts
    useEffect(() => {
        // Skip Turnstile in development environment
        if (import.meta.env.DEV) {
            setTurnstileLoaded(true);
            return;
        }

        const timeoutId: NodeJS.Timeout | null = null;
        let scriptLoadListener: (() => void) | null = null;

        const loadTurnstileScript = () => {
            // Add performance optimization with resource hints
            if (!document.querySelector('link[href="https://challenges.cloudflare.com"]')) {
                const preconnect = document.createElement('link');
                preconnect.rel = 'preconnect';
                preconnect.href = 'https://challenges.cloudflare.com';
                document.head.appendChild(preconnect);
            }

            // Check if Turnstile script is already loaded
            const existingScript = document.querySelector('script[src*="challenges.cloudflare.com/turnstile"]');
            if (existingScript) {
                // Script already exists, check if Turnstile is available
                if (window.turnstile) {
                    setTurnstileLoaded(true);
                } else {
                    // Wait for script to load
                    scriptLoadListener = () => setTurnstileLoaded(true);
                    existingScript.addEventListener('load', scriptLoadListener);
                }
                return;
            }

            // Load Turnstile script with explicit rendering
            const script = document.createElement('script');
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
            script.async = true;
            script.defer = true;
            
            scriptLoadListener = () => {
                setTurnstileLoaded(true);
                setTurnstileError(null);
            };
            
            script.addEventListener('load', scriptLoadListener);
            script.addEventListener('error', () => {
                setTurnstileError('Failed to load Turnstile script');
                console.error('Failed to load Turnstile script');
            });
            
            document.head.appendChild(script);
        };

        loadTurnstileScript();

        return () => {
            if (timeoutId) clearTimeout(timeoutId);
            if (scriptLoadListener) {
                const script = document.querySelector('script[src*="challenges.cloudflare.com/turnstile"]');
                if (script) {
                    script.removeEventListener('load', scriptLoadListener);
                }
            }
        };
    }, []);

    // Render Turnstile widget when script is loaded and we're on review step
    useEffect(() => {
        if (!turnstileLoaded || !window.turnstile || currentStep !== 'review') {
            return;
        }

        const turnstileElement = turnstileRef.current;
        if (!turnstileElement || turnstileWidgetId) {
            return; // Already rendered or no container
        }

        try {
            const widgetId = window.turnstile.render(turnstileElement, {
                sitekey: import.meta.env.VITE_TURNSTILE_SITE_KEY,
                theme: 'auto',
                size: 'normal',
                callback: (token: string) => {
                    console.log('Turnstile success callback triggered');
                    setData('turnstile_token', token);
                    setTurnstileError(null);
                    clearFieldError('turnstile_token');
                },
                'error-callback': (error: string) => {
                    console.error('Turnstile error callback:', error);
                    
                    // Enhanced error handling for specific error codes
                    if (error === '110200') {
                        // Domain mismatch error - check if we're in development environment
                        const hostname = window.location.hostname;
                        const isDevelopment = hostname === 'localhost' || 
                                            hostname.includes('.hostingersite.com') || 
                                            hostname.includes('.ngrok') || 
                                            hostname.includes('.vercel.app');
                        
                        if (isDevelopment) {
                            console.log('Development environment detected, bypassing Turnstile domain restriction');
                            setData('turnstile_token', 'dev-bypass-token');
                            setTurnstileError('Development mode: Security verification bypassed');
                            return;
                        }
                        setTurnstileError('Domain configuration error. Please contact support.');
                    } else if (error === '110100') {
                        setTurnstileError('Invalid site configuration. Please contact support.');
                    } else if (error === '110110') {
                        setTurnstileError('Widget configuration error. Please refresh and try again.');
                    } else {
                        setTurnstileError(`Verification failed: ${error}`);
                    }
                    
                    if (!error.startsWith('110200')) {
                        setData('turnstile_token', '');
                    }
                },
                'expired-callback': () => {
                    console.log('Turnstile expired callback');
                    setTurnstileError('Verification expired. Please try again.');
                    setData('turnstile_token', '');
                },
                'timeout-callback': () => {
                    console.log('Turnstile timeout callback');
                    setTurnstileError('Verification timed out. Please try again.');
                    setData('turnstile_token', '');
                }
            });
            
            setTurnstileWidgetId(widgetId);
            setTurnstileError(null);
        } catch (error) {
            console.error('Error rendering Turnstile widget:', error);
            setTurnstileError('Failed to initialize security verification');
        }

        return () => {
            if (turnstileWidgetId && window.turnstile) {
                try {
                    window.turnstile.remove(turnstileWidgetId);
                } catch (error) {
                    console.error('Error removing Turnstile widget:', error);
                }
                setTurnstileWidgetId(null);
            }
        };
    }, [turnstileLoaded, currentStep, turnstileWidgetId, setData, clearFieldError]);

    // Reset Turnstile widget if needed
    const resetTurnstile = () => {
        if (turnstileWidgetId && window.turnstile) {
            try {
                window.turnstile.reset(turnstileWidgetId);
                setData('turnstile_token', '');
                setTurnstileError(null);
            } catch (error) {
                console.error('Error resetting Turnstile widget:', error);
            }
        }
    };

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
    }, []);

    // Extract referral code from URL parameters
    useEffect(() => {
        const extractReferralCode = () => {
            if (typeof window !== 'undefined') {
                const urlParams = new URLSearchParams(window.location.search);
                // Extract referral code from URL parameters (ref, referral, or code)
                const referralCode = urlParams.get('ref') || urlParams.get('referral') || urlParams.get('code');
                if (referralCode && referralCode.length >= 6 && /^[A-Za-z0-9]{6,8}$/.test(referralCode)) {
                    setData('referral_code', referralCode.toUpperCase());
                }
            }
        };

        extractReferralCode();
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

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

    const fetchWards = async (subcountyId: number) => {
        setWardsLoading(true);
        try {
            const response = await fetch(`/api/wards?subcounty_id=${subcountyId}`);
            const data = await response.json();
            setWards(data);
        } catch (error) {
            console.error('Failed to fetch wards:', error);
            setWards([]);
        } finally {
            setWardsLoading(false);
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
        if (!code || code.length < 6) {
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
        } catch {
            setReferralValid(false);
            setReferralMessage('Failed to validate referral code');
        } finally {
            setReferralValidating(false);
        }
    };

    const steps = [
        { id: 'account', title: 'Account Setup', icon: User, description: 'Create your client profile', color: 'from-blue-500 to-cyan-500' },
        { id: 'campaign', title: 'Campaign Details', icon: Briefcase, description: 'Basic campaign information', color: 'from-purple-500 to-pink-500' },
        { id: 'targeting', title: 'Targeting & Budget', icon: Target, description: 'Define your audience and budget', color: 'from-orange-500 to-red-500' },
        { id: 'review', title: 'Review & Submit', icon: CheckSquare, description: 'Review and launch campaign', color: 'from-green-500 to-emerald-500' },
    ];

    const contentSafetyPreference = [
        { value: 'kids', label: 'Safe for Kids' },
        { value: 'teen', label: 'Teen Appropriate (13+)' },
        { value: 'adult', label: 'Adult Content (18+)' },
        { value: 'no_restrictions', label: 'No Restriction' },
    ];

    const musicGenres = [
        'Afrobeat', 'Afrobeats', 'Afro-rave', 'African hip-hop', 'Afro fusion', 'Alté', 'Amapiano', 'Benga', 'Bongo Flava', 'Blues', 'Classical', 
        'Country', 'Dancehall', 'Electronic', 'Folk', 'Funk', 'Gengetone', 
        'Gospel', 'Hip Hop', 'House', 'Jazz', 'Kapuka', 'Kwaito', 'Lingala',
        'Ohangla', 'Pop', 'R&B', 'Rap', 'Reggae', 'Rock', 'Rumba', 'Soul', 'Taarab', 'Traditional', 'Trap'
    ];

    const currentStepIndex = steps.findIndex(step => step.id === currentStep);

    // Validate referral code when it changes
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            validateReferralCode(data.referral_code);
        }, 500); // Debounce validation

        return () => clearTimeout(timeoutId);
    }, [data.referral_code]);

    const handleMusicGenreChange = (genre: string, checked: boolean | 'indeterminate' | undefined) => {
        const isChecked = Boolean(checked);
        if (isChecked) {
            setData('music_genres', [...(data.music_genres || []), genre]);
        } else {
            setData('music_genres', (data.music_genres || []).filter((g: string) => g !== genre));
        }
        // clear potential errors
        clearErrors('music_genres');
    };

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
        // Clear all existing errors first
        clearErrors();
        
        let hasErrors = false;

        if (step === 'account') {
            if (!data.business_name.trim()) {
                setError('business_name', 'Business/organization name is required');
                hasErrors = true;
            }
            if (!data.name.trim()) {
                setError('name', 'Contact person name is required');
                hasErrors = true;
            }
            if (!data.email.trim()) {
                setError('email', 'Email address is required');
                hasErrors = true;
            } else if (!validateEmail(data.email)) {
                setError('email', 'Please enter a valid email address');
                hasErrors = true;
            }
            if (!data.phone.trim()) {
                setError('phone', 'Phone number is required');
                hasErrors = true;
            } else if (!validatePhone(data.phone)) {
                setError('phone', 'Please enter a valid phone number');
                hasErrors = true;
            }
            if (!data.country) {
                setError('country', 'Country selection is required');
                hasErrors = true;
            }
        } else if (step === 'campaign') {
            // account_type must be set on campaign details now
            if (!data.account_type) {
                setError('account_type', 'Account type is required');
                hasErrors = true;
            }
            if (!data.campaign_title.trim()) {
                setError('campaign_title', 'Campaign title is required');
                hasErrors = true;
            }
            if (!data.digital_product_link.trim()) {
                setError('digital_product_link', 'Digital product link is required');
                hasErrors = true;
            } else if (!validateUrl(data.digital_product_link)) {
                setError('digital_product_link', 'Please enter a valid URL');
                hasErrors = true;
            }
            if (data.explainer_video_url && !validateUrl(data.explainer_video_url)) {
                setError('explainer_video_url', 'Please enter a valid URL');
                hasErrors = true;
            }
            if (!data.campaign_objective) {
                setError('campaign_objective', 'Campaign objective is required');
                hasErrors = true;
            }
            if (!data.budget || parseFloat(data.budget) <= 0) {
                setError('budget', 'Budget must be greater than $0');
                hasErrors = true;
            }


            // If 'label' or 'artist', require music_genres
            if (data.account_type === 'label' || data.account_type === 'artist') {
                if (!data.music_genres || data.music_genres.length === 0) {
                    setError('music_genres', 'Please select at least one music genre');
                    hasErrors = true;
                }

            }
        } else if (step === 'targeting') {
            if (!data.budget || parseFloat(data.budget) < 50) {
                console.log('Validation failed: budget is', data.budget, 'must be >= 50');
                setError('budget', 'Minimum budget is $50');
                hasErrors = true;
            }
            if (data.content_safety_preferences.length === 0) {
                console.log('Validation failed: no content safety preferences selected');
                setError('content_safety_preferences', 'Content safety preference is required');
                hasErrors = true;
            }
            if (!data.target_country) {
                console.log('Validation failed: no target country selected');
                setError('target_country', 'Target country is required');
                hasErrors = true;
            }
            // County, subcounty, and ward are now optional
            if (data.business_types.length === 0) {
                console.log('Validation failed: no business types selected');
                setError('business_types', 'Please select at least one business type');
                hasErrors = true;
            }
            if (!data.start_date) {
                console.log('Validation failed: no start date selected');
                setError('start_date', 'Start date is required');
                hasErrors = true;
            } else {
                const startDate = new Date(data.start_date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (startDate < today) {
                    console.log('Validation failed: start date is in the past', data.start_date);
                    setError('start_date', 'Start date cannot be in the past');
                    hasErrors = true;
                }
            }
            if (!data.end_date) {
                console.log('Validation failed: no end date selected');
                setError('end_date', 'End date is required');
                hasErrors = true;
            } else if (data.start_date && new Date(data.end_date) <= new Date(data.start_date)) {
                console.log('Validation failed: end date is not after start date', data.end_date, data.start_date);
                setError('end_date', 'End date must be after start date');
                hasErrors = true;
            }
        } else if (step === 'review') {
            if (!data.turnstile_token) {
                setError('turnstile_token', 'Please complete the security verification');
                hasErrors = true;
            }
        }

        return !hasErrors;
    };

    const handleContentSafetyPreferenceChange = (type: string, checked: boolean | "indeterminate") => {
        if (checked === true) {
            setData(prev => ({ ...prev, content_safety_preferences: [...prev.content_safety_preferences, type] }));
        } else {
            setData(prev => ({ ...prev, content_safety_preferences: prev.content_safety_preferences.filter((t: string) => t !== type) }));
        }
        clearFieldError('content_safety_preferences');
    };

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (isSubmitting) return; // prevent duplicate submissions
        if (currentStep === 'review') {
            // Validate all steps before submission
            let allValid = true;
            ['account', 'campaign', 'targeting', 'review'].forEach(step => {
                if (!validateStep(step as Step)) {
                    allValid = false;
                }
            });
            if (!allValid) {
                toast.error('Please fill in all required fields correctly before submitting.');
                return;
            }

            setIsSubmitting(true);
            try {
                const response = await fetch('/api/client/campaign/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (response.ok) {
                    console.log('Campaign submission successful:', result);

                    // Clear form and show success message
                    reset();
                    setCurrentStep('account');

                    // Show success toast
                    toast.success(result.message || 'Campaign submitted successfully!');
                    // Redirect to the home page after a brief delay
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 2000);
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
            finally {
                setIsSubmitting(false);
            }
        } else {
            // Validate current step before proceeding
            if (validateStep(currentStep)) {
                nextStep();
            } else {
                toast.error('Please fill in all required fields correctly before continuing.', {
                    autoClose: 3000
                });
            }
        }
    };

    const nextStep = () => {
        if (isSubmitting) return; // guard navigation while submitting
        if (validateStep(currentStep)) {
            const nextIndex = currentStepIndex + 1;
            if (nextIndex < steps.length) {
                setCurrentStep(steps[nextIndex].id as Step);
                // Clear any previous errors when successfully moving to next step
                clearErrors();
            }
        }
    };

    const prevStep = () => {
        const prevIndex = currentStepIndex - 1;
        if (prevIndex >= 0) {
            setCurrentStep(steps[prevIndex].id as Step);
        }
    };

    // business categories & helper for the business type targeting UI
    const businessCategories = [
        {
            id: 'retail',
            title: 'Retail & Merchant',
            emoji: <Building2 />,
            cardClass: '',
            titleClass: 'font-semibold text-sm mb-3 text-orange-500 flex items-center gap-2',
            types: ['kiosk_duka', 'mini_supermarket', 'wholesale_shop', 'hardware_store', 'agrovet', 'butchery', 'boutique', 'electronics', 'stationery', 'general_store'],
        },
        {
            id: 'services',
            title: 'Services & Care',
            emoji: <HandPlatter />,
            cardClass: '',
            titleClass: 'font-semibold text-sm mb-3 text-orange-500 flex items-center gap-2',
            types: ['salon', 'barber_shop', 'beauty_parlour', 'tailor', 'uber', 'shoe_repair', 'photography_studio', 'printing_cyber', 'laundry'],
        },
        {
            id: 'food',
            title: 'Food & Beverage',
            emoji: <Utensils />,
            cardClass: '',
            titleClass: 'font-semibold text-sm mb-3 text-orange-500 flex items-center gap-2',
            types: ['cafe', 'restaurant', 'fast_food', 'mama_mboga', 'milk_atm', 'bakery'],
        },
        {
            id: 'financial',
            title: 'Financial Services',
            emoji: <HandCoins />,
            cardClass: '',
            titleClass: 'font-semibold text-sm mb-3 text-orange-500 flex items-center gap-2',
            types: ['mobile_money', 'bank_agent', 'bill_payment', 'betting_shop'],
        },
        {
            id: 'transport',
            title: 'Transport',
            emoji: <CarTaxiFront />,
            cardClass: '',
            titleClass: 'font-semibold text-sm mb-3 text-orange-500 flex items-center gap-2',
            types: ['boda_boda', 'matatu_sacco', 'fuel_station', 'car_wash'],
        },
        {
            id: 'community',
            title: 'Community',
            emoji: <Church />,
            cardClass: '',
            titleClass: 'font-semibold text-sm mb-3 text-orange-500 flex items-center gap-2',
            types: ['church', 'school_canteen', 'bar_lounge', 'pharmacy', 'clinic', 'other'],
        },
    ];

    const handleBusinessTypeChange = (value: string, checked: boolean | 'indeterminate' | undefined) => {
        const current = data.business_types || [];
        const isChecked = Boolean(checked);
        if (isChecked) {
            if (!current.includes(value)) {
                setData('business_types', [...current, value]);
            }
        } else {
            setData('business_types', current.filter((t: string) => t !== value));
        }
        clearFieldError('business_types');
    };

    const handleSelectAllBusinessTypes = () => {
        const allTypes = businessCategories.flatMap(cat => cat.types);
        const isAllSelected = allTypes.every(type => data.business_types.includes(type));
        
        if (isAllSelected) {
            // Deselect all
            setData('business_types', []);
        } else {
            // Select all
            setData('business_types', allTypes);
        }
        clearFieldError('business_types');
    };

    const renderStepContent = () => {
        switch (currentStep) {
            case 'account':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-blue-50 to-cyan-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-blue-100 dark:border-blue-600">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg">
                                    <User className="w-5 h-5  text-slate-900" />
                                </div>
                                <div>
                                    <h3 className="font-semibold text-blue-900 dark:text-blue-300">Let's Get Started</h3>
                                    <p className="text-sm text-blue-700 dark:text-blue-300">Set up your account to begin your campaign journey with us</p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-5">
                            {/* Account Type moved to Campaign step; we'll keep account details simpler here */}

                            <div>
                                <Label htmlFor="business_name" className="text-sm font-medium mb-2 block">
                                    Business/Organization Name <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="business_name"
                                    type="text"
                                    value={data.business_name}
                                    onChange={(e) => updateData('business_name', e.target.value)}
                                    placeholder="Enter your business or organization name"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                <InputError message={errors.business_name} />
                            </div>

                            <div>
                                <Label htmlFor="name" className="text-sm font-medium mb-2 block">
                                    Full Name <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => updateData('name', e.target.value)}
                                    placeholder="Enter your full name"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <Label htmlFor="email" className="text-sm font-medium mb-2 block">
                                        Email Address <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => updateData('email', e.target.value)}
                                        placeholder="your.email@example.com"
                                        className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div>
                                    <Label htmlFor="phone" className="text-sm font-medium mb-2 block">
                                        Phone Number <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => updateData('phone', e.target.value)}
                                        placeholder="+234 xxx xxx xxxx"
                                        className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <Label htmlFor="country" className="text-sm font-medium mb-2 block">
                                        Country <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Select value={data.country} onValueChange={(value) => {
                                        setData('country', value);
                                        updateLabels(value);
                                    }} disabled={countriesLoading}>
                                        <SelectTrigger className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none">
                                            <SelectValue placeholder={countriesLoading ? "Loading countries..." : "Select your country"} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white dark:bg-slate-800 border-blue-300 dark:border-blue-600/20">
                                            <SelectItem value="-">-</SelectItem>
                                            {countries.map((country) => (
                                                <SelectItem key={country.id} value={country.code.toLowerCase()}>
                                                    {country.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.country} />
                                </div>

                                <div>
                                    <Label htmlFor="referral_code" className="text-sm font-medium mb-2 block">
                                        Referral Code (Optional)
                                    </Label>
                                    <Input
                                        id="referral_code"
                                        type="text"
                                        value={data.referral_code}
                                        onChange={(e) => setData('referral_code', e.target.value)}
                                        placeholder="Enter referral code if applicable"
                                        className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                    />
                                    <p className="mt-1.5 text-xs text-gray-500">
                                        If you were referred by anyone.
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
                        <div className="bg-gradient-to-br from-purple-50 to-pink-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-purple-100 dark:border-purple-600">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg">
                                    <Briefcase className="w-5 h-5  text-slate-900" />
                                </div>
                                <div>
                                    <h3 className="font-semibold text-purple-900 dark:text-purple-300">Campaign Information</h3>
                                    <p className="text-sm text-purple-700 dark:text-purple-300">Tell us about your campaign goals and objectives</p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-5">
                            <div>
                                <Label htmlFor="campaign_title" className="text-sm font-medium mb-2 block">
                                    Campaign Title <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="campaign_title"
                                    type="text"
                                    value={data.campaign_title}
                                    onChange={(e) => updateData('campaign_title', e.target.value)}
                                    required
                                    placeholder="Enter a compelling campaign title"
                                    className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none"
                                />
                                <InputError message={errors.campaign_title} />
                            </div>

                            <div>
                                <Label htmlFor="account_type" className="text-sm font-medium mb-2 block">
                                    Account Type <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.account_type} onValueChange={(value) => updateData('account_type', value)}>
                                    <SelectTrigger className="border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none">
                                        <SelectValue placeholder="Select account type" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-purple-300 dark:border-purple-600/20">
                                        <SelectItem value="-">-</SelectItem>
                                        <SelectItem value="startup"><Rocket/> Startup</SelectItem>
                                        <SelectItem value="artist"><Palette /> Artist</SelectItem>
                                        <SelectItem value="label"><Music /> Label</SelectItem>
                                        <SelectItem value="ngo"><Handshake /> NGO</SelectItem>
                                        <SelectItem value="agency"><Briefcase /> Agency</SelectItem>
                                        <SelectItem value="business"><Building /> Business</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.account_type} />
                            </div>

                            {/* If artist/label, allow selecting music genres */}
                            {(data.account_type === 'artist' || data.account_type === 'label') && (
                                <div>
                                    <Label className="text-sm font-medium mb-2 block">Music Genres <span className='text-red-500 dark:text-red-400'>*</span></Label>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        {musicGenres.map((genre) => (
                                            <div key={genre} className="flex items-center space-x-2">
                                                <Checkbox
                                                    id={`genre_${genre}`}
                                                    checked={(data.music_genres || []).includes(genre)}
                                                    onCheckedChange={(checked) => handleMusicGenreChange(genre, checked)}
                                                    className="border-purple-300 dark:border-purple-600/20"
                                                />
                                                <Label htmlFor={`genre_${genre}`} className="text-sm">{genre}</Label>
                                            </div>
                                        ))}
                                    </div>
                                    <InputError message={errors.music_genres} />
                                </div>
                            )}

                            <div>
                                <Label htmlFor="digital_product_link" className="text-sm font-medium mb-2 block">
                                    Digital Product Link <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="digital_product_link"
                                    type="url"
                                    value={data.digital_product_link}
                                    onChange={(e) => updateData('digital_product_link', e.target.value)}
                                    required
                                    placeholder="https://your-product-link.com"
                                    className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none"
                                />
                                <InputError message={errors.digital_product_link} />
                                <p className="mt-1.5 text-xs text-gray-500">
                                    Link to your digital product, app, or content
                                </p>
                            </div>

                            <div>
                                <Label htmlFor="explainer_video_url" className="text-sm font-medium mb-2 block">
                                    Explainer Video URL (Optional)
                                </Label>
                                <Input
                                    id="explainer_video_url"
                                    type="url"
                                    value={data.explainer_video_url}
                                    onChange={(e) => setData('explainer_video_url', e.target.value)}
                                    placeholder="https://youtube.com/watch?v=..."
                                    className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none"
                                />
                                <InputError message={errors.explainer_video_url} />
                                <p className="mt-1.5 text-xs text-gray-500">
                                    YouTube, Vimeo, or other video platform link
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <Label htmlFor="campaign_objective" className="text-sm font-medium mb-2 block">
                                        Campaign Objective <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Select value={data.campaign_objective} onValueChange={(value) => setData('campaign_objective', value)}>
                                        <SelectTrigger className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none">
                                            <SelectValue placeholder="Select campaign objective" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white dark:bg-slate-800 border-purple-300 dark:border-purple-600/20">
                                            <SelectItem value="-">-</SelectItem>
                                            <SelectItem value="music_promotion"><Music /> Music Promotion</SelectItem>
                                            <SelectItem value="app_downloads"><Phone /> App Downloads</SelectItem>
                                            <SelectItem value="brand_awareness"><Megaphone /> Brand Awareness</SelectItem>
                                            <SelectItem value="product_launch"><Rocket /> Product Launch</SelectItem>
                                            <SelectItem value="apartment_listing"><Home /> Apartment Listing</SelectItem>
                                            <SelectItem value="event_promotion"><PartyPopper /> Event Promotion</SelectItem>
                                            <SelectItem value="social_cause"><Heart /> Surveys</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.campaign_objective} />
                                    {data.campaign_objective && data.campaign_objective !== '-' && (
                                        <p className="mt-1.5 text-xs text-gray-600 dark:text-gray-400">
                                            Cost per click: {getCurrencySymbol(data.country)} {getCostPerClick(data.campaign_objective, data.country)}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="budget" className="text-sm font-medium mb-2 block">
                                        Budget ({getCurrencySymbol(data.country)}) <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="budget"
                                        type="number"
                                        min="0"
                                        value={data.budget}
                                        onChange={(e) => setData('budget', e.target.value)}
                                        required
                                        placeholder="Enter campaign budget"
                                        className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none"
                                    />
                                    <InputError message={errors.budget} />
                                    {data.budget && data.campaign_objective && data.campaign_objective !== '-' && parseFloat(data.budget) > 0 && (
                                        <div className="mt-2 p-3 bg-purple-50 dark:bg-slate-700 rounded-lg border border-purple-200 dark:border-purple-600">
                                            <div className="flex items-start gap-2">
                                                <CheckCircle className="w-4 h-4 text-purple-600 dark:text-purple-400 mt-0.5 flex-shrink-0" />
                                                <div className="text-xs text-purple-800 dark:text-purple-300">
                                                    <p className="font-semibold mb-1">Campaign Budget Breakdown:</p>
                                                    <p>• Cost per scan: {getCurrencySymbol(data.country)}{getCostPerClick(data.campaign_objective, data.country)}</p>
                                                    <p>• Maximum scans: <span className="font-bold">{Math.floor(parseFloat(data.budget) / getCostPerClick(data.campaign_objective, data.country))}</span> verified scans</p>
                                                    <p className="mt-1 text-purple-600 dark:text-purple-400">Your campaign will automatically complete when the scan limit is reached.</p>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>


                        </div>
                    </div>
                );

            case 'targeting':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-orange-50 to-red-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-orange-100 dark:border-orange-600">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg">
                                    <Target className="w-5 h-5  text-slate-900" />
                                </div>
                                <div>
                                    <h3 className="font-semibold text-orange-900 dark:text-orange-300">Account Setup</h3>
                                    <p className="text-sm text-orange-700 dark:text-orange-300">Set up your wallet and complete registration</p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-6">
                            {/* <div>
                                <Label htmlFor="budget" className="text-sm font-medium mb-2 block">
                                    Budget ({getCurrencySymbol(data.country)}) <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="budget"
                                    type="number"
                                    min="50"
                                    step="10"
                                    value={data.budget}
                                    onChange={(e) => setData('budget', e.target.value)}
                                    required
                                    placeholder="Enter campaign budget"
                                    className="mt-2 border-orange-300 dark:border-orange-600/20 bg-white dark:bg-slate-800 focus:border-orange-500 dark:focus:border-orange-400 focus:outline-none"
                                />
                                <p className="mt-1.5 text-xs text-gray-500">
                                    1 Credit = $1 = 10 verified clicks/scans
                                </p>
                                <InputError message={errors.budget} />
                            </div> */}

                            <div>
                                <Label className="text-sm font-medium mb-2 block">
                                    Content Safety Preference <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {contentSafetyPreference.map((type) => (
                                        <div key={type.value} className="flex items-center space-x-2 p-3 border-none rounded-lg hover:bg-gray-50 hover:dark:bg-slate-700 transition-colors">
                                            <Checkbox
                                                id={type.value}
                                                checked={data.content_safety_preferences.includes(type.value)}
                                                onCheckedChange={(checked) => handleContentSafetyPreferenceChange(type.value, checked)}
                                                className={`border-orange-300 dark:border-orange-600/20 bg-white dark:bg-slate-500 focus:ring-orange-500 dark:focus:ring-orange-400 ${ data.content_safety_preferences.includes(type.value) ? 'bg-orange-100 dark:bg-orange-700' : '' }`}
                                            />
                                            <Label htmlFor={type.value} className="text-sm">
                                                {type.label}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                                <p className="mt-3 text-xs text-gray-500">
                                    Help us match your content with appropriate Digital Content Distributors
                                </p>
                                <InputError message={errors.content_safety_preferences} />
                            </div>

                            <div>
                                <Label className="text-sm font-medium mb-2 block">
                                    Target Location <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
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
                                        <Label htmlFor="target_county" className="text-xs text-gray-600 mb-1.5 block">{countyLabel} (Optional)</Label>
                                        <Select
                                            value={data.target_county}
                                            onValueChange={(value) => {
                                                setData('target_county', value);
                                                setData('target_subcounty', '');
                                                setData('target_ward', ''); // Reset ward selection
                                                setSubcounties([]);
                                                setWards([]); // Clear wards when county changes
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
                                        <Label htmlFor="target_subcounty" className="text-xs text-gray-600 mb-1.5 block">{subcountyLabel} (Optional)</Label>
                                        <Select
                                            value={data.target_subcounty}
                                            onValueChange={(value) => {
                                                setData('target_subcounty', value);
                                                setData('target_ward', ''); // Reset ward selection
                                                if (value) {
                                                    fetchWards(parseInt(value));
                                                } else {
                                                    setWards([]);
                                                }
                                            }}
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
                                    <div>
                                        <Label htmlFor="target_ward" className="text-xs text-gray-600 mb-1.5 block">Ward (Optional)</Label>
                                        <Select
                                            value={data.target_ward}
                                            onValueChange={(value) => setData('target_ward', value)}
                                            disabled={wardsLoading || !data.target_subcounty}
                                        >
                                            <SelectTrigger className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500">
                                                <SelectValue placeholder={wardsLoading ? "Loading..." : "Select ward"} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {wards.map((ward) => (
                                                    <SelectItem key={ward.id} value={ward.id.toString()}>
                                                        {ward.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                <InputError message={errors.target_country} />
                                {/* County, subcounty, and ward errors removed as they are now optional */}
                            </div>

                            <div className="text-sm font-medium mb-2 block">
                                <Label className="text-sm font-medium mb-2 block">
                                    Business Type Targeting <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <div className="flex justify-end mb-3">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={handleSelectAllBusinessTypes}
                                        className="text-orange-600 border-orange-300 hover:bg-orange-50 dark:text-orange-400 dark:border-orange-600 dark:hover:bg-orange-900/20"
                                    >
                                        {businessCategories.flatMap(cat => cat.types).every(type => data.business_types.includes(type)) 
                                            ? 'Deselect All' 
                                            : 'Select All'}
                                    </Button>
                                </div>
                                <div className="max-h-96 overflow-y-auto">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto border rounded-lg p-4 bg-orange-50 dark:bg-slate-800 border-orange-200 dark:border-orange-600">
                                        {businessCategories.map((cat) => (
                                            <div key={cat.id} className={cat.cardClass}>
                                                <h4 className={cat.titleClass}>
                                                    <span className="text-lg">{cat.emoji}</span> {cat.title}
                                                </h4>
                                                <div className="space-y-2.5">
                                                    {cat.types.map((type) => (
                                                        <div key={type} className="flex items-center space-x-2">
                                                            <Checkbox
                                                                id={`business_${type}`}
                                                                checked={data.business_types.includes(type)}
                                                                onCheckedChange={(checked) => handleBusinessTypeChange(type, checked)}
                                                                className={`border-orange-300 dark:border-orange-600/20 bg-white dark:bg-slate-500 focus:ring-orange-500 dark:focus:ring-orange-400 ${ data.business_types.includes(type) ? 'bg-orange-100 dark:bg-orange-700' : '' }`}
                                                            />
                                                            <Label htmlFor={`business_${type}`} className="text-sm">
                                                                {type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                            </Label>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {data.business_types.includes('other') && (
                                    <Input
                                        type="text"
                                        value={data.other_business_type}
                                        onChange={(e) => setData('other_business_type', e.target.value)}
                                        placeholder="Please specify other business type"
                                        className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none"
                                    />
                                )}

                                {errors.business_types && (
                                    <p className="mt-2 text-sm text-red-600 animate-in slide-in-from-top-1">{errors.business_types}</p>
                                )}
                            </div>

                            <div>
                                <Label className="text-sm font-medium text-gray-700 mb-3 block">Campaign Duration <span className='text-red-500 dark:text-red-400'>*</span></Label>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="start_date" className="text-xs text-gray-600 mb-1.5 block">Start Date</Label>
                                        <Input
                                            id="start_date"
                                            type="date"
                                            value={data.start_date}
                                            onChange={(e) => updateData('start_date', e.target.value)}
                                            required
                                            min={new Date().toISOString().split('T')[0]}
                                            className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500"
                                        />
                                        <InputError message={errors.start_date} />
                                    </div>
                                    <div>
                                        <Label htmlFor="end_date" className="text-xs text-gray-600 mb-1.5 block">End Date</Label>
                                        <Input
                                            id="end_date"
                                            type="date"
                                            value={data.end_date}
                                            onChange={(e) => updateData('end_date', e.target.value)}
                                            required
                                            min={data.start_date || new Date().toISOString().split('T')[0]}
                                            className="transition-all duration-200 hover:border-orange-400 focus:ring-2 focus:ring-orange-500"
                                        />
                                        <InputError message={errors.end_date} />
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <Label htmlFor="target_audience" className="text-sm font-medium mb-2 block">Target Audience</Label>
                                    <textarea
                                        id="target_audience"
                                        value={data.target_audience}
                                        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('target_audience', e.target.value)}
                                        placeholder="Describe your target audience (age, gender, interests, etc.)"
                                        rows={3}
                                        className="flex min-h-[60px] w-full rounded-lg border border-input bg-background px-4 py-3 text-sm ring-offset-background placeholder:text-muted-foreground transition-all duration-200 hover:border-orange-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="objectives" className="text-sm font-medium mb-2 block">Key Objectives</Label>
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
                                    <CheckSquare className="w-5 h-5  text-slate-900" />
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
                                        <span className="font-medium text-green-600">{getCurrencySymbol(data.country)}{data.budget}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Cost per Click:</span>
                                        <span className="font-medium text-blue-600">{getCurrencySymbol(data.country)}{getCostPerClick(data.campaign_objective, data.country)}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Maximum Scans:</span>
                                        <span className="font-medium text-purple-600">{Math.floor(parseFloat(data.budget) / getCostPerClick(data.campaign_objective, data.country))} verified scans</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Duration:</span>
                                        <span className="font-medium text-gray-900">{data.start_date} to {data.end_date}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600">Safety:</span>
                                        <span className="font-medium text-gray-900">
                                            {data.content_safety_preferences.length > 0 
                                                ? data.content_safety_preferences.map(pref => {
                                                    const prefObj = contentSafetyPreference.find(p => p.value === pref);
                                                    return prefObj?.label;
                                                }).join(', ')
                                                : 'Not specified'
                                            }
                                        </span>
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
                                    {data.music_genres && data.music_genres.length > 0 && (
                                        <div className="flex justify-between py-2">
                                            <span className="text-gray-600">Music Genres:</span>
                                            <span className="font-medium text-gray-900">{Array.isArray(data.music_genres) ? data.music_genres.join(', ') : data.music_genres}</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>



                        {data.target_audience && (
                            <div className="bg-white p-5 rounded-xl border-2 border-gray-100">
                                <h3 className="font-semibold text-base mb-3 text-gray-900">Target Audience</h3>
                                <p className="text-sm text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-lg">{data.target_audience}</p>
                            </div>
                        )}



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

                        <div className="bg-amber-50 p-5 rounded-xl border-2 border-amber-100">
                            <h3 className="text-lg font-semibold text-amber-700 mb-3 flex items-center gap-2">
                                <Shield className="w-5 h-5" />
                                Security Verification
                            </h3>
                            <p className="text-amber-600 mb-3">Please complete the security check to submit your campaign.</p>
                            
                            {!turnstileLoaded ? (
                                <div className="flex items-center gap-2 p-4 bg-amber-100 rounded-lg">
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-amber-600"></div>
                                    <span className="text-amber-700">Loading security verification...</span>
                                </div>
                            ) : data.turnstile_token === 'dev-bypass-token' ? (
                                <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <p className="text-blue-700 text-sm flex items-center gap-2">
                                        <Shield className="w-4 h-4" />
                                        Security verification bypassed for development environment
                                    </p>
                                </div>
                            ) : (
                                <div>
                                    <div ref={turnstileRef} className="mb-3"></div>
                                    {turnstileError && turnstileError !== 'Development mode: Security verification bypassed' && (
                                        <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <p className="text-red-700 text-sm flex items-center gap-2">
                                                <AlertCircle className="w-4 h-4" />
                                                {turnstileError}
                                            </p>
                                            <button
                                                type="button"
                                                onClick={resetTurnstile}
                                                className="mt-2 text-sm text-red-600 hover:text-red-800 underline"
                                            >
                                                Try again
                                            </button>
                                        </div>
                                    )}
                                </div>
                            )}
                            
                            {(errors.turnstile_token || (!data.turnstile_token && currentStep === 'review')) && !turnstileError && (
                                <p className="text-red-600 text-sm mt-2 flex items-center gap-1">
                                    <AlertCircle className="w-4 h-4" />
                                    {errors.turnstile_token || 'Please complete the security verification'}
                                </p>
                            )}
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

            <div className="h-screen bg-background text-foreground overflow-y-auto bg-white dark:from-slate-700 dark:via-slate-800 dark:to-slate-700 campaign-page">

                <div className="absolute inset-0 bg-black opacity-10"></div>
                <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAxOGMzLjMxNCAwIDYgMi42ODYgNiA2cy0yLjY4NiA2LTYgNi02LTIuNjg2LTYtNiAyLjY4Ni02IDYtNiIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIiIG9wYWNpdHk9Ii4xIi8+PC9nPjwvc3ZnPg==')] opacity-100 dark:opacity-80"></div>

                <div className="max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8 relative z-10">
                    {isSubmitting && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 pointer-events-auto">
                            <div className="bg-white/95 p-6 rounded-lg flex items-center gap-3 shadow-lg">
                                <Loader2 className="animate-spin h-6 w-6 text-gray-900" />
                                <span className="text-gray-900 font-medium">Submitting campaign... please wait</span>
                            </div>
                        </div>
                    )}
                    {/* Page-specific overrides to ensure good input contrast on glass background */}
                    <div className="text-center mb-12 animate-in fade-in-50 duration-700">
                        {/* <div className="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mb-4 shadow-lg">
                            <Rocket className="w-8 h-8  text-slate-900" />
                        </div>
                        <h1 className="text-4xl font-bold  text-slate-900 mb-3">
                            Launch Your Campaign
                        </h1>
                        <p className="text-base  text-slate-900/90 max-w-2xl mx-auto">
                            Create your account and launch your first campaign in one simple process
                        </p> */}


                        <div className="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full mb-6">
                            <Sparkles className="w-5 h-5 mr-2" />
                            <span className="text-sm font-medium">Join Africa's Leading Digital Network</span>
                        </div>

                        <h1 className="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                            Launch Your First
                            <span className="block bg-gradient-to-r from-yellow-300 to-orange-300 bg-clip-text text-transparent">
                                Campaign
                            </span>
                        </h1>
                    </div>

                    <p className="text-xl md:text-2xl  text-slate-900 max-w-3xl mx-auto mb-12 dark:text-slate-300">
                        Join our community of influencers and earn rewards while promoting Daya across Africa
                    </p>

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
                                                isCompleted ? 'bg-gradient-to-br from-green-500 to-emerald-500  text-slate-900 scale-110' :
                                                isActive ? `bg-gradient-to-br ${step.color}  text-slate-900 scale-110 shadow-xl` : 
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

                    <Card className="text-card-foreground flex flex-col gap-6 rounded-xl border shadow-xl border-none animate-in zoom-in-95 duration-500 bg-gray-100/50 dark:bg-gray-950 backdrop-blur-md border-gray-200 dark:border-slate-600 py-0">
                        <CardHeader className={`flex flex-col gap-1.5 px-6 py-6 bg-gradient-to-br ${steps[currentStepIndex].color}  text-slate-900 rounded-t-lg`}>
                            <CardTitle className="flex items-center text-xl">
                                {React.createElement(steps[currentStepIndex].icon, { className: "w-6 h-6 mr-3" })}
                                {steps[currentStepIndex].title}
                            </CardTitle>
                            <CardDescription className=" text-slate-900/90 text-sm">
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
                                        disabled={processing || isSubmitting}
                                        className={`px-6 py-2.5 bg-gradient-to-br ${steps[currentStepIndex].color}  text-slate-900 hover:shadow-lg transition-all duration-200 disabled:opacity-60`}
                                    >
                                        {(processing || isSubmitting) ? (
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

                    {/* <div className="mt-8 grid grid-cols-3 gap-4 animate-in fade-in-50 duration-700 delay-300">
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
                    </div> */}
                </div>
            </div>
            <ToastContainer
                position="top-right"
                autoClose={5000}
                hideProgressBar={false}
                newestOnTop={false}
                closeOnClick
                rtl={false}
                pauseOnFocusLoss
                draggable
                pauseOnHover
                theme="colored"
            />
        </>
    );
}