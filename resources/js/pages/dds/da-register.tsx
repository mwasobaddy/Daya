import React, { useState, useRef, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CheckCircle, Loader2, Shield, Users, Award, TrendingUp, Sparkles, User, ArrowRight, ArrowLeft, Wallet, FileText, MapPin, Phone, Mail, Calendar, Globe, Building2, Instagram, Twitter, Facebook, MessageCircle, Linkedin, Music } from 'lucide-react';
import AppearanceToggleDropdown from '@/components/appearance-dropdown';

declare global {
    interface Window {
        turnstile: {
            render: (element: string | HTMLElement, config: { sitekey: string; callback: (token: string) => void }) => void;
            remove: (element: string | HTMLElement) => void;
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

type Step = 'personal' | 'social' | 'account';

export default function DaRegister({ flash }: { flash?: { success?: string; error?: string } }) {
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
    const [countyLabel, setCountyLabel] = useState('County');
    const [subcountyLabel, setSubcountyLabel] = useState('Sub-county');
    const turnstileRef = useRef(null);

    const { data, setData, post, errors, reset } = useForm({
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
        turnstile_token: '',
    });

    // Initialize Turnstile when component mounts
    useEffect(() => {
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
                        setData('turnstile_token', token);
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

    // Fetch countries on component mount
    useEffect(() => {
        const fetchCountries = async () => {
            try {
                const response = await fetch('/api/countries');
                const data = await response.json();
                setCountries(data);
            } catch (error) {
                console.error('Failed to fetch countries:', error);
            }
        };

        fetchCountries();
    }, []);

    // Check location permission when URL has started=true parameter
    useEffect(() => {
        const checkLocationPermission = async () => {
            if (typeof window !== 'undefined') {
                const urlParams = new URLSearchParams(window.location.search);
                const hasStartedParam = urlParams.get('started') === 'true';

                if (hasStartedParam && !locationPermissionGranted) {
                    try {
                        await requestLocationPermission();
                    } catch (error) {
                        // If location permission is denied, redirect back to landing page
                        window.location.href = '/da/register';
                    }
                }
            }
        };

        checkLocationPermission();
    }, [locationPermissionGranted]);

    const handlePlatformChange = (platform: string, checked: boolean | "indeterminate") => {
        const isChecked = checked === true;
        if (isChecked) {
            setData('platforms', [...data.platforms, platform]);
        } else {
            setData('platforms', data.platforms.filter((p: string) => p !== platform));
        }
    };

    const requestLocationPermission = () => {
        return new Promise<void>((resolve, reject) => {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by this browser.');
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

                        if (locationData.countryName) {
                            setData('country', locationData.countryName.toLowerCase());
                        }
                        if (locationData.principalSubdivision) {
                            setData('county', locationData.principalSubdivision);
                        }
                        if (locationData.city) {
                            setData('subcounty', locationData.city);
                        }
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

                    alert(errorMessage);
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

    const updateLabels = (countryValue: string) => {
        const selectedCountry = countries.find(country => country.name.toLowerCase() === countryValue);
        if (selectedCountry) {
            setCountyLabel(selectedCountry.county_label);
            setSubcountyLabel(selectedCountry.subcounty_label);
        } else {
            setCountyLabel('County');
            setSubcountyLabel('Sub-county');
        }
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

    const validateStep = (step: Step): boolean => {
        let isValid = true;

        if (step === 'personal') {
            if (!data.full_name.trim()) {
                isValid = false;
            }
            if (!data.national_id.trim()) {
                isValid = false;
            }
            if (!data.dob) {
                isValid = false;
            }
            if (!data.gender) {
                isValid = false;
            }
            if (!data.email.trim() || !validateEmail(data.email)) {
                isValid = false;
            }
            if (!data.country.trim()) {
                isValid = false;
            }
            if (!data.county.trim()) {
                isValid = false;
            }
            if (!data.subcounty.trim()) {
                isValid = false;
            }
            if (!data.ward.trim()) {
                isValid = false;
            }
            if (!data.address.trim()) {
                isValid = false;
            }
            if (!data.phone.trim() || !validatePhone(data.phone)) {
                isValid = false;
            }
        } else if (step === 'social') {
            if (data.platforms.length === 0) {
                isValid = false;
            }
            if (!data.followers) {
                isValid = false;
            }
            if (!data.communication_channel) {
                isValid = false;
            }
        } else if (step === 'account') {
            if (!data.wallet_type) {
                isValid = false;
            }
            if (!data.wallet_pin || data.wallet_pin.length < 4) {
                isValid = false;
            }
            if (data.wallet_pin !== data.confirm_pin) {
                isValid = false;
            }
            if (!data.terms) {
                isValid = false;
            }
            if (!data.turnstile_token) {
                isValid = false;
            }
        }

        return isValid;
    };

    const nextStep = () => {
        if (validateStep(currentStep)) {
            const nextIndex = currentStepIndex + 1;
            if (nextIndex < steps.length) {
                setCurrentStep(steps[nextIndex].id as Step);
            }
        }
    };

    const prevStep = () => {
        const prevIndex = currentStepIndex - 1;
        if (prevIndex >= 0) {
            setCurrentStep(steps[prevIndex].id as Step);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (currentStep === 'account') {
            if (!validateStep('account')) {
                return;
            }
            setProcessing(true);
            post('/api/da/create', {
                onSuccess: () => {
                    setProcessing(false);
                    reset();
                    alert('Registration successful! (Demo mode)');
                },
                onError: () => {
                    setProcessing(false);
                },
            });
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
                                    onChange={(e) => setData('referral_code', e.target.value)}
                                    required
                                    placeholder="Enter referring DA's code"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                {errors.referral_code && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.referral_code}</p>
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
                                    onChange={(e) => setData('full_name', e.target.value)}
                                    required
                                    placeholder="Enter your full name"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                {errors.full_name && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.full_name}</p>
                                )}
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
                                    onChange={(e) => setData('national_id', e.target.value)}
                                    required
                                    placeholder="Enter your national ID"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                {errors.national_id && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.national_id}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="dob" className="text-sm font-medium mb-2 block">
                                    Date of Birth <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="dob"
                                    type="date"
                                    value={data.dob}
                                    onChange={(e) => setData('dob', e.target.value)}
                                    required
                                    max="2007-11-12"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                {errors.dob && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.dob}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="gender" className="text-sm font-medium mb-2 block">
                                    Gender <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.gender} onValueChange={(value) => setData('gender', value)}>
                                    <SelectTrigger className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none">
                                        <SelectValue placeholder="Select gender" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-blue-300 dark:border-blue-600/20">
                                        <SelectItem value="male">Male</SelectItem>
                                        <SelectItem value="female">Female</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.gender && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.gender}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="email" className="text-sm font-medium mb-2 block">
                                    Email Address <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    placeholder="primary@email.com"
                                    className="border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                                {errors.email && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.email}</p>
                                )}
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
                                            className="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 text-sm"
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
                                        updateLabels(value);
                                    }}>
                                        <SelectTrigger className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none">
                                            <SelectValue placeholder="Select Country" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white dark:bg-slate-800 border-cyan-300 dark:border-cyan-600/20">
                                            <SelectItem value="kenya">Kenya</SelectItem>
                                            <SelectItem value="nigeria">Nigeria</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.country && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.country}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="county" className="text-sm font-medium mb-2 block">
                                        {countyLabel} <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="county"
                                        type="text"
                                        value={data.county}
                                        onChange={(e) => setData('county', e.target.value)}
                                        required
                                        placeholder="Enter county"
                                        className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none"
                                    />
                                    {errors.county && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.county}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <Label htmlFor="subcounty" className="text-sm font-medium mb-2 block">
                                        {subcountyLabel} <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="subcounty"
                                        type="text"
                                        value={data.subcounty}
                                        onChange={(e) => setData('subcounty', e.target.value)}
                                        required
                                        placeholder="Enter sub-county"
                                        className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none"
                                    />
                                    {errors.subcounty && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.subcounty}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="ward" className="text-sm font-medium mb-2 block">
                                        Ward <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="ward"
                                        type="text"
                                        value={data.ward}
                                        onChange={(e) => setData('ward', e.target.value)}
                                        required
                                        placeholder="Enter ward"
                                        className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none"
                                    />
                                    {errors.ward && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.ward}</p>
                                    )}
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
                                    onChange={(e) => setData('address', e.target.value)}
                                    required
                                    placeholder="Enter your full address"
                                    className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none"
                                />
                                {errors.address && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.address}</p>
                                )}
                            </div>

                            <div className="mt-4">
                                <Label htmlFor="phone" className="text-sm font-medium mb-2 block">
                                    Phone Number <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    required
                                    placeholder="e.g., 0712 345678"
                                    className="border-cyan-300 dark:border-cyan-600/20 bg-white dark:bg-slate-800 focus:border-cyan-500 dark:focus:border-cyan-400 focus:outline-none"
                                />
                                {errors.phone && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.phone}</p>
                                )}
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
                            {errors.platforms && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.platforms}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="followers" className="text-sm font-medium mb-2 block">
                                    Total Followers <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.followers} onValueChange={(value) => setData('followers', value)}>
                                    <SelectTrigger className="border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none">
                                        <SelectValue placeholder="Select Range" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-purple-300 dark:border-purple-600/20">
                                        <SelectItem value="500-1000">500–1,000</SelectItem>
                                        <SelectItem value="1000-5000">1,000–5,000</SelectItem>
                                        <SelectItem value="5000-50000">5,000–50,000</SelectItem>
                                        <SelectItem value="50000-100000">50,000–100,000</SelectItem>
                                        <SelectItem value="100000-500000">100,000–500,000</SelectItem>
                                        <SelectItem value="500000-1000000">500,000–1M+</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.followers && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.followers}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="communication_channel" className="text-sm font-medium mb-2 block">
                                    Preferred Communication Channel <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.communication_channel} onValueChange={(value) => setData('communication_channel', value)}>
                                    <SelectTrigger className="border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none">
                                        <SelectValue placeholder="Select Channel" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-purple-300 dark:border-purple-600/20">
                                        <SelectItem value="whatsapp">WhatsApp</SelectItem>
                                        <SelectItem value="email">Email</SelectItem>
                                        <SelectItem value="in-app">In-app Messaging</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.communication_channel && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.communication_channel}</p>
                                )}
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
                                <Select value={data.wallet_type} onValueChange={(value) => setData('wallet_type', value)}>
                                    <SelectTrigger className="border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-800 focus:border-green-500 dark:focus:border-green-400 focus:outline-none">
                                        <SelectValue placeholder="Select Wallet Type" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-green-300 dark:border-green-600/20">
                                        <SelectItem value="personal">Personal</SelectItem>
                                        <SelectItem value="business">Business</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.wallet_type && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.wallet_type}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="wallet_pin" className="text-sm font-medium mb-2 block">
                                    Wallet PIN (4-digit) <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="wallet_pin"
                                    type="password"
                                    value={data.wallet_pin}
                                    onChange={(e) => setData('wallet_pin', e.target.value)}
                                    required
                                    maxLength={4}
                                    placeholder="Enter 4-digit PIN"
                                    className="border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-800 focus:border-green-500 dark:focus:border-green-400 focus:outline-none"
                                />
                                {errors.wallet_pin && (
                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.wallet_pin}</p>
                                )}
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
                                onChange={(e) => setData('confirm_pin', e.target.value)}
                                required
                                maxLength={4}
                                placeholder="Confirm your PIN"
                                className="border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-800 focus:border-green-500 dark:focus:border-green-400 focus:outline-none"
                            />
                            {errors.confirm_pin && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.confirm_pin}</p>
                            )}
                        </div>

                        <div className="bg-emerald-50 dark:bg-slate-700 border-l-4 border-emerald-400 dark:border-emerald-600 p-4 rounded-r-lg">
                            <p className="text-sm text-emerald-800 dark:text-emerald-300">
                                <strong>Important:</strong> Keep your PIN secure and do not share it with anyone. You'll need it to access your earnings and manage your account.
                            </p>
                        </div>

                        <div className="bg-gradient-to-r from-green-50 to-purple-50 dark:from-slate-700 dark:to-slate-800 p-6 rounded-xl border-2 border-green-100 dark:border-green-600">
                            <div className="flex items-start gap-3">
                                <FileText className="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" />
                                <div className="flex-1">
                                    <h4 className="font-medium text-green-900 dark:text-green-300 mb-3">Terms & Conditions</h4>
                                    <div className="text-sm text-green-800 dark:text-green-400 mb-4 space-y-2">
                                        <p>• I agree to distribute digital content through my business premises</p>
                                        <p>• I understand that content distribution must comply with local laws and regulations</p>
                                        <p>• I will maintain appropriate content ratings and safety standards</p>
                                        <p>• I acknowledge that earnings depend on content performance and user engagement</p>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="terms"
                                            checked={data.terms}
                                            onCheckedChange={(checked) => setData('terms', checked === true)}
                                            className={`border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-500 focus:ring-green-500 dark:focus:ring-green-400 ${data.terms ? 'bg-green-100 dark:bg-green-700' : ''}`}
                                        />
                                        <Label htmlFor="terms" className="text-sm text-green-800 dark:text-green-300 font-medium">
                                            I agree to the terms and conditions <span className='text-red-500 dark:text-red-400'>*</span>
                                        </Label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-gradient-to-br from-red-50 to-orange-50 dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-red-400">
                            <div className="flex items-center justify-center py-4">
                                <div className="text-center">
                                    <Shield className="w-8 h-8 text-red-600 dark:text-red-400 mx-auto mb-2" />
                                    <h4 className="font-medium text-red-900 dark:text-red-300 mb-2">Security Verification</h4>
                                    <p className="text-sm text-red-700 dark:text-red-400 mb-4">Complete the security check to finalize your registration</p>
                                    <div ref={turnstileRef} className="flex justify-center"></div>
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
        <div className="h-screen bg-background text-foreground overflow-y-auto bg-gradient-to-r from-blue-300 via-indigo-400 to-purple-300 text-white dark:from-slate-700 dark:via-slate-800 dark:to-slate-700">
            {/* Appearance Toggle */}
            <div className="absolute top-4 right-4 z-50">
                <AppearanceToggleDropdown />
            </div>

            <div className="absolute inset-0 bg-black opacity-10"></div>
            <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0zNiAxOGMzLjMxNCAwIDYgMi42ODYgNiA2cy0yLjY4NiA2LTYgNi02LTIuNjg2LTYtNiAyLjY4Ni02IDYtNiIgc3Ryb2tlPSIjZmZmIiBzdHJva2Utd2lkdGg9IjIiIG9wYWNpdHk9Ii4xIi8+PC9nPjwvc3ZnPg==')] opacity-100 dark:opacity-80"></div>

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

                                <p className="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto mb-12 dark:text-slate-300">
                                    Join our community of influencers and earn rewards while promoting Daya across Africa
                                </p>

                                {/* Earnings Potential Section */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto mb-12">
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-3">
                                            <Award className="w-8 h-8 mr-2" />
                                            <div className="text-2xl font-bold">5% Commission</div>
                                        </div>
                                        <div className="text-sm text-blue-100 dark:text-slate-300">Earn 5% of all earnings from every DCD you recruit</div>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-3">
                                            <TrendingUp className="w-8 h-8 mr-2" />
                                            <div className="text-2xl font-bold">Venture Shares</div>
                                        </div>
                                        <div className="text-sm text-blue-100 dark:text-slate-300">Build ownership in the platform as you grow the network</div>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-3">
                                            <Users className="w-8 h-8 mr-2" />
                                            <div className="text-2xl font-bold">Residual Income</div>
                                        </div>
                                        <div className="text-sm text-blue-100 dark:text-slate-300">Ongoing commissions from your recruited DCDs' scans</div>
                                    </div>
                                </div>

                                {/* Video Section with Modern Card */}
                                <Card className="shadow-2xl border-0 overflow-hidden mb-8 bg-gray-800/50 backdrop-blur-sm py-0">
                                    <CardContent className="p-0 !h-full">
                                        <div className="aspect-video bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center overflow-hidden relative">
                                            <iframe
                                                className="w-full h-full"
                                                src="https://www.youtube.com/embed/MiQ45k5rx1Q"
                                                title="Digital Ambassador Program Explained"
                                                frameBorder="0"
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
                                        className="!px-8 !py-8 text-lg font-semibold bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white rounded-xl shadow-2xl hover:shadow-yellow-500/25 transition-all duration-300 transform hover:scale-105"
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
                                        <div className="text-sm text-blue-100 dark:text-slate-300">Active Ambassadors</div>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-2">
                                            <TrendingUp className="w-6 h-6 mr-2" />
                                            <div className="text-3xl font-bold">$50K+</div>
                                        </div>
                                        <div className="text-sm text-blue-100 dark:text-slate-300">Commissions Paid</div>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                        <div className="flex items-center justify-center mb-2">
                                            <Award className="w-6 h-6 mr-2" />
                                            <div className="text-3xl font-bold">98%</div>
                                        </div>
                                        <div className="text-sm text-blue-100 dark:text-slate-300">Success Rate</div>
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
                                                isCompleted ? 'bg-gradient-to-br from-green-500 to-emerald-500 text-white scale-110' :
                                                isActive ? `bg-gradient-to-br ${step.color} text-white scale-110 shadow-xl` :
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
                        <CardHeader className={`py-6 bg-gradient-to-br ${steps[currentStepIndex].color} text-white rounded-t-lg`}>
                            <CardTitle className="flex items-center text-xl">
                                {React.createElement(steps[currentStepIndex].icon, { className: "w-6 h-6 mr-3" })}
                                {steps[currentStepIndex].title}
                            </CardTitle>
                            <CardDescription className="text-white/90 text-sm">
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
                                        className={`px-6 py-2.5 bg-gradient-to-br ${steps[currentStepIndex].color} text-white hover:shadow-lg transition-all duration-200 disabled:opacity-60`}
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
                                        <a href="mailto:support@daya.africa" className="text-blue-600 hover:text-blue-700 font-medium hover:underline transition-colors dark:text-blue-400 dark:hover:text-blue-300">
                                            support@daya.africa
                                        </a>
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="mt-8 grid grid-cols-3 gap-4 animate-in fade-in-50 duration-700 delay-300">
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
                    </div>

                    {/* Footer */}
                    <div className="text-center mt-12 pb-8">
                        <div className="bg-white rounded-2xl shadow-lg p-8 border border-gray-100 dark:bg-slate-800 dark:border-slate-600 dark:shadow-slate-700">
                            <h3 className="text-xl font-semibold text-gray-900 mb-4 dark:text-slate-100">Need Help?</h3>
                            <p className="text-gray-600 mb-4 dark:text-slate-300">
                                Our support team is here to assist you with your registration
                            </p>
                            <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
                                <a
                                    href="mailto:support@daya.africa"
                                    className="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg hover:from-blue-600 hover:to-green-600 transition-all shadow-md hover:shadow-lg dark:from-blue-600 dark:to-green-600 dark:hover:from-blue-700 dark:hover:to-green-700"
                                >
                                    📧 support@daya.africa
                                </a>
                                <span className="text-gray-400 dark:text-slate-400">or</span>
                                <span className="text-gray-600 font-medium dark:text-slate-300">📞 Call: +254 700 123 456</span>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}