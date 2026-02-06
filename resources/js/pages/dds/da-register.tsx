import React, { useState, useRef, useEffect, useCallback } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import { CheckCircle, Loader2, Users, Award, TrendingUp, Sparkles, User, ArrowRight, ArrowLeft, Wallet, FileText, MapPin, Globe, Instagram, Twitter, Facebook, MessageCircle, Linkedin, Music, XCircle, Shield, AlertCircle } from 'lucide-react';
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

type Step = 'personal' | 'social' | 'account';

export default function DaRegister() {
    // Initialize showForm based on URL parameters
    const getInitialShowForm = () => {
        if (typeof window !== 'undefined') {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('started') === 'true';
        }
        return false;
    };

    const [currentStep, setCurrentStep] = useState<Step>('personal');
    const [processing, setProcessing] = useState(false);
    const [showForm, setShowForm] = useState(getInitialShowForm);
    const [locationPermissionGranted, setLocationPermissionGranted] = useState(false);
    const [locationLoading, setLocationLoading] = useState(false);
    const [countries, setCountries] = useState<Country[]>([]);
    const [countriesLoading, setCountriesLoading] = useState(true);
    const [counties, setCounties] = useState<County[]>([]);
    const [countiesLoading, setCountiesLoading] = useState(false);
    const [subcounties, setSubcounties] = useState<Subcounty[]>([]);
    const [subcountiesLoading, setSubcountiesLoading] = useState(false);
    const [wards, setWards] = useState<Ward[]>([]);
    const [wardsLoading, setWardsLoading] = useState(false);
    const [countyLabel, setCountyLabel] = useState('County');
    const [subcountyLabel, setSubcountyLabel] = useState('Sub-county');
    const turnstileRef = useRef(null);
    const [turnstileWidgetId, setTurnstileWidgetId] = useState<string | null>(null);
    const [turnstileLoaded, setTurnstileLoaded] = useState(false);
    const [turnstileError, setTurnstileError] = useState<string | null>(null);
    const [referralValidating, setReferralValidating] = useState(false);
    const [referralValid, setReferralValid] = useState<boolean | null>(null);
    const [referralMessage, setReferralMessage] = useState('');
    const [emailValidating, setEmailValidating] = useState(false);
    const [emailValid, setEmailValid] = useState<boolean | null>(null);
    const [emailMessage, setEmailMessage] = useState('');
    const [nationalIdValidating, setNationalIdValidating] = useState(false);
    const [nationalIdValid, setNationalIdValid] = useState<boolean | null>(null);
    const [nationalIdMessage, setNationalIdMessage] = useState('');
    const [phoneValidating, setPhoneValidating] = useState(false);
    const [phoneValid, setPhoneValid] = useState<boolean | null>(null);
    const [phoneMessage, setPhoneMessage] = useState('');

    const { data, setData, errors, reset, clearErrors, setError } = useForm({
        referral_code: '',
        full_name: '',
        national_id: '',
        dob: '',
        gender: '',
        email: '',
        country: '',
        county: '',
        subcounty: '',
        ward: '',
        address: '',
        phone: '',
        latitude: '',
        longitude: '',
        platforms: [] as string[],
        followers: '',
        communication_channel: '',
        wallet_type: '',
        wallet_pin: '',
        confirm_pin: '',
        terms: false,
        turnstile_token: import.meta.env.DEV ? 'dev-bypass-token' : '',
    });

    // APP_URL is no longer used for redirects; constant removed to avoid linter errors

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

    // Render Turnstile widget when script is loaded and we're on account step
    useEffect(() => {
        if (!turnstileLoaded || !window.turnstile || currentStep !== 'account') {
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
                    // Clear turnstile_token error inline to avoid circular dependency
                    if (errors.turnstile_token) {
                        clearErrors('turnstile_token');
                    }
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
    }, [turnstileLoaded, currentStep, turnstileWidgetId, setData, clearErrors, errors.turnstile_token]);

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

    // Fetch countries on component mount
    useEffect(() => {
        const fetchCountries = async () => {
            setCountriesLoading(true);
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
    }, [setData]);

    // Check location permission when URL has started=true parameter
    useEffect(() => {
        const checkLocationPermission = async () => {
            if (typeof window !== 'undefined') {
                const urlParams = new URLSearchParams(window.location.search);
                const hasStartedParam = urlParams.get('started') === 'true';

                if (hasStartedParam && !locationPermissionGranted && countries.length > 0) {
                    try {
                        await requestLocationPermission();
                    } catch (_error) { // eslint-disable-line @typescript-eslint/no-unused-vars
                        // If location permission is denied, redirect back while preserving parameters except 'started' to avoid loops
                        const currentParams = new URLSearchParams(window.location.search);
                        currentParams.delete('started');
                        const query = currentParams.toString();
                        window.location.href = query ? `/da/register?${query}` : '/da/register';
                    }
                }
            }
        };

        checkLocationPermission();
    }, [locationPermissionGranted, countries]); // eslint-disable-line react-hooks/exhaustive-deps

    // Extract referral code from URL parameters or fetch admin's code
    useEffect(() => {
        const extractReferralCode = async () => {
            if (typeof window !== 'undefined') {
                const urlParams = new URLSearchParams(window.location.search);
                // Extract referral code from URL parameters (ref, referral, or code)
                const referralCode = urlParams.get('ref') || urlParams.get('referral') || urlParams.get('code');

                if (referralCode && referralCode.length >= 6 && /^[A-Za-z0-9]{6,8}$/.test(referralCode)) {
                    setData('referral_code', referralCode.toUpperCase());
                } else {
                    // No referral code in URL, fetch admin's referral code
                    try {
                        const response = await fetch('/api/admin-referral-code');
                        const result = await response.json();
                        if (response.ok && result.referral_code) {
                            setData('referral_code', result.referral_code);
                        }
                    } catch (error) {
                        console.log('Could not fetch admin referral code:', error);
                    }
                }
            }
        };

        extractReferralCode();
    }, [setData]);

    // Validate referral code when it changes
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            validateReferralCode(data.referral_code);
        }, 500); // Debounce validation

        return () => clearTimeout(timeoutId);
    }, [data.referral_code]);

    // Validate email uniqueness when it changes
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            validateEmailUniqueness(data.email);
        }, 500); // Debounce validation

        return () => clearTimeout(timeoutId);
    }, [data.email]); // eslint-disable-line react-hooks/exhaustive-deps

    // Validate national ID uniqueness when it changes
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            validateNationalIdUniqueness(data.national_id);
        }, 500); // Debounce validation

        return () => clearTimeout(timeoutId);
    }, [data.national_id]); // eslint-disable-line react-hooks/exhaustive-deps

    // Validate phone uniqueness when it changes
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            validatePhoneUniqueness(data.phone);
        }, 500); // Debounce validation

        return () => clearTimeout(timeoutId);
    }, [data.phone]); // eslint-disable-line react-hooks/exhaustive-deps

    const handlePlatformChange = (platform: string, checked: boolean | "indeterminate") => {
        const isChecked = checked === true;
        if (isChecked) {
            setData('platforms', [...data.platforms, platform]);
        } else {
            setData('platforms', data.platforms.filter((p: string) => p !== platform));
        }
    };

    const updateLabels = (countryId: string) => {
        const selectedCountry = countries.find(country => country.id.toString() === countryId);
        if (selectedCountry) {
            setCountyLabel(selectedCountry.county_label);
            setSubcountyLabel(selectedCountry.subcounty_label);
        } else {
            setCountyLabel('County');
            setSubcountyLabel('Sub-county');
        }
    };

    const fetchCounties = async (countryId: number) => {
        setCountiesLoading(true);
        try {
            const response = await fetch(`/api/counties?country_id=${countryId}`);
            const data = await response.json();
            setCounties(data);
        } catch (error) {
            console.error('Failed to fetch counties:', error);
            setCounties([]);
        } finally {
            setCountiesLoading(false);
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
        } catch (_error) { // eslint-disable-line @typescript-eslint/no-unused-vars
            setReferralValid(false);
            setReferralMessage('Failed to validate referral code');
        } finally {
            setReferralValidating(false);
        }
    };

    const validateEmailUniqueness = async (email: string) => {
        if (!email || !validateEmail(email)) {
            setEmailValid(null);
            setEmailMessage('');
            return;
        }

        setEmailValidating(true);
        try {
            const response = await fetch('/api/validate-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({ email: email.toLowerCase() }),
            });

            const result = await response.json();

            if (response.ok) {
                setEmailValid(true);
                setEmailMessage('Email address is available');
            } else {
                setEmailValid(false);
                setEmailMessage(result.message || 'This email address is already registered');
            }
        } catch (_error) { // eslint-disable-line @typescript-eslint/no-unused-vars
            setEmailValid(false);
            setEmailMessage('Failed to validate email address');
        } finally {
            setEmailValidating(false);
        }
    };

    const validateNationalIdUniqueness = async (nationalId: string) => {
        if (!nationalId || !validateNationalId(nationalId)) {
            setNationalIdValid(null);
            setNationalIdMessage('');
            return;
        }

        setNationalIdValidating(true);
        try {
            const response = await fetch('/api/validate-national-id', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({ national_id: nationalId }),
            });

            const result = await response.json();

            if (response.ok) {
                setNationalIdValid(true);
                setNationalIdMessage('National ID is available');
            } else {
                setNationalIdValid(false);
                setNationalIdMessage(result.message || 'This National ID is already registered');
            }
        } catch (_error) { // eslint-disable-line @typescript-eslint/no-unused-vars
            setNationalIdValid(false);
            setNationalIdMessage('Failed to validate National ID');
        } finally {
            setNationalIdValidating(false);
        }
    };

    const validatePhoneUniqueness = async (phone: string) => {
        if (!phone || !validatePhone(phone)) {
            setPhoneValid(null);
            setPhoneMessage('');
            return;
        }

        setPhoneValidating(true);
        try {
            const response = await fetch('/api/validate-phone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({ phone: phone }),
            });

            const result = await response.json();

            if (response.ok) {
                setPhoneValid(true);
                setPhoneMessage('Phone number is available');
            } else {
                setPhoneValid(false);
                setPhoneMessage(result.message || 'This phone number is already registered');
            }
        } catch (_error) { // eslint-disable-line @typescript-eslint/no-unused-vars
            setPhoneValid(false);
            setPhoneMessage('Failed to validate phone number');
        } finally {
            setPhoneValidating(false);
        }
    };

    interface LocationData {
        countryName?: string;
        country?: string;
        countyName?: string;
        county?: string;
        subcountyName?: string;
        subcounty?: string;
        wardName?: string;
        ward?: string;
        [key: string]: any; // eslint-disable-line @typescript-eslint/no-explicit-any
    }

    const autoFillLocation = async (locationData: LocationData) => {
        console.log('Auto-filling location with data:', locationData);
        console.log('Full location data structure:', JSON.stringify(locationData, null, 2));

        // Wait for countries to be loaded if they're not ready yet
        if (countries.length === 0) {
            console.log('Countries not loaded yet, waiting...');
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        if (countries.length === 0) {
            console.log('Countries still not loaded, skipping auto-fill');
            return;
        }

        // Check for country name with different possible field names
        const countryName = locationData.countryName || locationData.country;
        console.log('Country name from location data:', countryName);

        if (countryName) {
            console.log('Looking for country:', countryName);
            console.log('Available countries:', countries.map(c => c.name));
            
            // Enhanced country matching with exact match priority
            const detectedCountry = countries.find(country =>
                country.name.toLowerCase() === countryName.toLowerCase()
            ) || countries.find(country =>
                country.name.toLowerCase().includes(countryName.toLowerCase()) ||
                countryName.toLowerCase().includes(country.name.toLowerCase())
            );

            console.log('Detected country:', detectedCountry);

            if (detectedCountry) {
                // Set country and update labels
                setData('country', detectedCountry.id.toString());
                updateLabels(detectedCountry.id.toString());

                // Fetch counties for this country
                await fetchCounties(detectedCountry.id);

                // Wait for state to update
                await new Promise(resolve => setTimeout(resolve, 200));

                // Get fresh counties data
                const countiesResponse = await fetch(`/api/counties?country_id=${detectedCountry.id}`);
                const countiesData = await countiesResponse.json();
                console.log('Fetched counties:', countiesData);
                console.log('Available county names:', countiesData.map((c: County) => c.name));

                // Try to match county - check multiple possible field names
                const subdivisionName = locationData.principalSubdivision || locationData.state || locationData.region || locationData.administrativeArea || locationData.locality;
                console.log('Subdivision name from location data:', subdivisionName);

                if (subdivisionName) {
                    console.log('Looking for county:', subdivisionName);
                    
                    // Enhanced county matching with exact match priority
                    const detectedCounty = countiesData.find((county: County) =>
                        county.name.toLowerCase() === subdivisionName.toLowerCase()
                    ) || countiesData.find((county: County) =>
                        county.name.toLowerCase().includes(subdivisionName.toLowerCase()) ||
                        subdivisionName.toLowerCase().includes(county.name.toLowerCase())
                    );

                    console.log('Detected county:', detectedCounty);

                    if (detectedCounty) {
                        setData('county', detectedCounty.id.toString());

                        // Fetch subcounties for this county
                        await fetchSubcounties(detectedCounty.id);

                        // Wait for state to update
                        await new Promise(resolve => setTimeout(resolve, 200));

                        // Get fresh subcounties data
                        const subcountiesResponse = await fetch(`/api/subcounties?county_id=${detectedCounty.id}`);
                        const subcountiesData = await subcountiesResponse.json();
                        console.log('Fetched subcounties:', subcountiesData);
                        console.log('Available subcounty names:', subcountiesData.map((s: Subcounty) => s.name));

                        // Try to match subcounty/city - check multiple possible field names
                        const cityName = locationData.city || locationData.locality || locationData.localityInfo?.localityName;
                        console.log('City name from location data:', cityName);

                        if (cityName) {
                            console.log('Looking for subcounty/city:', cityName);
                            const detectedSubcounty = subcountiesData.find((subcounty: Subcounty) =>
                                subcounty.name.toLowerCase() === cityName.toLowerCase() ||
                                subcounty.name.toLowerCase().includes(cityName.toLowerCase()) ||
                                cityName.toLowerCase().includes(subcounty.name.toLowerCase())
                            );

                            console.log('Detected subcounty:', detectedSubcounty);

                            if (detectedSubcounty) {
                                setData('subcounty', detectedSubcounty.id.toString());

                                // Fetch wards for this subcounty
                                await fetchWards(detectedSubcounty.id);

                                console.log('Location auto-filled successfully');
                            } else {
                                console.log('No matching subcounty found for:', cityName);
                            }
                        } else {
                            console.log('No city/locality data in location response');
                        }
                    } else {
                        console.log('No matching county found for:', subdivisionName);
                    }
                } else {
                    console.log('No subdivision data in location response');
                }
            } else {
                console.log('No matching country found for:', countryName);
                console.log('Available countries:', countries.map(c => c.name));
            }
        } else {
            console.log('No country name in location data');
        }
    };

    const requestLocationPermission = () => {
        return new Promise<void>((resolve, reject) => {
            if (!navigator.geolocation) {
                toast.error('Geolocation is not supported by this browser.');
                reject(new Error('Geolocation not supported'));
                return;
            }

            setLocationLoading(true);

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const { latitude, longitude } = position.coords;

                    // Store coordinates
                    setData('latitude', latitude.toString());
                    setData('longitude', longitude.toString());

                    // Try to get location details using reverse geocoding
                    try {
                        const response = await fetch(
                            `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`
                        );
                        const locationData = await response.json();
                        console.log('Reverse geocoding response:', locationData);

                        // Auto-populate location dropdowns
                        await autoFillLocation(locationData);
                    } catch (error) {
                        console.log('Could not get detailed location info:', error);
                    }

                    setLocationPermissionGranted(true);
                    setLocationLoading(false);
                    resolve();
                },
                (error) => {
                    setLocationLoading(false);
                    let errorMessage = 'Unable to retrieve your location. ';

                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Location access denied. Please enable location services and try again.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Location request timed out.';
                            break;
                        default:
                            errorMessage += 'An unknown error occurred.';
                    }

                    toast.error(errorMessage);
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000, // 5 minutes
                }
            );
        });
    };

    // Validation functions
    const validateEmail = (email: string): boolean => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    const validatePhone = (phone: string): boolean => {
        const phoneRegex = /^0[\d\s\-()]{9,}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    };

    const validateNationalId = (nationalId: string): boolean => {
        const nationalIdRegex = /^\d+$/;
        return nationalIdRegex.test(nationalId.trim());
    };

    const clearFieldError = useCallback((field: string) => {
        if (errors[field as keyof typeof errors]) {
            clearErrors(field as keyof typeof errors);
        }
    }, [errors, clearErrors]);

    const updateData = (field: string, value: string | string[] | boolean) => {
        setData(field as keyof typeof data, value);
        clearFieldError(field);
    };

    const validateStep = (step: Step): boolean => {
        // Clear all existing errors first
        clearErrors();
        
        let hasErrors = false;

        if (step === 'personal') {
            if (!data.full_name.trim()) {
                setError('full_name', 'Full name is required');
                hasErrors = true;
            }
            if (!data.national_id.trim()) {
                setError('national_id', 'National ID is required');
                hasErrors = true;
            } else if (!validateNationalId(data.national_id)) {
                setError('national_id', 'National ID must contain only numbers');
                hasErrors = true;
            } else if (nationalIdValid === false) {
                setError('national_id', nationalIdMessage || 'This National ID is already registered');
                hasErrors = true;
            }
            if (!data.dob) {
                setError('dob', 'Date of birth is required');
                hasErrors = true;
            }
            if (!data.gender) {
                setError('gender', 'Gender is required');
                hasErrors = true;
            }
            if (!data.email.trim()) {
                setError('email', 'Email address is required');
                hasErrors = true;
            } else if (!validateEmail(data.email)) {
                setError('email', 'Please enter a valid email address');
                hasErrors = true;
            } else if (emailValid === false) {
                setError('email', emailMessage || 'This email address is already registered');
                hasErrors = true;
            }
            if (!data.country.trim()) {
                setError('country', 'Country is required');
                hasErrors = true;
            }
            if (!data.county.trim()) {
                setError('county', 'County is required');
                hasErrors = true;
            }
            if (!data.subcounty.trim()) {
                setError('subcounty', 'Sub-county is required');
                hasErrors = true;
            }
            if (!data.ward.trim()) {
                setError('ward', 'Ward is required');
                hasErrors = true;
            }
            if (!data.address.trim()) {
                setError('address', 'Address is required');
                hasErrors = true;
            }
            if (!data.phone.trim()) {
                setError('phone', 'Phone number is required');
                hasErrors = true;
            } else if (!validatePhone(data.phone)) {
                setError('phone', 'Please enter a valid phone number');
                hasErrors = true;
            } else if (phoneValid === false) {
                setError('phone', phoneMessage || 'This phone number is already registered');
                hasErrors = true;
            }
        } else if (step === 'social') {
            if (data.platforms.length === 0) {
                setError('platforms', 'Please select at least one social media platform');
                hasErrors = true;
            }
            if (!data.followers) {
                setError('followers', 'Follower count range is required');
                hasErrors = true;
            }
            if (!data.communication_channel) {
                setError('communication_channel', 'Preferred communication channel is required');
                hasErrors = true;
            }
        } else if (step === 'account') {
            if (!data.wallet_type) {
                setError('wallet_type', 'Wallet type is required');
                hasErrors = true;
            }
            if (!data.wallet_pin || data.wallet_pin.length < 4) {
                setError('wallet_pin', 'PIN must be at least 4 digits');
                hasErrors = true;
            }
            if (data.wallet_pin !== data.confirm_pin) {
                setError('confirm_pin', 'PINs do not match');
                hasErrors = true;
            }
            if (!data.terms) {
                setError('terms', 'You must accept the terms and conditions');
                hasErrors = true;
            }
            if (!data.turnstile_token) {
                // Check if we're in development environment
                const hostname = window.location.hostname;
                const isDevelopment = hostname === 'localhost' || 
                                    hostname.includes('.hostingersite.com') || 
                                    hostname.includes('.ngrok') || 
                                    hostname.includes('.vercel.app');
                
                if (!isDevelopment) {
                    setError('turnstile_token', 'Please complete the security verification');
                    hasErrors = true;
                }
            }
        }

        return !hasErrors;
    };

    const nextStep = () => {
        if (validateStep(currentStep)) {
            const nextIndex = currentStepIndex + 1;
            if (nextIndex < steps.length) {
                setCurrentStep(steps[nextIndex].id as Step);
            }
        } else {
            // Show toast error for validation failures
            toast.error('Please fill in all required fields correctly before continuing.', {
                autoClose: 3000
            });
        }
    };

    const prevStep = () => {
        const prevIndex = currentStepIndex - 1;
        if (prevIndex >= 0) {
            setCurrentStep(steps[prevIndex].id as Step);
        }
    };

    const handleRegistrationError = (errors: any) => { // eslint-disable-line @typescript-eslint/no-explicit-any
        console.log('Registration error details:', errors);

        // Check if this is actually a successful response coming through onError
        if (typeof errors === 'object' && errors !== null && errors.message === 'DA registered successfully') {
            console.log('Success response received through error handler:', errors);
            setProcessing(false);
            reset();
            toast.success('🎉 Registration successful! Welcome to Daya!\nCheck your email for your referral link.', {
                autoClose: 3000
            });
                    // Redirect to the home page after a short delay
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 3000);
            return;
        }

        // Check if errors is an object with field-specific errors
        if (typeof errors === 'object' && errors !== null) {
            // Get the first error message from validation errors
            const errorKeys = Object.keys(errors);
            if (errorKeys.length > 0) {
                const firstErrorKey = errorKeys[0];
                const firstError = errors[firstErrorKey];

                // Handle array of errors or single error
                const errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;

                // Show specific field error
                toast.error(`Error: ${errorMessage}\nPlease check the ${firstErrorKey.replace('_', ' ')} field`, {
                    autoClose: 6000
                });
                return;
            }
        }

        // Check for specific error messages from the server
        if (typeof errors === 'string') {
            if (errors.includes('email') && errors.includes('unique')) {
                toast.error('This email address is already registered. Please use a different email or try logging in.', {
                    autoClose: 7000
                });
                return;
            }
            if (errors.includes('national_id') && errors.includes('unique')) {
                toast.error('This National ID is already registered in our system.', {
                    autoClose: 6000
                });
                return;
            }
            if (errors.includes('referral_code')) {
                toast.error('Invalid referral code. Please check and try again.', {
                    autoClose: 5000
                });
                return;
            }
        }

        // Check for network/server errors
        if (errors?.message) {
            if (errors.message.includes('network') || errors.message.includes('fetch')) {
                toast.error('Network error. Please check your internet connection and try again.', {
                    autoClose: 5000
                });
                return;
            }
            if (errors.message.includes('500') || errors.message.includes('server')) {
                toast.error('Server error. Please try again in a few moments.', {
                    autoClose: 5000
                });
                return;
            }
        }

        // Default fallback error
        toast.error('Registration failed. Please check your information and try again.\nIf the problem persists, contact support.', {
            autoClose: 5000
        });
    };

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (currentStep === 'account') {
            if (!validateStep('account')) {
                console.log('Validation failed for account step');
                console.log('Form data:', data);
                toast.error('Please fill all required fields correctly before submitting.\nCheck the form for any highlighted errors.', {
                    autoClose: 4000
                });
                return;
            }
            console.log('Submitting form with data:', data);
            setProcessing(true);

            try {
                // Transform data to match backend expectations
                const submitData = {
                    ...data,
                    ward_id: data.ward, // Map ward to ward_id for backend
                };

                const response = await fetch('/api/da/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(submitData),
                });

                const result = await response.json();
                
                if (response.ok) {
                    console.log('Success response:', result);
                    setProcessing(false);
                    reset();
                    toast.success('🎉 Registration successful! Welcome to Daya!\nCheck your email for your referral link.', {
                        autoClose: 3000
                    });
                    // Redirect to the home page after a short delay
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 3000);
                } else {
                    console.log('Error response:', result);
                    setProcessing(false);
                    handleRegistrationError(result);
                }
            } catch (error) {
                console.log('Network error:', error);
                setProcessing(false);
                toast.error('Network error. Please check your internet connection and try again.', {
                    autoClose: 5000
                });
            }
        } else {
            nextStep();
        }
    };

    const steps = [
        { id: 'personal', title: 'Personal Information', icon: User, description: 'Your personal details and location', color: 'from-blue-500 to-cyan-500' },
        { id: 'social', title: 'Social Media Presence', icon: Instagram, description: 'Your social platforms and reach', color: 'from-purple-500 to-pink-500' },
        { id: 'account', title: 'Account Setup', icon: Wallet, description: 'Set up your wallet and complete registration', color: 'from-green-500 to-emerald-500' },
    ];

    const currentStepIndex = steps.findIndex(step => step.id === currentStep);

    const renderStepContent = () => {
        switch (currentStep) {
            case 'personal':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-blue-50 to-cyan-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-blue-100 dark:border-blue-600">
                            <div className="flex items-center gap-3 mb-4">
                                <User className="w-6 h-6 text-blue-600" />
                                <div>
                                    <h3 className="font-semibold text-blue-900 dark:text-blue-300">Personal Information</h3>
                                    <p className="text-sm text-blue-700 dark:text-blue-300">Tell us about yourself</p>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="referral_code" className="text-sm font-medium mb-2 block">
                                    Referral Code <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="referral_code"
                                    type="text"
                                    value={data.referral_code}
                                    onChange={(e) => updateData('referral_code', e.target.value)}
                                    placeholder="Enter referring DA's code"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                <InputError message={errors.referral_code} />
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

                            <div>
                                <Label htmlFor="full_name" className="text-sm font-medium mb-2 block">
                                    Full Name <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="full_name"
                                    type="text"
                                    value={data.full_name}
                                    onChange={(e) => updateData('full_name', e.target.value)}
                                    placeholder="Enter your full name"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                <InputError message={errors.full_name} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="national_id" className="text-sm font-medium mb-2 block">
                                    National ID <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="national_id"
                                    type="text"
                                    value={data.national_id}
                                    onChange={(e) => updateData('national_id', e.target.value)}
                                    placeholder="Enter your national ID"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                <InputError message={errors.national_id} />
                                <div className="mt-2 flex items-center space-x-2">
                                    {nationalIdValidating ? (
                                        <div className="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 dark:border-blue-400"></div>
                                            <span className="text-sm">Checking availability...</span>
                                        </div>
                                    ) : nationalIdValid === true ? (
                                        <div className="flex items-center space-x-2 text-green-600 dark:text-green-400">
                                            <CheckCircle className="h-4 w-4" />
                                            <span className="text-sm">{nationalIdMessage}</span>
                                        </div>
                                    ) : nationalIdValid === false ? (
                                        <div className="flex items-center space-x-2 text-red-600 dark:text-red-400">
                                            <XCircle className="h-4 w-4" />
                                            <span className="text-sm">{nationalIdMessage}</span>
                                        </div>
                                    ) : null}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="dob" className="text-sm font-medium mb-2 block">
                                    Date of Birth <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="dob"
                                    type="date"
                                    value={data.dob}
                                    onChange={(e) => updateData('dob', e.target.value)}
                                    max="2007-11-12"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                <InputError message={errors.dob} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="gender" className="text-sm font-medium mb-2 block">
                                    Gender <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.gender} onValueChange={(value) => updateData('gender', value)}>
                                    <SelectTrigger className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none">
                                        <SelectValue placeholder="Select gender" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-blue-300 dark:border-blue-600/20">
                                        <SelectItem value="-">-</SelectItem>
                                        <SelectItem value="male">Male</SelectItem>
                                        <SelectItem value="female">Female</SelectItem>
                                        <SelectItem value="other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.gender} />
                            </div>

                            <div>
                                <Label htmlFor="email" className="text-sm font-medium mb-2 block">
                                    Email Address <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => updateData('email', e.target.value)}
                                    placeholder="primary@email.com"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                <InputError message={errors.email} />
                                <div className="mt-2 flex items-center space-x-2">
                                    {emailValidating ? (
                                        <div className="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 dark:border-blue-400"></div>
                                            <span className="text-sm">Checking availability...</span>
                                        </div>
                                    ) : emailValid === true ? (
                                        <div className="flex items-center space-x-2 text-green-600 dark:text-green-400">
                                            <CheckCircle className="h-4 w-4" />
                                            <span className="text-sm">{emailMessage}</span>
                                        </div>
                                    ) : emailValid === false ? (
                                        <div className="flex items-center space-x-2 text-red-600 dark:text-red-400">
                                            <XCircle className="h-4 w-4" />
                                            <span className="text-sm">{emailMessage}</span>
                                        </div>
                                    ) : null}
                                </div>
                            </div>
                        </div>

                        <div className="bg-gradient-to-br from-cyan-50 to-blue-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-cyan-100 dark:border-cyan-600">
                            <div className="flex items-center gap-3 mb-4">
                                <MapPin className="w-5 h-5 text-cyan-600" />
                                <div>
                                    <h4 className="font-medium text-cyan-900 dark:text-cyan-300 mb-2">Location Information</h4>
                                    <p className="text-sm text-cyan-700 dark:text-cyan-300">Your location helps us connect you with local opportunities</p>
                                </div>
                            </div>

                            {/* Location Permission Button */}
                            {!locationPermissionGranted && (
                                <div className="mb-6 p-4 bg-cyan-50 dark:bg-slate-700 border border-cyan-200 dark:border-cyan-600 rounded-lg">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <Globe className="w-5 h-5 text-cyan-600" />
                                            <div>
                                                <p className="font-medium text-cyan-900 dark:text-cyan-300">Auto-fill location</p>
                                                <p className="text-sm text-cyan-700 dark:text-cyan-400">Grant location access to automatically fill your geographic details</p>
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            onClick={requestLocationPermission}
                                            disabled={locationLoading}
                                            className="bg-cyan-600 hover:bg-cyan-700  text-slate-900 px-4 py-2 text-sm"
                                        >
                                            {locationLoading ? (
                                                <>
                                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                                    Getting location...
                                                </>
                                            ) : (
                                                <>
                                                    <MapPin className="w-4 h-4 mr-2" />
                                                    Use My Location
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {locationPermissionGranted && (
                                <div className="mb-6 p-4 bg-green-50 dark:bg-slate-700 border border-green-200 dark:border-green-600 rounded-lg">
                                    <div className="flex items-center gap-3">
                                        <CheckCircle className="w-5 h-5 text-green-600" />
                                        <div>
                                            <p className="font-medium text-green-900 dark:text-green-300">Location detected</p>
                                            <p className="text-sm text-green-700 dark:text-green-400">Your location has been automatically filled</p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="country" className="text-sm font-medium mb-2 block">
                                        Country <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Select value={data.country} onValueChange={(value) => {
                                        setData('country', value);
                                        setData('county', '');
                                        setData('subcounty', '');
                                        setData('ward', '');
                                        updateLabels(value);
                                        setCounties([]);
                                        setSubcounties([]);
                                        setWards([]);
                                        const selectedCountry = countries.find(c => c.id.toString() === value);
                                        if (selectedCountry) {
                                            fetchCounties(selectedCountry.id);
                                        }
                                    }} disabled={countriesLoading || countries.length === 0}>
                                        <SelectTrigger className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none">
                                            <SelectValue placeholder={countriesLoading ? "Loading countries..." : "Select Country"} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white dark:bg-slate-800 border-cyan-300 dark:border-cyan-600/20">
                                            {countries.map((country) => (
                                                <SelectItem key={country.id} value={country.id.toString()}>
                                                    {country.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.country} />
                                </div>

                                <div>
                                    <Label htmlFor="county" className="text-sm font-medium mb-2 block">
                                        {countyLabel} <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Select value={data.county} onValueChange={(value) => {
                                        setData('county', value);
                                        setData('subcounty', '');
                                        setData('ward', '');
                                        setSubcounties([]);
                                        setWards([]);
                                        const selectedCounty = counties.find(c => c.id.toString() === value);
                                        if (selectedCounty) {
                                            fetchSubcounties(selectedCounty.id);
                                        }
                                    }} disabled={countiesLoading || !data.country}>
                                        <SelectTrigger className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none">
                                            <SelectValue placeholder={countiesLoading ? "Loading..." : `Select ${countyLabel.toLowerCase()}`} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white dark:bg-slate-800 border-cyan-300 dark:border-cyan-600/20">
                                            {counties.map((county) => (
                                                <SelectItem key={county.id} value={county.id.toString()}>
                                                    {county.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.county} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <Label htmlFor="subcounty" className="text-sm font-medium mb-2 block">
                                        {subcountyLabel} <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Select value={data.subcounty} onValueChange={(value) => {
                                        setData('subcounty', value);
                                        setData('ward', '');
                                        setWards([]);
                                        const selectedSubcounty = subcounties.find(s => s.id.toString() === value);
                                        if (selectedSubcounty) {
                                            fetchWards(selectedSubcounty.id);
                                        }
                                    }} disabled={subcountiesLoading || !data.county}>
                                        <SelectTrigger className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none">
                                            <SelectValue placeholder={subcountiesLoading ? "Loading..." : `Select ${subcountyLabel.toLowerCase()}`} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white dark:bg-slate-800 border-cyan-300 dark:border-cyan-600/20">
                                            {subcounties.map((subcounty) => (
                                                <SelectItem key={subcounty.id} value={subcounty.id.toString()}>
                                                    {subcounty.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.subcounty} />
                                </div>

                                <div>
                                    <Label htmlFor="ward" className="text-sm font-medium mb-2 block">
                                        Ward <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Select value={data.ward} onValueChange={(value) => updateData('ward', value)} disabled={wardsLoading || !data.subcounty}>
                                        <SelectTrigger className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none">
                                            <SelectValue placeholder={wardsLoading ? "Loading..." : "Select ward"} />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white dark:bg-slate-800 border-cyan-300 dark:border-cyan-600/20">
                                            {wards.map((ward) => (
                                                <SelectItem key={ward.id} value={ward.id.toString()}>
                                                    {ward.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.ward} />
                                </div>
                            </div>

                            <div className="mt-4">
                                <Label htmlFor="address" className="text-sm font-medium mb-2 block">
                                    Address <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="address"
                                    type="text"
                                    value={data.address}
                                    onChange={(e) => updateData('address', e.target.value)}
                                    placeholder="Enter your full address"
                                    className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none"
                                />
                                <InputError message={errors.address} />
                            </div>

                            <div className="mt-4">
                                <Label htmlFor="phone" className="text-sm font-medium mb-2 block">
                                    Phone Number <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={data.phone}
                                    onChange={(e) => updateData('phone', e.target.value)}
                                    placeholder="e.g., 0712 345678"
                                    className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none"
                                />
                                <InputError message={errors.phone} />
                                <div className="mt-2 flex items-center space-x-2">
                                    {phoneValidating ? (
                                        <div className="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 dark:border-blue-400"></div>
                                            <span className="text-sm">Checking availability...</span>
                                        </div>
                                    ) : phoneValid === true ? (
                                        <div className="flex items-center space-x-2 text-green-600 dark:text-green-400">
                                            <CheckCircle className="h-4 w-4" />
                                            <span className="text-sm">{phoneMessage}</span>
                                        </div>
                                    ) : phoneValid === false ? (
                                        <div className="flex items-center space-x-2 text-red-600 dark:text-red-400">
                                            <XCircle className="h-4 w-4" />
                                            <span className="text-sm">{phoneMessage}</span>
                                        </div>
                                    ) : null}
                                </div>
                            </div>
                        </div>
                    </div>
                );

            case 'social':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-purple-50 to-pink-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-purple-100 dark:border-purple-600">
                            <div className="flex items-center gap-3 mb-4">
                                <Instagram className="w-6 h-6 text-purple-600" />
                                <div>
                                    <h3 className="font-semibold text-purple-900 dark:text-purple-300">Social Media Presence</h3>
                                    <p className="text-sm text-purple-700 dark:text-purple-300">Show us your social media reach</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <Label className="text-sm font-medium mb-4 block">
                                Which social platforms do you frequent? <span className='text-red-500 dark:text-red-400'>*</span>
                            </Label>
                            <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                                {[
                                    { value: 'instagram', label: 'Instagram', icon: Instagram },
                                    { value: 'twitter', label: 'Twitter', icon: Twitter },
                                    { value: 'facebook', label: 'Facebook', icon: Facebook },
                                    { value: 'whatsapp', label: 'WhatsApp', icon: MessageCircle },
                                    { value: 'linkedin', label: 'LinkedIn', icon: Linkedin },
                                    { value: 'tiktok', label: 'TikTok', icon: Music },
                                ].map((platform) => (
                                    <div key={platform.value} className="flex items-center space-x-2 p-3 border rounded-lg border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                                        <Checkbox
                                            id={platform.value}
                                            checked={data.platforms.includes(platform.value)}
                                            onCheckedChange={(checked) => handlePlatformChange(platform.value, checked)}
                                            className={`border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-500 focus:ring-purple-500 dark:focus:ring-purple-400 ${data.platforms.includes(platform.value) ? 'bg-purple-100 dark:bg-purple-700' : ''}`}
                                        />
                                        <Label htmlFor={platform.value} className="text-sm flex items-center gap-2 cursor-pointer">
                                            <platform.icon className="w-4 h-4" />
                                            {platform.label}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            <InputError message={errors.platforms} />
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="followers" className="text-sm font-medium mb-2 block">
                                    Total Followers <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.followers} onValueChange={(value) => updateData('followers', value)}>
                                    <SelectTrigger className="border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none">
                                        <SelectValue placeholder="Select Range" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-purple-300 dark:border-purple-600/20">
                                        <SelectItem value="less_than_1k">Less than 1K</SelectItem>
                                        <SelectItem value="1k_10k">1K–10K</SelectItem>
                                        <SelectItem value="10k_50k">10K–50K</SelectItem>
                                        <SelectItem value="50k_100k">50K–100K</SelectItem>
                                        <SelectItem value="100k_plus">100K+</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.followers} />
                            </div>

                            <div>
                                <Label htmlFor="communication_channel" className="text-sm font-medium mb-2 block">
                                    Preferred Communication Channel <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.communication_channel} onValueChange={(value) => updateData('communication_channel', value)}>
                                    <SelectTrigger className="border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none">
                                        <SelectValue placeholder="Select Channel" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-purple-300 dark:border-purple-600/20">
                                        <SelectItem value="whatsapp">WhatsApp</SelectItem>
                                        <SelectItem value="telegram">Telegram</SelectItem>
                                        <SelectItem value="email">Email</SelectItem>
                                        <SelectItem value="phone">Phone</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.communication_channel} />
                            </div>
                        </div>
                    </div>
                );

            case 'account':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-green-50 to-emerald-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-green-100 dark:border-green-600">
                            <div className="flex items-center gap-3 mb-4">
                                <Wallet className="w-6 h-6 text-green-600" />
                                <div>
                                    <h3 className="font-semibold text-green-900 dark:text-green-300">Account Setup</h3>
                                    <p className="text-sm text-green-700 dark:text-green-300">Set up your wallet and complete registration</p>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="wallet_type" className="text-sm font-medium mb-2 block">
                                    Preferred Wallet Type <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.wallet_type} onValueChange={(value) => updateData('wallet_type', value)}>
                                    <SelectTrigger className="border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-800 focus:border-green-500 dark:focus:border-green-400 focus:outline-none">
                                        <SelectValue placeholder="Select Wallet Type" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-green-300 dark:border-green-600/20">
                                        <SelectItem value="personal">Personal</SelectItem>
                                        <SelectItem value="business">Business</SelectItem>
                                        <SelectItem value="both">Both</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.wallet_type} />
                            </div>

                            <div>
                                <Label htmlFor="wallet_pin" className="text-sm font-medium mb-2 block">
                                    Wallet PIN (4-digit) <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="wallet_pin"
                                    type="password"
                                    value={data.wallet_pin}
                                    onChange={(e) => updateData('wallet_pin', e.target.value)}
                                    maxLength={4}
                                    placeholder="Enter 4-digit PIN"
                                    className="border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-800 focus:border-green-500 dark:focus:border-green-400 focus:outline-none"
                                />
                                <InputError message={errors.wallet_pin} />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="confirm_pin" className="text-sm font-medium mb-2 block">
                                Confirm Wallet PIN <span className='text-red-500 dark:text-red-400'>*</span>
                            </Label>
                            <Input
                                id="confirm_pin"
                                type="password"
                                value={data.confirm_pin}
                                onChange={(e) => updateData('confirm_pin', e.target.value)}
                                maxLength={4}
                                placeholder="Confirm your PIN"
                                className="border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-800 focus:border-green-500 dark:focus:border-green-400 focus:outline-none"
                            />
                            <InputError message={errors.confirm_pin} />
                        </div>

                        <div className="bg-emerald-50 dark:bg-slate-700 border-l-4 border-emerald-400 dark:border-emerald-600 p-4 rounded-r-lg">
                            <p className="text-sm text-emerald-800 dark:text-emerald-300">
                                <strong>Important:</strong> Keep your PIN secure and do not share it with anyone. You'll need it to access your earnings and manage your account.
                            </p>
                        </div>

                        {/* Security Verification */}
                        <div className="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-slate-700 dark:to-slate-800 p-6 rounded-xl border-2 border-amber-100 dark:border-amber-600">
                            <div className="flex items-center gap-3 mb-4">
                                <Shield className="w-5 h-5 text-amber-600" />
                                <div>
                                    <h4 className="font-medium text-amber-900 dark:text-amber-300">Security Verification</h4>
                                    <p className="text-sm text-amber-700 dark:text-amber-400">Complete the security check below to verify you're human</p>
                                </div>
                            </div>
                            
                            {!turnstileLoaded ? (
                                <div className="flex items-center justify-center gap-2 p-4 bg-amber-100 dark:bg-slate-600 rounded-lg">
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-amber-600"></div>
                                    <span className="text-amber-700 dark:text-amber-300">Loading security verification...</span>
                                </div>
                            ) : data.turnstile_token === 'dev-bypass-token' ? (
                                <div className="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <p className="text-blue-700 dark:text-blue-400 text-sm flex items-center gap-2 justify-center">
                                        <Shield className="w-4 h-4" />
                                        Security verification bypassed for development environment
                                    </p>
                                </div>
                            ) : (
                                <div>
                                    <div className="flex justify-center mb-3">
                                        <div ref={turnstileRef}></div>
                                    </div>
                                    {turnstileError && turnstileError !== 'Development mode: Security verification bypassed' && (
                                        <div className="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                            <p className="text-red-700 dark:text-red-400 text-sm flex items-center gap-2 justify-center">
                                                <AlertCircle className="w-4 h-4" />
                                                {turnstileError}
                                            </p>
                                            <div className="flex justify-center mt-2">
                                                <button
                                                    type="button"
                                                    onClick={resetTurnstile}
                                                    className="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 underline"
                                                >
                                                    Try again
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                            
                            {(errors.turnstile_token || (!data.turnstile_token && currentStep === 'account')) && !turnstileError && (
                                <div className="text-center">
                                    <InputError message={errors.turnstile_token || 'Please complete the security verification'} />
                                </div>
                            )}
                        </div>

                        <div className="bg-gradient-to-r from-green-50 to-purple-50 dark:from-slate-700 dark:to-slate-800 p-6 rounded-xl border-2 border-green-100 dark:border-green-600">
                            <div className="flex items-start gap-3">
                                <FileText className="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" />
                                <div className="flex-1">
                                    <h4 className="font-medium text-green-900 dark:text-green-300 mb-3">Terms & Conditions</h4>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="terms"
                                            checked={data.terms}
                                            onCheckedChange={(checked) => updateData('terms', checked === true)}
                                            className={`border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-500 focus:ring-green-500 dark:focus:ring-green-400 ${data.terms ? 'bg-green-100 dark:bg-green-700' : ''}`}
                                        />
                                        <Label htmlFor="terms" className="text-sm text-green-800 dark:text-green-300 font-medium">
                                            I agree to the <a href="https://www.daya.africa/TnC" target="_blank" rel="noopener noreferrer" className="underline text-green-800 dark:text-green-300 hover:text-green-900 dark:hover:text-green-200">terms and conditions</a> <span className='text-red-500 dark:text-red-400'>*</span>
                                        </Label>
                                    </div>
                                    <InputError message={errors.terms} />
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
        <div className="h-screen bg-background text-foreground overflow-y-auto bg-white dark:from-slate-700 dark:via-slate-800 dark:to-slate-700">

            {!showForm ? (
                /* Landing Page */
                <>
                    {/* Hero Section with Gradient */}
                    <div className="max-w-5xl mx-auto px-2 md:px-4 py-8 mt-8 relative z-10 h-screen flex items-start 2xl:items-center justify-center">
                        <div className="relative max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
                            <div className="text-center">
                                <div className="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full mb-6">
                                    <Sparkles className="w-5 h-5 mr-2" />
                                    <span className="text-sm font-medium">Join Africa's Leading Digital Network</span>
                                </div>

                                <h1 className="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                                    Become a Digital
                                    <span className="block bg-gradient-to-r from-yellow-300 to-orange-300 bg-clip-text text-transparent">
                                        Ambassador
                                    </span>
                                </h1>

                                <p className="text-xl md:text-2xl  text-slate-900 max-w-3xl mx-auto mb-12 dark:text-slate-300">
                                    Join our community of influencers and earn rewards while promoting Daya across Africa
                                </p>

                                {/* Earnings Potential Section */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto mb-12">
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-3">
                                            <Award className="w-8 h-8 mr-2" />
                                            <div className="text-2xl font-bold">5% Commission</div>
                                        </div>
                                        <div className="text-sm  text-slate-900 dark:text-slate-300">Earn 5% of all earnings from every DCD you recruit</div>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-3">
                                            <TrendingUp className="w-8 h-8 mr-2" />
                                            <div className="text-2xl font-bold">Venture Shares</div>
                                        </div>
                                        <div className="text-sm  text-slate-900 dark:text-slate-300">Build ownership in the platform as you grow the network</div>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-3">
                                            <Users className="w-8 h-8 mr-2" />
                                            <div className="text-2xl font-bold">Residual Income</div>
                                        </div>
                                        <div className="text-sm  text-slate-900 dark:text-slate-300">Ongoing commissions from your recruited DCDs' scans</div>
                                    </div>
                                </div>

                                {/* Video Section with Modern Card */}
                                <Card className="shadow-2xl border-0 overflow-hidden mb-8 bg-gray-800/50 backdrop-blur-sm py-0">
                                    <CardContent className="p-0 !h-full">
                                        <div className="aspect-video bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center overflow-hidden relative">
                                            <iframe
                                                className="w-full h-full"
                                                src="https://www.youtube.com/embed/KBSQg6WPxtU"
                                                title="Digital Ambassador Program Explained"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowFullScreen
                                            />
                                        </div>
                                        <div className="bg-gradient-to-r from-green-50 to-purple-50 dark:from-slate-700 dark:to-slate-800 p-6 rounded-xl border-2 border-green-100 dark:border-green-600 mb-8">
                                            <p className="text-center text-sm text-gray-700 font-medium dark:text-slate-200">
                                                {/* tv Icon */}
                                                <span className="inline-block mr-2">📺</span>
                                                Watch this 2-minute explainer to learn how you can earn 5% commissions + venture shares
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Start Button */}
                                <div className="mb-12">
                                    <Button
                                        onClick={() => {
                                            setShowForm(true);
                                            // Update URL to make it shareable
                                            const url = new URL(window.location.href);
                                            url.searchParams.set('started', 'true');
                                            window.history.pushState({}, '', url.toString());
                                        }}
                                        className="!px-8 !py-8 text-lg font-semibold bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600  text-slate-900 rounded-xl shadow-2xl hover:shadow-yellow-500/25 transition-all duration-300 transform hover:scale-105"
                                    >
                                        <Sparkles className="w-6 h-6 mr-3" />
                                            Start Registration
                                        <ArrowRight className="w-6 h-6 ml-3" />
                                    </Button>
                                </div>

                                {/* Stats Bar */}
                                <div className="grid grid-cols-3 gap-4 max-w-3xl mx-auto">
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-2">
                                            <Users className="w-6 h-6 mr-2" />
                                            <div className="text-3xl font-bold">500+</div>
                                        </div>
                                        <div className="text-sm  text-slate-900 dark:text-slate-300">Active Ambassadors</div>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-2">
                                            <TrendingUp className="w-6 h-6 mr-2" />
                                            <div className="text-3xl font-bold">$50K+</div>
                                        </div>
                                        <div className="text-sm  text-slate-900 dark:text-slate-300">Commissions Paid</div>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-2">
                                            <Award className="w-6 h-6 mr-2" />
                                            <div className="text-3xl font-bold">98%</div>
                                        </div>
                                        <div className="text-sm  text-slate-900 dark:text-slate-300">Success Rate</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </>
            ) : (
                /* Form Section */
                <div className="max-w-5xl mx-auto px-2 md:px-4 py-8 mt-8 relative z-10">
                    <div className="mb-8 text-center animate-in slide-in-from-top-5 duration-700 flex items-center justify-center flex-col">
                        <h1 className="text-3xl md:text-6xl font-bold leading-tight">
                            Digital
                            <span className="block bg-gradient-to-r from-yellow-300 to-orange-300 bg-clip-text text-transparent">
                                Ambassador
                            </span>
                            Registration
                        </h1>
                        <span className="w-32 h-1 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full"></span>
                    </div>

                    {/* Progress Indicator */}
                    <div className="mb-10 animate-in slide-in-from-top-5 duration-700">
                        <div className="relative">
                            <div className="absolute top-5 left-0 right-0 h-1 bg-gray-200 rounded-full dark:bg-slate-600" style={{ zIndex: 0 }}></div>
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
                                            <div className={`flex items-center justify-center w-10 h-10 rounded-full border-4 border-white shadow-lg transition-all duration-300 dark:border-slate-800 ${
                                                isCompleted ? 'bg-gradient-to-br from-green-500 to-emerald-500  text-slate-900 scale-110' :
                                                isActive ? `bg-gradient-to-br ${step.color}  text-slate-900 scale-110 shadow-xl` :
                                                'bg-white text-gray-400 border-gray-300 dark:bg-slate-700 dark:text-slate-500 dark:border-slate-500'
                                            }`}>
                                                {isCompleted ? <CheckCircle className="w-5 h-5" /> : <StepIcon className="w-5 h-5" />}
                                            </div>
                                            <div className="mt-3 text-center hidden sm:block">
                                                <p className={`text-xs font-semibold transition-colors duration-300 ${
                                                    isActive ? 'text-gray-900 dark:text-slate-100' : isCompleted ? 'text-green-600 dark:text-green-400' : 'text-gray-300 dark:text-slate-400'
                                                }`}>
                                                    {step.title}
                                                </p>
                                                <p className="text-xs text-gray-200 mt-0.5 max-w-[120px] dark:text-slate-400">{step.description}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    <Card className="shadow-xl border-none animate-in zoom-in-95 duration-500 bg-gray-100/50 dark:bg-gray-950 backdrop-blur-md border-gray-200 dark:border-slate-600 py-0">
                        <CardHeader className={`py-6 bg-gradient-to-br ${steps[currentStepIndex].color}  text-slate-900 rounded-t-lg`}>
                            <CardTitle className="flex items-center text-xl">
                                {React.createElement(steps[currentStepIndex].icon, { className: "w-6 h-6 mr-3" })}
                                {steps[currentStepIndex].title}
                            </CardTitle>
                            <CardDescription className=" text-slate-900/90 text-sm">
                                {steps[currentStepIndex].description}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="">
                            <form onSubmit={submit} className="space-y-8">
                                {renderStepContent()}

                                <div className="flex justify-between py-8 border-t-2 border-gray-100 dark:border-slate-600">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={prevStep}
                                        disabled={currentStepIndex === 0}
                                        className="px-6 py-2.5 border-2 hover:bg-gray-50 transition-all duration-200 disabled:opacity-40 dark:border-slate-500 dark:hover:bg-slate-700 dark:text-slate-200"
                                    >
                                        <ArrowLeft className="w-4 h-4 mr-2" />
                                        Previous
                                    </Button>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className={`px-6 py-2.5 bg-gradient-to-br ${steps[currentStepIndex].color}  text-slate-900 hover:shadow-lg transition-all duration-200 disabled:opacity-60`}
                                    >
                                        {processing ? (
                                            <>
                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                {currentStep === 'account' ? 'Registering...' : 'Processing...'}
                                            </>
                                        ) : currentStep === 'account' ? (
                                            <>
                                                <CheckCircle className="w-4 h-4 mr-2" />
                                                Complete Registration
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
                                <div className="mb-8 text-center p-4 bg-gray-50 rounded-xl dark:bg-slate-700">
                                    <p className="text-sm text-gray-600 dark:text-slate-300">
                                        <strong className="text-gray-900 dark:text-slate-100">Need help?</strong> Contact our support team at{' '}
                                        <a href="mailto:support@dayadistribution.com" className="text-blue-600 hover:text-blue-700 font-medium hover:underline transition-colors dark:text-blue-400 dark:hover:text-blue-300">
                                            support@dayadistribution.com
                                        </a>
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* <div className="mt-8 grid grid-cols-3 gap-4 animate-in fade-in-50 duration-700 delay-300">
                        <div className="text-center p-4 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow dark:bg-slate-800 dark:border-slate-600 dark:hover:shadow-slate-700">
                            <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">24/7</div>
                            <div className="text-xs text-gray-600 mt-1 dark:text-slate-300">Support Available</div>
                        </div>
                        <div className="text-center p-4 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow dark:bg-slate-800 dark:border-slate-600 dark:hover:shadow-slate-700">
                            <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">10min</div>
                            <div className="text-xs text-gray-600 mt-1 dark:text-slate-300">Setup Time</div>
                        </div>
                        <div className="text-center p-4 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow dark:bg-slate-800 dark:border-slate-600 dark:hover:shadow-slate-700">
                            <div className="text-2xl font-bold text-green-600 dark:text-green-400">100%</div>
                            <div className="text-xs text-gray-600 mt-1 dark:text-slate-300">Success Rate</div>
                        </div>
                    </div> */}

                    {/* Footer */}
                    <div className="text-center mt-12 pb-8">
                        <div className="bg-white rounded-2xl shadow-lg p-8 border border-gray-100 dark:bg-slate-800 dark:border-slate-600 dark:shadow-slate-700">
                            <h3 className="text-xl font-semibold text-gray-900 mb-4 dark:text-slate-100">Need Help?</h3>
                            <p className="text-gray-600 mb-4 dark:text-slate-300">
                                Our support team is here to assist you with your registration
                            </p>
                            <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
                                <a
                                    href="mailto:support@dayadistribution.com"
                                    className="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-green-500  text-slate-900 rounded-lg hover:from-blue-600 hover:to-green-600 transition-all shadow-md hover:shadow-lg dark:from-blue-600 dark:to-green-600 dark:hover:from-blue-700 dark:hover:to-green-700"
                                >
                                    📧 support@dayadistribution.com
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            )}
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
        </div>
    );
}