import React, { useState, useRef, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { CheckCircle, Loader2, Shield, Building, Music, Wallet, FileText, Sparkles, TrendingUp, Users, Award, User, ArrowRight, ArrowLeft, MapPin, Tv } from 'lucide-react';
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

type Step = 'personal' | 'business' | 'preferences' | 'account';

// Calculate max date for 18+ years old (moved outside component for purity)
const MAX_DATE = new Date(Date.now() - 18 * 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

export default function DcdRegister() {
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

    const [data, setData] = useState({
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
        business_address: '',
        phone: '',
        latitude: '',
        longitude: '',
        business_name: '',
        business_types: [] as string[],
        other_business_type: '',
        daily_foot_traffic: '',
        operating_hours_start: '',
        operating_hours_end: '',
        operating_days: [] as string[],
        campaign_types: [] as string[],
        music_genres: [] as string[],
        other_music_genre: '',
        safe_for_kids: false,
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
                        setData(prev => ({ ...prev, turnstile_token: token }));
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
                        // If location permission is granted, show the form
                        setShowForm(true);
                    } catch {
                        // If location permission is denied or fails, redirect to base URL
                        const baseUrl = window.location.origin + window.location.pathname;
                        window.location.href = baseUrl;
                    }
                }
            }
        };

        checkLocationPermission();
    }, [locationPermissionGranted]);

    // Update labels based on selected country
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

    const handleBusinessTypeChange = (type: string, checked: boolean | "indeterminate") => {
        const isChecked = checked === true;
        if (isChecked) {
            setData(prev => ({ ...prev, business_types: [...prev.business_types, type] }));
        } else {
            setData(prev => ({ ...prev, business_types: prev.business_types.filter((t: string) => t !== type) }));
        }
    };

    const handleCampaignTypeChange = (type: string, checked: boolean | "indeterminate") => {
        if (checked === true) {
            setData(prev => ({ ...prev, campaign_types: [...prev.campaign_types, type] }));
        } else {
            setData(prev => ({ ...prev, campaign_types: prev.campaign_types.filter((t: string) => t !== type) }));
        }
    };

    const handleMusicGenreChange = (genre: string, checked: boolean | "indeterminate") => {
        if (checked === true) {
            setData(prev => ({ ...prev, music_genres: [...prev.music_genres, genre] }));
        } else {
            setData(prev => ({ ...prev, music_genres: prev.music_genres.filter((g: string) => g !== genre) }));
        }
    };

    const handleOperatingDayChange = (day: string, checked: boolean | "indeterminate") => {
        if (checked === true) {
            setData(prev => ({ ...prev, operating_days: [...prev.operating_days, day] }));
        } else {
            setData(prev => ({ ...prev, operating_days: prev.operating_days.filter((d: string) => d !== day) }));
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
                    setData(prev => ({
                        ...prev,
                        latitude: latitude.toString(),
                        longitude: longitude.toString(),
                    }));

                    // Try to get location details using reverse geocoding
                    try {
                        const response = await fetch(
                            `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`
                        );
                        const locationData = await response.json();

                        // Map country name to dropdown value
                        let detectedCountry = '';
                        const countryName = locationData.countryName || '';
                        if (countryName.toLowerCase().includes('kenya')) {
                            detectedCountry = 'kenya';
                        } else if (countryName.toLowerCase().includes('nigeria')) {
                            detectedCountry = 'nigeria';
                        }

                        // If country is detected and exists in our dropdown, set it and fetch counties
                        if (detectedCountry && countries.some(c => c.name.toLowerCase() === detectedCountry)) {
                            setData(prev => ({
                                ...prev,
                                country: detectedCountry,
                            }));

                            // Update labels for the detected country
                            updateLabels(detectedCountry);

                            // Fetch counties for the detected country
                            const selectedCountry = countries.find(c => c.name.toLowerCase() === detectedCountry);
                            if (selectedCountry) {
                                try {
                                    const countiesResponse = await fetch(`/api/counties?country_id=${selectedCountry.id}`);
                                    const countiesData = await countiesResponse.json();
                                    setCounties(countiesData);

                                    // Check if the detected county exists in our data
                                    const detectedCounty = locationData.principalSubdivision || '';
                                    const matchingCounty = countiesData.find((county: any) =>
                                        county.name.toLowerCase().includes(detectedCounty.toLowerCase()) ||
                                        detectedCounty.toLowerCase().includes(county.name.toLowerCase())
                                    );

                                    if (matchingCounty) {
                                        // Fetch subcounties for the matched county
                                        try {
                                            const subcountiesResponse = await fetch(`/api/subcounties?county_id=${matchingCounty.id}`);
                                            const subcountiesData = await subcountiesResponse.json();

                                            // Try to match subcounty/locality
                                            const detectedLocality = locationData.locality || '';
                                            const matchingSubcounty = subcountiesData.find((subcounty: any) =>
                                                subcounty.name.toLowerCase().includes(detectedLocality.toLowerCase()) ||
                                                detectedLocality.toLowerCase().includes(subcounty.name.toLowerCase())
                                            );

                                            setData(prev => ({
                                                ...prev,
                                                county: matchingCounty.id.toString(),
                                                subcounty: matchingSubcounty ? matchingSubcounty.id.toString() : '',
                                                ward: '',
                                            }));

                                            // If we have a matching subcounty, fetch its wards
                                            if (matchingSubcounty) {
                                                try {
                                                    const wardsResponse = await fetch(`/api/wards?subcounty_id=${matchingSubcounty.id}`);
                                                    const wardsData = await wardsResponse.json();
                                                    setWards(wardsData);
                                                } catch (wardsError) {
                                                    console.warn('Could not fetch wards:', wardsError);
                                                }
                                            }
                                        } catch (subcountiesError) {
                                            console.warn('Could not fetch subcounties:', subcountiesError);
                                            setData(prev => ({
                                                ...prev,
                                                county: matchingCounty.id.toString(),
                                                subcounty: '',
                                                ward: '',
                                            }));
                                        }
                                    } else {
                                        setData(prev => ({
                                            ...prev,
                                            county: '',
                                            subcounty: '',
                                            ward: '',
                                        }));
                                    }
                                } catch (countiesError) {
                                    console.warn('Could not fetch counties:', countiesError);
                                    setData(prev => ({
                                        ...prev,
                                        county: '',
                                        subcounty: locationData.locality || '',
                                        ward: locationData.localityInfo?.administrative?.[2]?.name || '',
                                    }));
                                }
                            }
                        } else {
                            // Country not detected or not in our list, set defaults
                            setData(prev => ({
                                ...prev,
                                country: 'kenya', // Default to Kenya
                                county: '',
                                subcounty: '',
                                ward: '',
                            }));
                            updateLabels('kenya');
                            // Fetch counties for Kenya as default
                            const kenyaCountry = countries.find(c => c.name.toLowerCase() === 'kenya');
                            if (kenyaCountry) {
                                fetchCounties(kenyaCountry.id);
                            }
                        }
                    } catch (error) {
                        console.warn('Could not fetch location details:', error);
                        // Set default values if geocoding fails
                        setData(prev => ({
                            ...prev,
                            country: 'kenya', // Default to Kenya
                        }));
                        updateLabels('kenya');
                        // Fetch counties for Kenya as default
                        const kenyaCountry = countries.find(c => c.name.toLowerCase() === 'kenya');
                        if (kenyaCountry) {
                            fetchCounties(kenyaCountry.id);
                        }
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
                            errorMessage += 'Location access denied. Please enable location permissions to continue.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Location request timed out.';
                            break;
                        default:
                            errorMessage += 'An unknown error occurred.';
                            break;
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
            if (!data.phone.trim() || !validatePhone(data.phone)) {
                isValid = false;
            }
            if (!data.business_address.trim()) {
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
        } else if (step === 'business') {
            if (!data.business_name.trim()) {
                isValid = false;
            }
            if (data.business_types.length === 0) {
                isValid = false;
            }
            if (!data.daily_foot_traffic) {
                isValid = false;
            }
            if (!data.operating_hours_start || !data.operating_hours_end) {
                isValid = false;
            }
            if (data.operating_days.length === 0) {
                isValid = false;
            }
        } else if (step === 'preferences') {
            if (data.campaign_types.length === 0) {
                isValid = false;
            }
            if (data.campaign_types.includes('music') && data.music_genres.length === 0) {
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
            setTimeout(() => {
                setProcessing(false);
                alert('Registration successful! (Demo mode)');
            }, 2000);
        } else {
            nextStep();
        }
    };

    const businessTypes = [
        { value: 'kiosk_duka', label: 'Kiosk/Duka', category: 'retail' },
        { value: 'mini_supermarket', label: 'Mini Supermarket', category: 'retail' },
        { value: 'wholesale_shop', label: 'Wholesale Shop', category: 'retail' },
        { value: 'hardware_store', label: 'Hardware Store', category: 'retail' },
        { value: 'agrovet', label: 'Agrovet', category: 'retail' },
        { value: 'butchery', label: 'Butchery', category: 'retail' },
        { value: 'boutique', label: 'Boutique', category: 'retail' },
        { value: 'electronics', label: 'Electronics', category: 'retail' },
        { value: 'stationery', label: 'Stationery', category: 'retail' },
        { value: 'general_store', label: 'General Store', category: 'retail' },
        { value: 'salon', label: 'Salon', category: 'services' },
        { value: 'barber_shop', label: 'Barber Shop', category: 'services' },
        { value: 'beauty_parlour', label: 'Beauty Parlour', category: 'services' },
        { value: 'tailor', label: 'Tailor', category: 'services' },
        { value: 'shoe_repair', label: 'Shoe Repair', category: 'services' },
        { value: 'photography_studio', label: 'Photography Studio', category: 'services' },
        { value: 'printing_cyber', label: 'Printing/Cyber', category: 'services' },
        { value: 'laundry', label: 'Laundry', category: 'services' },
        { value: 'cafe', label: 'Café', category: 'food' },
        { value: 'restaurant', label: 'Restaurant', category: 'food' },
        { value: 'fast_food', label: 'Fast-Food Stand', category: 'food' },
        { value: 'mama_mboga', label: 'Mama Mboga', category: 'food' },
        { value: 'milk_atm', label: 'Milk ATM', category: 'food' },
        { value: 'bakery', label: 'Bakery', category: 'food' },
        { value: 'mobile_money', label: 'Mobile Money Agent', category: 'financial' },
        { value: 'bank_agent', label: 'Bank Agent', category: 'financial' },
        { value: 'bill_payment', label: 'Bill Payment', category: 'financial' },
        { value: 'betting_shop', label: 'Betting Shop', category: 'financial' },
        { value: 'boda_boda', label: 'Boda Boda', category: 'transport' },
        { value: 'matatu_sacco', label: 'Matatu', category: 'transport' },
        { value: 'fuel_station', label: 'Fuel Station', category: 'transport' },
        { value: 'car_wash', label: 'Car Wash', category: 'transport' },
        { value: 'church', label: 'Church', category: 'community' },
        { value: 'school_canteen', label: 'School Canteen', category: 'community' },
        { value: 'bar_lounge', label: 'Bar/Lounge', category: 'community' },
        { value: 'pharmacy', label: 'Pharmacy', category: 'community' },
        { value: 'clinic', label: 'Clinic', category: 'community' },
        { value: 'other', label: 'Other (please specify)', category: 'other' },
    ];

    const campaignTypes = [
        { value: 'music', label: 'Music' },
        { value: 'movies', label: 'Movies' },
        { value: 'games', label: 'Games' },
        { value: 'mobile_apps', label: 'Mobile Apps' },
        { value: 'product_launch', label: 'Product Launch' },
        { value: 'product_activation', label: 'Product Activation' },
        { value: 'events', label: 'Events & Promotions' },
        { value: 'education', label: 'Education & Learning' },
    ];

    const musicGenres = [
        'Afrobeat', 'Benga', 'Blues', 'Classical', 'Country', 'Electronic',
        'Folk', 'Funk', 'Gospel', 'Hip Hop', 'Jazz', 'Kwaito', 'Pop', 'R&B',
        'Reggae', 'Rock', 'Soul', 'Traditional', 'Other'
    ];

    const operatingDays = [
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
    ];

    const steps = [
        { id: 'personal', title: 'Personal Information', icon: User, description: 'Your personal details and contact information', color: 'from-blue-500 to-cyan-500' },
        { id: 'business', title: 'Business Details', icon: Building, description: 'Your business information and operations', color: 'from-purple-500 to-pink-500' },
        { id: 'preferences', title: 'Content Preferences', icon: Music, description: 'Choose your content interests and settings', color: 'from-orange-500 to-red-500' },
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
                                <User className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                <div>
                                    <h3 className="font-semibold text-blue-900 dark:text-blue-300">Personal Information</h3>
                                    <p className="text-sm text-blue-700 dark:text-blue-400">Please provide your personal details for verification</p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-5">
                            <div>
                                <Label htmlFor="referral_code" className="text-sm font-medium mb-2 block">
                                    Referral Code (Optional)
                                </Label>
                                <Input
                                    id="referral_code"
                                    type="text"
                                    value={data.referral_code}
                                    onChange={(e) => setData(prev => ({ ...prev, referral_code: e.target.value }))}
                                    placeholder="Enter DA referral code if applicable"
                                    className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="full_name" className="text-sm font-medium mb-2 block">
                                        Full Name <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="full_name"
                                        type="text"
                                        value={data.full_name}
                                        onChange={(e) => setData(prev => ({ ...prev, full_name: e.target.value }))}
                                        required
                                        placeholder="Enter your full name"
                                        className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="national_id" className="text-sm font-medium mb-2 block">
                                        National ID Number <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="national_id"
                                        type="text"
                                        value={data.national_id}
                                        onChange={(e) => setData(prev => ({ ...prev, national_id: e.target.value }))}
                                        required
                                        placeholder="Enter your national ID"
                                        className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="dob" className="text-sm font-medium mb-2 block">
                                        Date of Birth <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="dob"
                                        type="date"
                                        value={data.dob}
                                        onChange={(e) => setData(prev => ({ ...prev, dob: e.target.value }))}
                                        max={MAX_DATE}
                                        required
                                        className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="gender" className="text-sm font-medium mb-2 block">
                                        Gender <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Select value={data.gender} onValueChange={(value) => setData(prev => ({ ...prev, gender: value }))}>
                                        <SelectTrigger className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none">
                                            <SelectValue placeholder="Select gender" />
                                        </SelectTrigger>
                                        <SelectContent className="bg-white dark:bg-slate-800 border-blue-300 dark:border-blue-600/20">
                                            <SelectItem value="male">Male</SelectItem>
                                            <SelectItem value="female">Female</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="email" className="text-sm font-medium mb-2 block">
                                    Email Address <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData(prev => ({ ...prev, email: e.target.value }))}
                                    required
                                    placeholder="preferred@email.com"
                                    className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                            </div>

                            <div>
                                <Label htmlFor="phone" className="text-sm font-medium mb-2 block">
                                    Phone Number <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={data.phone}
                                    onChange={(e) => setData(prev => ({ ...prev, phone: e.target.value }))}
                                    required
                                    placeholder="e.g., 0712 345678"
                                    className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                            </div>

                            <div>
                                <Label htmlFor="business_address" className="text-sm font-medium mb-2 block">
                                    Business Address <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="business_address"
                                    type="text"
                                    value={data.business_address}
                                    onChange={(e) => setData(prev => ({ ...prev, business_address: e.target.value }))}
                                    required
                                    placeholder="Physical location for verification"
                                    className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none"
                                />
                            </div>

                            {/* Location Information */}
                            <div className="bg-blue-50 dark:bg-slate-700 p-4 rounded-lg border border-blue-200 dark:border-blue-600">
                                <div className="flex items-center gap-2 mb-3">
                                    <MapPin className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                    <span className="text-sm font-medium text-blue-900 dark:text-blue-300">Location Information</span>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="country" className="text-sm font-medium mb-2 block">
                                            Country <span className='text-red-500 dark:text-red-400'>*</span>
                                        </Label>
                                        <Select value={data.country} onValueChange={(value) => {
                                            setData(prev => ({ ...prev, country: value, county: '', subcounty: '', ward: '' }));
                                            updateLabels(value);
                                            setCounties([]);
                                            setSubcounties([]);
                                            setWards([]);
                                            const selectedCountry = countries.find(c => c.name.toLowerCase() === value);
                                            if (selectedCountry) {
                                                fetchCounties(selectedCountry.id);
                                            }
                                        }} disabled={countriesLoading || countries.length === 0}>
                                            <SelectTrigger className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none">
                                                <SelectValue placeholder={countriesLoading ? "Loading countries..." : "Select Country"} />
                                            </SelectTrigger>
                                            <SelectContent className="bg-white dark:bg-slate-800 border-blue-300 dark:border-blue-600/20">
                                                {countries.map((country) => (
                                                    <SelectItem key={country.id} value={country.name.toLowerCase()}>
                                                        {country.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="county" className="text-sm font-medium mb-2 block">
                                            {countyLabel} <span className='text-red-500 dark:text-red-400'>*</span>
                                        </Label>
                                        <Select value={data.county} onValueChange={(value) => {
                                            setData(prev => ({ ...prev, county: value, subcounty: '', ward: '' }));
                                            setSubcounties([]);
                                            setWards([]);
                                            const selectedCounty = counties.find(c => c.id.toString() === value);
                                            if (selectedCounty) {
                                                fetchSubcounties(selectedCounty.id);
                                            }
                                        }}>
                                            <SelectTrigger className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none">
                                                <SelectValue placeholder={countiesLoading ? "Loading..." : `Select ${countyLabel.toLowerCase()}`} />
                                            </SelectTrigger>
                                            <SelectContent className="bg-white dark:bg-slate-800 border-blue-300 dark:border-blue-600/20">
                                                {counties.map((county) => (
                                                    <SelectItem key={county.id} value={county.id.toString()}>
                                                        {county.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="subcounty" className="text-sm font-medium mb-2 block">
                                            {subcountyLabel} <span className='text-red-500 dark:text-red-400'>*</span>
                                        </Label>
                                        <Select value={data.subcounty} onValueChange={(value) => {
                                            setData(prev => ({ ...prev, subcounty: value, ward: '' }));
                                            setWards([]);
                                            const selectedSubcounty = subcounties.find(s => s.id.toString() === value);
                                            if (selectedSubcounty) {
                                                fetchWards(selectedSubcounty.id);
                                            }
                                        }} disabled={subcountiesLoading || !data.county}>
                                            <SelectTrigger className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none">
                                                <SelectValue placeholder={subcountiesLoading ? "Loading..." : `Select ${subcountyLabel.toLowerCase()}`} />
                                            </SelectTrigger>
                                            <SelectContent className="bg-white dark:bg-slate-800 border-blue-300 dark:border-blue-600/20">
                                                {subcounties.map((subcounty) => (
                                                    <SelectItem key={subcounty.id} value={subcounty.id.toString()}>
                                                        {subcounty.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="ward" className="text-sm font-medium mb-2 block">
                                            Ward <span className='text-red-500 dark:text-red-400'>*</span>
                                        </Label>
                                        <Select value={data.ward} onValueChange={(value) => setData(prev => ({ ...prev, ward: value }))} disabled={wardsLoading || !data.subcounty}>
                                            <SelectTrigger className="mt-2 border-blue-300 dark:border-blue-600/20 bg-white dark:bg-slate-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none">
                                                <SelectValue placeholder={wardsLoading ? "Loading..." : "Select ward"} />
                                            </SelectTrigger>
                                            <SelectContent className="bg-white dark:bg-slate-800 border-blue-300 dark:border-blue-600/20">
                                                {wards.map((ward) => (
                                                    <SelectItem key={ward.id} value={ward.id.toString()}>
                                                        {ward.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                {data.latitude && data.longitude && (
                                    <div className="mt-3 text-xs text-blue-700 dark:text-blue-400">
                                        📍 Location detected: {data.latitude}, {data.longitude}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                );

            case 'business':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-purple-50 to-pink-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-purple-100 dark:border-purple-600">
                            <div className="flex items-center gap-3 mb-4">
                                <Building className="w-6 h-6 text-purple-600" />
                                <div>
                                    <h3 className="font-semibold text-purple-900 dark:text-purple-300">Business Information</h3>
                                    <p className="text-sm text-purple-700 dark:text-purple-400">Tell us about your business operations</p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <div>
                                <Label htmlFor="business_name" className="text-sm font-medium mb-2 block">
                                    Business Name <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Input
                                    id="business_name"
                                    type="text"
                                    value={data.business_name}
                                    onChange={(e) => setData(prev => ({ ...prev, business_name: e.target.value }))}
                                    placeholder="Official business name"
                                    className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none"
                                />
                            </div>

                            <div>
                                <Label className="text-sm font-medium mb-4 block">
                                    Business Type <span className='text-red-500 dark:text-red-400'>*</span> (Select all that apply)
                                </Label>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto border rounded-lg p-4 bg-purple-50 dark:bg-slate-800 border-purple-200 dark:border-purple-600">
                                    {businessTypes.map((type) => (
                                        <div key={type.value} className="flex items-center space-x-2">
                                            <Checkbox
                                                id={type.value}
                                                checked={data.business_types.includes(type.value)}
                                                onCheckedChange={(checked) => handleBusinessTypeChange(type.value, checked)}
                                                className={`border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-500 focus:ring-purple-500 dark:focus:ring-purple-400 ${ data.business_types.includes(type.value) ? 'bg-purple-100 dark:bg-purple-700' : '' }`}
                                            />
                                            <Label htmlFor={type.value} className="text-sm">
                                                {type.label}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                                {data.business_types.includes('other') && (
                                    <Input
                                        type="text"
                                        value={data.other_business_type}
                                        onChange={(e) => setData(prev => ({ ...prev, other_business_type: e.target.value }))}
                                        placeholder="Please specify other business type"
                                        className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none"
                                    />
                                )}
                            </div>

                            <div>
                                <Label htmlFor="daily_foot_traffic" className="text-sm font-medium mb-2 block">
                                    Daily Foot Traffic <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.daily_foot_traffic} onValueChange={(value) => setData(prev => ({ ...prev, daily_foot_traffic: value }))}>
                                    <SelectTrigger className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none">
                                        <SelectValue placeholder="Select daily foot traffic" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-purple-300 dark:border-purple-600/20">
                                        <SelectItem value="1-10">1-10 people</SelectItem>
                                        <SelectItem value="11-50">11-50 people</SelectItem>
                                        <SelectItem value="51-100">51-100 people</SelectItem>
                                        <SelectItem value="101-500">101-500 people</SelectItem>
                                        <SelectItem value="500+">500+ people</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="operating_hours_start" className="text-sm font-medium mb-2 block">
                                        Opening Time <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="operating_hours_start"
                                        type="time"
                                        value={data.operating_hours_start}
                                        onChange={(e) => setData(prev => ({ ...prev, operating_hours_start: e.target.value }))}
                                        required
                                        className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="operating_hours_end" className="text-sm font-medium mb-2 block">
                                        Closing Time <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="operating_hours_end"
                                        type="time"
                                        value={data.operating_hours_end}
                                        onChange={(e) => setData(prev => ({ ...prev, operating_hours_end: e.target.value }))}
                                        required
                                        className="mt-2 border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-800 focus:border-purple-500 dark:focus:border-purple-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <Label className="text-sm font-medium mb-4 block">
                                    Operating Days <span className='text-red-500 dark:text-red-400'>*</span> (Select all that apply)
                                </Label>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    {operatingDays.map((day) => (
                                        <div key={day} className="flex items-center space-x-2">
                                            <Checkbox
                                                id={day}
                                                checked={data.operating_days.includes(day)}
                                                onCheckedChange={(checked) => handleOperatingDayChange(day, checked)}
                                                className={`border-purple-300 dark:border-purple-600/20 bg-white dark:bg-slate-500 focus:ring-purple-500 dark:focus:ring-purple-400 ${ data.operating_days.includes(day) ? 'bg-purple-100 dark:bg-purple-700' : '' }`}
                                            />
                                            <Label htmlFor={day} className="text-sm capitalize">
                                                {day}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                );

            case 'preferences':
                return (
                    <div className="space-y-6 animate-in fade-in-50 duration-500">
                        <div className="bg-gradient-to-br from-orange-50 to-red-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-orange-100 dark:border-orange-600">
                            <div className="flex items-center gap-3 mb-4">
                                <Music className="w-6 h-6 text-orange-600" />
                                <div>
                                    <h3 className="font-semibold text-orange-900 dark:text-orange-300">Content Preferences</h3>
                                    <p className="text-sm text-orange-700 dark:text-orange-400">Choose the types of content you'd like to distribute</p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <div>
                                <Label className="text-sm font-medium mb-4 block">
                                    Campaign Types <span className='text-red-500 dark:text-red-400'>*</span> (Select all that apply)
                                </Label>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {campaignTypes.map((type) => (
                                        <div key={type.value} className="flex items-center space-x-2 p-3 border-none rounded-lg hover:bg-gray-50 hover:dark:bg-slate-700 transition-colors">
                                            <Checkbox
                                                id={type.value}
                                                checked={data.campaign_types.includes(type.value)}
                                                onCheckedChange={(checked) => handleCampaignTypeChange(type.value, checked)}
                                                className={`border-orange-300 dark:border-orange-600/20 bg-white dark:bg-slate-500 focus:ring-orange-500 dark:focus:ring-orange-400 ${ data.campaign_types.includes(type.value) ? 'bg-orange-100 dark:bg-orange-700' : '' }`}
                                            />
                                            <Label htmlFor={type.value} className="text-sm">
                                                {type.label}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {data.campaign_types.includes('music') && (
                                <div className="animate-in fade-in slide-in-from-top-4 duration-500">
                                    <Label className="text-sm font-medium mb-4 block">
                                        Music Genres <span className='text-red-500 dark:text-red-400'>*</span> (Select all that apply)
                                    </Label>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto border rounded-lg p-4 bg-orange-50 dark:bg-slate-800 border-orange-200 dark:border-orange-600">
                                        {musicGenres.map((genre) => (
                                            <div key={genre} className="flex items-center space-x-2">
                                                <Checkbox
                                                    id={genre}
                                                    checked={data.music_genres.includes(genre)}
                                                    onCheckedChange={(checked) => handleMusicGenreChange(genre, checked)}
                                                    className={`border-orange-300 dark:border-orange-600/20 bg-white dark:bg-slate-500 focus:ring-orange-500 dark:focus:ring-orange-400 ${ data.music_genres.includes(genre) ? 'bg-orange-100 dark:bg-orange-700' : '' }`}
                                                />
                                                <Label htmlFor={genre} className="text-sm">
                                                    {genre}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                    {data.music_genres.includes('Other') && (
                                        <Input
                                            type="text"
                                            value={data.other_music_genre}
                                            onChange={(e) => setData(prev => ({ ...prev, other_music_genre: e.target.value }))}
                                            placeholder="Please specify other music genre"
                                            className="mt-2"
                                        />
                                    )}
                                </div>
                            )}

                            <div className="bg-gradient-to-br from-orange-50 to-red-50 dark:bg-gradient-to-br dark:from-slate-700 dark:to-slate-600 p-6 rounded-xl border-2 border-orange-100 dark:border-orange-600">
                                <div className="flex items-start gap-3">
                                    <Shield className="w-5 h-5 text-orange-600 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <h4 className="font-medium text-orange-900 dark:text-orange-300 mb-2">Content Safety Preference</h4>
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id="safe_for_kids"
                                                checked={data.safe_for_kids}
                                                onCheckedChange={(checked) => setData(prev => ({ ...prev, safe_for_kids: checked === true }))}
                                                className={`border-orange-300 dark:border-orange-600/20 bg-white dark:bg-slate-500 focus:ring-orange-500 dark:focus:ring-orange-400 ${ data.safe_for_kids ? 'bg-orange-100 dark:bg-orange-700' : '' }`}
                                            />
                                            <Label htmlFor="safe_for_kids" className="text-sm text-orange-800 dark:text-orange-300 font-medium">
                                                I prefer content that is safe for children and families
                                            </Label>
                                        </div>
                                    </div>
                                </div>
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
                                    <p className="text-sm text-green-700 dark:text-green-300">Set up your wallet and complete your registration</p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <div>
                                <Label htmlFor="wallet_type" className="text-sm font-medium mb-2 block">
                                    Wallet Type <span className='text-red-500 dark:text-red-400'>*</span>
                                </Label>
                                <Select value={data.wallet_type} onValueChange={(value) => setData(prev => ({ ...prev, wallet_type: value }))}>
                                    <SelectTrigger className="mt-2 border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-800 focus:border-green-500 dark:focus:border-green-400 focus:outline-none">
                                        <SelectValue placeholder="Select your preferred wallet" />
                                    </SelectTrigger>
                                    <SelectContent className="bg-white dark:bg-slate-800 border-green-300 dark:border-green-600/20">
                                        <SelectItem value="mpesa">M-Pesa</SelectItem>
                                        <SelectItem value="airtel">Airtel Money</SelectItem>
                                        <SelectItem value="bank">Bank Account</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="wallet_pin" className="text-sm font-medium mb-2 block">
                                        Wallet PIN <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="wallet_pin"
                                        type="password"
                                        value={data.wallet_pin}
                                        onChange={(e) => setData(prev => ({ ...prev, wallet_pin: e.target.value }))}
                                        required
                                        placeholder="Enter 4-6 digit PIN"
                                        maxLength={6}
                                        className="mt-2 border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-800 focus:border-green-500 dark:focus:border-green-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="confirm_pin" className="text-sm font-medium mb-2 block">
                                        Confirm PIN <span className='text-red-500 dark:text-red-400'>*</span>
                                    </Label>
                                    <Input
                                        id="confirm_pin"
                                        type="password"
                                        value={data.confirm_pin}
                                        onChange={(e) => setData(prev => ({ ...prev, confirm_pin: e.target.value }))}
                                        required
                                        placeholder="Confirm your PIN"
                                        maxLength={6}
                                        className="mt-2 border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-800 focus:border-green-500 dark:focus:border-green-400 focus:outline-none"
                                    />
                                </div>
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
                                                onCheckedChange={(checked) => setData(prev => ({ ...prev, terms: checked === true }))}
                                                className={`border-green-300 dark:border-green-600/20 bg-white dark:bg-slate-500 focus:ring-green-500 dark:focus:ring-green-400 ${ data.terms ? 'bg-green-100 dark:bg-green-700' : '' }`}
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
                        <div className="text-center">
                            <div className="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full mb-6">
                                <Sparkles className="w-5 h-5 mr-2" />
                                <span className="text-sm font-medium">Join Africa's Leading Digital Network</span>
                            </div>

                            <h1 className="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                                Become a Digital Content
                                <span className="block bg-gradient-to-r from-yellow-300 to-orange-300 bg-clip-text text-transparent">
                                    Distributor
                                </span>
                            </h1>


                            {/* Video Section with Modern Card */}
                            <Card className="shadow-2xl border-0 overflow-hidden mb-8 bg-gray-800/50 backdrop-blur-sm py-0">
                                <CardContent className="p-0 !h-full">
                                    <div className="aspect-video bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center overflow-hidden relative">
                                        <iframe
                                            className="w-full h-full"
                                            src="https://www.youtube.com/embed/GDtO2TdRP80"
                                            title="Digital Content Distributor Program Explained"
                                            frameBorder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowFullScreen
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                
                            <div className="bg-gradient-to-r from-green-50 to-purple-50 dark:from-slate-700 dark:to-slate-800 p-6 rounded-xl border-2 border-green-100 dark:border-green-600 mb-8">
                                <p className="text-center text-sm text-gray-700 font-medium dark:text-slate-200">
                                    {/* tv Icon */}
                                    <Tv className="w-4 h-4 inline-block mr-2 mb-1 text-gray-500 dark:text-slate-400" />
                                    Watch this explainer to learn how you can earn by sharing digital content at your business
                                </p>
                            </div>

                            <p className="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto mb-12 dark:text-slate-300">
                                Share content, earn rewards, and grow with Africa's premier digital distribution network
                            </p>

                            {/* Start Button */}
                            <div className="mb-12">
                                <Button
                                    onClick={() => {
                                        // Update URL to make it shareable and trigger location permission check
                                        const url = new URL(window.location.href);
                                        url.searchParams.set('started', 'true');
                                        window.location.href = url.toString();
                                    }}
                                    disabled={locationLoading}
                                    className="!px-8 !py-8 text-lg font-semibold bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white rounded-xl shadow-2xl hover:shadow-yellow-500/25 transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {locationLoading ? (
                                        <>
                                            <Loader2 className="w-6 h-6 mr-3 animate-spin" />
                                            Getting Location...
                                        </>
                                    ) : (
                                        <>
                                            <Sparkles className="w-6 h-6 mr-3" />
                                            Start Registration
                                            <ArrowRight className="w-6 h-6 ml-3" />
                                        </>
                                    )}
                                </Button>
                            </div>

                            {/* Stats Bar */}
                            <div className="grid grid-cols-3 gap-4 max-w-3xl mx-auto">
                                <div className="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                    <div className="flex items-center justify-center mb-2">
                                        <Users className="w-6 h-6 mr-2" />
                                        <div className="text-3xl font-bold">5K+</div>
                                    </div>
                                    <div className="text-sm text-blue-100 dark:text-slate-300">Active Distributors</div>
                                </div>
                                <div className="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 dark:bg-slate-800/50 dark:border-slate-600">
                                    <div className="flex items-center justify-center mb-2">
                                        <TrendingUp className="w-6 h-6 mr-2" />
                                        <div className="text-3xl font-bold">$2M+</div>
                                    </div>
                                    <div className="text-sm text-blue-100 dark:text-slate-300">Distributed</div>
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
                </>
            ) : (
                /* Form Section */
                <div className="max-w-5xl mx-auto px-2 md:px-4 py-8 mt-8 relative z-10">
                    <div className="mb-8 text-center animate-in slide-in-from-top-5 duration-700 flex items-center justify-center flex-col">
                        <h1 className="text-3xl md:text-6xl font-bold leading-tight">
                            Digital 
                            <span className="block bg-gradient-to-r from-yellow-300 to-orange-300 bg-clip-text text-transparent">
                                Content Distributor
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
                                                    isActive ? 'text-orange-400 dark:text-orange-400' : isCompleted ? 'text-green-600 dark:text-green-400' : 'text-yellow-300 dark:text-yellow-400'
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